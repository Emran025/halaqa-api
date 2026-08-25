<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_general_profile_and_student_levels(): void
    {
        $user = User::factory()->student()->create(['password' => 'password123']);
        StudentProfile::create(['user_id' => $user->id, 'memorization_level' => 'beginner', 'review_level' => 'beginner']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->patchJson('/api/v1/me', [
            'name' => 'Updated Student',
            'phone' => '500000099',
            'memorization_level' => 'advanced',
            'review_level' => 'intermediate',
        ])->assertOk()->assertJsonPath('user.name', 'Updated Student')->assertJsonPath('user.phone', '500000099')->assertJsonMissingPath('data');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Student', 'phone' => '500000099']);
        $this->assertDatabaseHas('student_profiles', ['user_id' => $user->id, 'memorization_level' => 'advanced', 'review_level' => 'intermediate']);
    }

    public function test_teacher_cannot_update_student_memorization_fields_via_general_profile(): void
    {
        $teacher = User::factory()->teacher()->create();
        $token = $teacher->createToken('test')->plainTextToken;

        $this->withToken($token)->patchJson('/api/v1/me', [
            'memorization_level' => 'advanced',
        ])->assertForbidden();
    }

    public function test_profile_update_rejects_unknown_fields(): void
    {
        $user = User::factory()->student()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->patchJson('/api/v1/me', [
            'name' => 'Updated Student',
            'unexpected' => true,
        ])->assertUnprocessable()->assertJsonPath('field_errors.0.field', '_schema');
    }
}
