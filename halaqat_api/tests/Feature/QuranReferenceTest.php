<?php

namespace Tests\Feature;

use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuranReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(QuranReferenceSeeder::class);
    }

    public function test_authenticated_client_can_read_surahs_pages_and_ayahs(): void
    {
        $student = $this->postJson('/api/v1/auth/register/student', [
            'name' => 'Quran Reader', 'username' => 'quran_'.Str::lower(Str::random(6)), 'email' => 'quran_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
            'gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => '500000099', 'phone_zone' => '+966',
            'memorization_level' => 'beginner', 'previous_memorization' => ['memorized_juz_count' => 0, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => []],
            'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00', 'preferred' => true]], 'preferred_session_duration_minutes' => 30],
            'follow_up_plan' => ['frequency' => 'twiceAWeek', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1, 'notes' => null]]], 'teacher_code' => null, 'profile_bio' => null, 'client_operation_id' => (string) Str::uuid(),
        ])->assertCreated()->json();

        $this->withToken($student['token'])->getJson('/api/v1/quran/surahs')->assertOk()->assertJsonCount(114, 'surahs')->assertJsonPath('surahs.0.edition_id', 1)->assertJsonMissingPath('data');
        $this->withToken($student['token'])->getJson('/api/v1/quran/pages/1')->assertOk()->assertJsonPath('quran_page.page_number', 1)->assertJsonPath('quran_page.ayahs.0.id', 1);
        $this->withToken($student['token'])->getJson('/api/v1/quran/ayahs/1')->assertOk()->assertJsonPath('ayah.surah_id', 1)->assertJsonPath('ayah.edition_id', 1);
    }
}
