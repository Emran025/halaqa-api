<?php

namespace Tests\Feature;

use App\Models\Halaqa;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_list_own_sessions_with_explicit_meta(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $halaqa = Halaqa::create([
            'id' => (string) Str::uuid(),
            'teacher_id' => $teacher->id,
            'name' => 'Sessions Halaqa',
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
        $token = $student->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/sessions?state=ended&per_page=10')
            ->assertOk()
            ->assertJsonStructure(['sessions', 'meta'])
            ->assertJsonPath('sessions.0.id', (string) $session->id)
            ->assertJsonPath('sessions.0.state', 'ended')
            ->assertJsonPath('sessions.0.direct_p2p_only', true)
            ->assertJsonMissingPath('data');
    }

    public function test_unknown_session_query_fields_are_rejected(): void
    {
        $student = User::factory()->student()->create();
        $token = $student->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/sessions?unexpected=true')
            ->assertUnprocessable()
            ->assertJsonPath('field_errors.0.field', '_schema');
    }
}
