<?php

namespace Tests\Feature;

use App\Exceptions\RealtimeProtocolException;
use App\Models\Halaqa;
use App\Models\LiveSession;
use App\Models\RealtimeOutboxMessage;
use App\Models\SessionReport;
use App\Models\User;
use App\Realtime\RealtimeEventTypes;
use App\Realtime\Signaling\RealtimeServerEventEnvelopeFactory;
use App\Realtime\WebSocket\ConnectionManager;
use App\Realtime\WebSocket\FrameCodec;
use App\Realtime\WebSocket\RealtimeOutboxDispatcher;
use App\Services\Reports\SessionReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RealtimeDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_delivers_server_event_to_only_the_target_participant_and_marks_delivery(): void
    {
        [$session, $teacher, $student] = $this->sessionFixture();
        $message = RealtimeOutboxMessage::create([
            'id' => (string) Str::uuid(),
            'session_id' => $session->id,
            'recipient_id' => $student->id,
            'event_type' => RealtimeEventTypes::DIRECT_CONNECTION_UNAVAILABLE,
            'dedupe_key' => hash('sha256', 'delivery-test'),
            'payload' => ['state' => 'direct_connection_unavailable', 'reason' => 'No direct route'],
        ]);
        [$teacherSocket, $studentSocket] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
        $manager = new ConnectionManager;
        $manager->add($teacherSocket, $teacher, $session->id, $student->id);
        $manager->add($studentSocket, $student, $session->id, $teacher->id);

        $delivered = (new RealtimeOutboxDispatcher($manager, new FrameCodec, new RealtimeServerEventEnvelopeFactory))->dispatch();

        $this->assertSame(1, $delivered);
        $this->assertDatabaseHas('realtime_outbox_messages', ['id' => $message->id, 'attempts' => 1]);
        $this->assertNotNull(RealtimeOutboxMessage::find($message->id)->delivered_at);
        stream_set_blocking($teacherSocket, false);
        $frame = (new FrameCodec)->decodeServerFrame((string) fread($teacherSocket, 65535));
        $this->assertSame(0x1, $frame['opcode']);
        $envelope = json_decode($frame['payload'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('server', $envelope['source']);
        $this->assertNull($envelope['sender_id']);
        $this->assertSame((string) $student->id, $envelope['recipient_id']);
        $this->assertSame(RealtimeEventTypes::DIRECT_CONNECTION_UNAVAILABLE, $envelope['type']);
        fclose($teacherSocket);
        fclose($studentSocket);
    }

    public function test_envelope_factory_rejects_a_recipient_outside_the_session(): void
    {
        [$session] = $this->sessionFixture();
        $outsider = User::factory()->student()->create();
        $message = RealtimeOutboxMessage::create([
            'id' => (string) Str::uuid(),
            'session_id' => $session->id,
            'recipient_id' => $outsider->id,
            'event_type' => RealtimeEventTypes::SESSION_STATE_CHANGED,
            'dedupe_key' => hash('sha256', 'foreign-recipient-test'),
            'payload' => ['state' => 'reconnecting'],
        ]);

        try {
            (new RealtimeServerEventEnvelopeFactory)->make($message);
            $this->fail('Expected a foreign recipient to be rejected.');
        } catch (RealtimeProtocolException $exception) {
            $this->assertSame('server_event_recipient_mismatch', $exception->codeName);
        }
    }

    public function test_pending_server_event_is_not_marked_delivered_when_recipient_is_offline(): void
    {
        [$session, $teacher, $student] = $this->sessionFixture();
        $message = RealtimeOutboxMessage::create([
            'id' => (string) Str::uuid(),
            'session_id' => $session->id,
            'recipient_id' => $student->id,
            'event_type' => RealtimeEventTypes::SESSION_STATE_CHANGED,
            'dedupe_key' => hash('sha256', 'offline-test'),
            'payload' => ['state' => 'reconnecting'],
        ]);
        $manager = new ConnectionManager;
        $socket = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
        $manager->add($socket[0], $teacher, $session->id, $student->id);

        $delivered = (new RealtimeOutboxDispatcher($manager, new FrameCodec, new RealtimeServerEventEnvelopeFactory))->dispatch();

        $this->assertSame(0, $delivered);
        $message->refresh();
        $this->assertNull($message->delivered_at);
        $this->assertSame(0, $message->attempts);
        fclose($socket[0]);
        fclose($socket[1]);
    }

    public function test_report_update_publishes_a_versioned_event_to_both_participants(): void
    {
        [$session, $teacher] = $this->sessionFixture();
        $report = SessionReport::create(['id' => (string) Str::uuid(), 'session_id' => $session->id, 'state' => 'draft', 'version' => 1]);

        app(SessionReportService::class)->update($teacher, $report, ['summary' => 'Updated summary']);

        $messages = RealtimeOutboxMessage::query()->where('event_type', RealtimeEventTypes::REPORT_UPDATED)->get();
        $this->assertCount(2, $messages);
        $this->assertEqualsCanonicalizing([$session->teacher_id, $session->student_id], $messages->pluck('recipient_id')->all());
        $this->assertSame((string) $report->id, $messages->first()->payload['report_id']);
        $this->assertSame(2, $messages->first()->payload['version']);
    }

    /** @return array{0:LiveSession,1:User,2:User} */
    private function sessionFixture(): array
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $halaqa = Halaqa::create(['id' => (string) Str::uuid(), 'teacher_id' => $teacher->id, 'name' => 'Realtime Halaqa', 'gender' => 'male', 'country' => 'Saudi Arabia', 'residence' => 'Riyadh', 'timezone' => 'Asia/Riyadh', 'max_students' => 5, 'status' => 'active']);
        $session = LiveSession::create(['id' => (string) Str::uuid(), 'halaqa_id' => $halaqa->id, 'teacher_id' => $teacher->id, 'student_id' => $student->id, 'task_type_id' => 1, 'state' => 'connected', 'requested_at' => now()->subHour(), 'connected_at' => now()->subMinutes(30), 'direct_p2p_only' => true, 'client_operation_id' => (string) Str::uuid()]);

        return [$session, $teacher, $student];
    }
}
