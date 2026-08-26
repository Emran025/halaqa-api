<?php

namespace Tests\Feature;

use App\Models\Halaqa;
use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProgressQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_read_progress_with_totals_and_last_completed_ranges(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $halaqa = Halaqa::create([
            'id' => (string) Str::uuid(),
            'teacher_id' => $teacher->id,
            'name' => 'Progress Halaqa',
            'gender' => $student->gender,
            'country' => $student->country,
            'residence' => 'Riyadh',
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $session = LiveSession::create([
            'id' => (string) Str::uuid(),
            'halaqa_id' => $halaqa->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'task_type_id' => 1,
            'state' => 'ended',
            'requested_at' => now()->subHour(),
            'ended_at' => now(),
            'direct_p2p_only' => true,
            'client_operation_id' => (string) Str::uuid(),
        ]);
        SessionTask::create([
            'id' => (string) Str::uuid(),
            'session_id' => $session->id,
            'client_operation_id' => (string) Str::uuid(),
            'tracking_type_id' => 1,
            'sequence_no' => 1,
            'state' => 'completed',
            'start_page' => 10,
            'start_ayah_id' => 100,
            'end_page' => 12,
            'end_ayah_id' => 120,
            'completed_at' => now(),
        ]);
        $token = $student->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/students/'.$student->id.'/progress?task_type=memorization')
            ->assertOk()
            ->assertJsonPath('progress.student_id', (string) $student->id)
            ->assertJsonPath('progress.totals.total_sessions', 1)
            ->assertJsonPath('progress.totals.total_tasks', 1)
            ->assertJsonPath('progress.totals.memorization_tasks', 1)
            ->assertJsonPath('progress.totals.review_tasks', 0)
            ->assertJsonPath('progress.last_completed.memorization.start_page', 10)
            ->assertJsonPath('progress.last_completed.review', null)
            ->assertJsonMissingPath('data');
    }
}
