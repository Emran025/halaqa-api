<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\TeacherDocument;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_read_and_update_detailed_profile(): void
    {
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = $student->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me/student-profile')
            ->assertOk()
            ->assertJsonStructure(['student_profile' => [
                'id', 'role', 'name', 'email', 'previous_memorization',
                'attendance_preferences', 'follow_up_plan', 'visibility',
            ]])
            ->assertJsonPath('student_profile.visibility', 'self')
            ->assertJsonMissingPath('data');

        $this->withToken($token)->patchJson('/api/v1/me/student-profile', [
            'name' => 'Detailed Student',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'country' => 'Saudi Arabia',
            'city' => 'Riyadh',
            'phone' => '500000010',
            'phone_zone' => '+966',
            'memorization_level' => 'advanced',
            'previous_memorization' => [
                'memorized_juz_count' => 8.5,
                'memorized_surah_ids' => [1, 2, 3],
                'last_completed_unit' => ['task_type' => 'memorization', 'unit' => 'page', 'amount' => 2, 'notes' => 'Good'],
                'previous_teacher_notes' => 'Consistent',
                'stop_reasons' => null,
            ],
        ])->assertOk()
            ->assertJsonPath('student_profile.name', 'Detailed Student')
            ->assertJsonPath('student_profile.previous_memorization.memorized_juz_count', 8.5)
            ->assertJsonPath('student_profile.previous_memorization.memorized_surah_ids.0', 1);

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'memorized_juz_count' => 8.5,
            'previous_memorization_notes' => 'Consistent',
        ]);
    }

    public function test_profile_detail_routes_are_restricted_by_role(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        StudentProfile::create(['user_id' => $student->id]);
        TeacherProfile::create(['user_id' => $teacher->id, 'teacher_code' => 'TCH-TEST-'.$teacher->id, 'qualification' => 'Ijazah']);
        $studentToken = $student->createToken('test')->plainTextToken;
        $teacherToken = $teacher->createToken('test')->plainTextToken;

        $this->withToken($teacherToken)->getJson('/api/v1/me/student-profile')->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($studentToken)->getJson('/api/v1/me/teacher-profile')->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($teacherToken)->patchJson('/api/v1/me/student-profile', ['name' => 'No'])->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($studentToken)->patchJson('/api/v1/me/teacher-profile', ['bio' => 'No'])->assertForbidden();
    }

    public function test_teacher_can_read_update_and_manage_private_documents(): void
    {
        Storage::fake('local');
        $teacher = User::factory()->teacher()->create();
        TeacherProfile::create(['user_id' => $teacher->id, 'teacher_code' => 'TCH-TEST-'.$teacher->id, 'qualification' => 'Ijazah']);
        $token = $teacher->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me/teacher-profile')
            ->assertOk()
            ->assertJsonPath('teacher_profile.qualification', 'Ijazah')
            ->assertJsonStructure(['teacher_profile' => ['public_halaqas', 'documents']])
            ->assertJsonMissingPath('data');

        $this->withToken($token)->patchJson('/api/v1/me/teacher-profile', [
            'name' => 'Updated Teacher',
            'qualification' => 'Advanced Ijazah',
            'experience_years' => 15,
            'available_time' => '18:30',
            'bio' => 'Teacher biography',
            'max_halaqas' => 3,
        ])->assertOk()
            ->assertJsonPath('teacher_profile.display_name', 'Updated Teacher')
            ->assertJsonPath('teacher_profile.qualification', 'Advanced Ijazah');

        $response = $this->withToken($token)->post('/api/v1/me/teacher-documents', [
            'name' => 'Ijazah Certificate',
            'certificate_type' => 'ijazah',
            'certificate_type_other' => null,
            'riwayah' => 'Hafs',
            'issuing_place' => 'Riyadh',
            'issuing_date' => '2024-01-01',
            'file' => UploadedFile::fake()->create('certificate.pdf', 20, 'application/pdf'),
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['teacher_document' => ['id', 'name', 'certificate_type', 'has_file']])
            ->assertJsonPath('teacher_document.has_file', true)
            ->assertJsonMissingPath('teacher_document.storage_path');

        $documentId = $response->json('teacher_document.id');
        $this->withToken($token)->getJson('/api/v1/me/teacher-documents')
            ->assertOk()
            ->assertJsonStructure(['documents', 'meta'])
            ->assertJsonPath('documents.0.id', $documentId)
            ->assertJsonMissingPath('documents.0.storage_path');

        $this->withToken($token)->deleteJson('/api/v1/me/teacher-documents/'.$documentId)->assertNoContent();
        $this->assertSoftDeleted('teacher_documents', ['id' => $documentId]);
    }

    public function test_teacher_document_delete_rejects_other_teachers(): void
    {
        $owner = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();
        $document = TeacherDocument::create([
            'teacher_id' => $owner->id,
            'name' => 'Certificate',
            'certificate_type' => 'ijazah',
        ]);
        $token = $other->createToken('test')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/v1/me/teacher-documents/'.$document->id)->assertForbidden();
        $this->assertDatabaseHas('teacher_documents', ['id' => $document->id, 'deleted_at' => null]);
    }

    public function test_student_can_read_own_detailed_profile_but_unrelated_teacher_cannot(): void
    {
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $teacher = User::factory()->teacher()->create();
        $studentToken = $student->createToken('test')->plainTextToken;
        $teacherToken = $teacher->createToken('test')->plainTextToken;

        $this->withToken($studentToken)->getJson('/api/v1/students/'.$student->id)
            ->assertOk()
            ->assertJsonPath('student_profile.visibility', 'self');
        app('auth')->forgetGuards();
        $this->withToken($teacherToken)->getJson('/api/v1/students/'.$student->id)->assertForbidden();
    }

    public function test_student_can_discover_public_teachers_without_sensitive_fields(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create([
            'name' => 'Public Teacher',
            'email' => 'public-teacher@example.test',
            'phone' => '500000099',
            'country' => 'Saudi Arabia',
            'city' => 'Riyadh',
        ]);
        TeacherProfile::create([
            'user_id' => $teacher->id,
            'teacher_code' => 'TCH-PUBLIC-'.$teacher->id,
            'qualification' => 'Ijazah',
            'experience_years' => 10,
            'bio' => 'Public biography',
            'max_halaqas' => 2,
        ]);
        $token = $student->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/teachers?search=Public&per_page=10')
            ->assertOk()
            ->assertJsonStructure(['teachers', 'meta'])
            ->assertJsonPath('teachers.0.display_name', 'Public Teacher')
            ->assertJsonMissingPath('teachers.0.email')
            ->assertJsonMissingPath('teachers.0.phone')
            ->assertJsonMissingPath('data');

        $this->withToken($token)->getJson('/api/v1/teachers/'.$teacher->id)
            ->assertOk()
            ->assertJsonPath('teacher.display_name', 'Public Teacher')
            ->assertJsonPath('teacher.bio', 'Public biography')
            ->assertJsonMissingPath('teacher.email')
            ->assertJsonMissingPath('teacher.phone')
            ->assertJsonMissingPath('teacher.documents');
    }

    public function test_teacher_list_is_restricted_to_students_and_query_is_strict(): void
    {
        $teacher = User::factory()->teacher()->create();
        TeacherProfile::create(['user_id' => $teacher->id, 'teacher_code' => 'TCH-LIST-'.$teacher->id, 'qualification' => 'Ijazah']);
        $token = $teacher->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/teachers')->assertForbidden();
        app('auth')->forgetGuards();
        $student = User::factory()->student()->create();
        $studentToken = $student->createToken('test')->plainTextToken;
        $this->withToken($studentToken)->getJson('/api/v1/teachers?unexpected=true')
            ->assertUnprocessable()
            ->assertJsonPath('field_errors.0.field', '_schema');
    }

    public function test_student_profile_patch_can_update_attendance_and_follow_up_plan(): void
    {
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = $student->createToken('test')->plainTextToken;

        $this->withToken($token)->patchJson('/api/v1/me/student-profile', [
            'attendance_preferences' => [
                'timezone' => 'Asia/Riyadh',
                'weekly_slots' => [['day_of_week' => 1, 'from' => '18:00', 'to' => '19:00', 'preferred' => true]],
                'preferred_session_duration_minutes' => 45,
            ],
            'follow_up_plan' => [
                'frequency' => 'onceAWeek',
                'details' => [['task_type' => 'review', 'unit' => 'page', 'amount' => 3, 'notes' => 'Weekly']],
                'starts_on' => '2026-08-26',
            ],
        ])->assertOk()
            ->assertJsonPath('student_profile.attendance_preferences.timezone', 'Asia/Riyadh')
            ->assertJsonPath('student_profile.follow_up_plan.frequency', 'onceAWeek');

        $this->assertDatabaseHas('student_availability_profiles', ['student_id' => $student->id, 'timezone' => 'Asia/Riyadh']);
        $this->assertDatabaseHas('follow_up_plans', ['student_id' => $student->id, 'frequency' => 'onceAWeek']);
    }

    public function test_detailed_profile_rejects_unknown_fields(): void
    {
        $student = User::factory()->student()->create();
        StudentProfile::create(['user_id' => $student->id]);
        $token = $student->createToken('test')->plainTextToken;

        $this->withToken($token)->patchJson('/api/v1/me/student-profile', [
            'unknown' => true,
        ])->assertUnprocessable()->assertJsonPath('field_errors.0.field', '_schema');
    }
}
