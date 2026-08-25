<?php

namespace Tests\Unit;

use App\Exceptions\RealtimeProtocolException;
use App\Models\User;
use App\Realtime\Signaling\WebRtcSignalingService;
use App\Realtime\WebSocket\ConnectionManager;
use App\Realtime\WebSocket\FrameCodec;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class RealtimeProtocolTest extends TestCase
{
    public function test_frame_codec_decodes_masked_text_and_reports_consumed_bytes(): void
    {
        $codec = new FrameCodec;
        $payload = '{"type":"ping"}';
        $frame = $this->maskedFrame($payload);
        $decoded = $codec->decode($frame.$frame);

        $this->assertSame(0x1, $decoded['opcode']);
        $this->assertSame($payload, $decoded['payload']);
        $this->assertSame(strlen($frame), $decoded['consumed']);
    }

    public function test_frame_codec_rejects_unmasked_client_frames(): void
    {
        $this->expectException(RealtimeProtocolException::class);
        $this->expectExceptionMessage('must be masked');

        (new FrameCodec)->decode(chr(0x81).chr(3).'abc');
    }

    public function test_signaling_rewrites_sender_role_and_accepts_only_host_ice(): void
    {
        $sender = new User(['id' => (string) Str::uuid(), 'role' => 'teacher']);
        $recipientId = (string) Str::uuid();
        $sessionId = (string) Str::uuid();
        $message = $this->message($sender->id, $recipientId, $sessionId, 'webrtc.ice_candidate', ['candidate' => 'candidate:1 1 udp 1 192.0.2.1 5000 typ host', 'sdp_mid' => '0', 'sdp_m_line_index' => 0, 'username_fragment' => null]);

        $validated = (new WebRtcSignalingService)->validate($sender, $message, ['session_id' => $sessionId, 'recipient_id' => $recipientId]);

        $this->assertSame('teacher', $validated['sender_role']);
        $this->assertSame('webrtc.ice_candidate', $validated['type']);
        $this->assertNotSame($message['occurred_at'] ?? null, $validated['occurred_at']);
    }

    public function test_signaling_rejects_non_host_ice_and_spoofed_recipient(): void
    {
        $sender = new User(['id' => (string) Str::uuid(), 'role' => 'student']);
        $sessionId = (string) Str::uuid();
        $message = $this->message($sender->id, (string) Str::uuid(), $sessionId, 'webrtc.ice_candidate', ['candidate' => 'candidate:1 1 udp 1 192.0.2.1 5000 typ srflx', 'sdp_mid' => '0', 'sdp_m_line_index' => 0, 'username_fragment' => null]);

        try {
            (new WebRtcSignalingService)->validate($sender, $message, ['session_id' => $sessionId, 'recipient_id' => $message['recipient_id']]);
            $this->fail('Expected non-host candidate rejection.');
        } catch (RealtimeProtocolException $exception) {
            $this->assertSame('non_host_ice_candidate', $exception->codeName);
        }

        $message['payload']['candidate'] = 'candidate:1 1 udp 1 192.0.2.1 5000 typ host';
        $message['recipient_id'] = (string) Str::uuid();
        $this->expectException(RealtimeProtocolException::class);
        (new WebRtcSignalingService)->validate($sender, $message, ['session_id' => $sessionId, 'recipient_id' => (string) Str::uuid()]);
    }

    public function test_connection_manager_sends_only_to_the_other_session_participant(): void
    {
        [$left, $right] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
        $manager = new ConnectionManager;
        $leftUser = new User(['id' => (string) Str::uuid(), 'role' => 'teacher']);
        $rightUser = new User(['id' => (string) Str::uuid(), 'role' => 'student']);
        $sessionId = (string) Str::uuid();
        $manager->add($left, $leftUser, $sessionId, $rightUser->id);
        $manager->add($right, $rightUser, $sessionId, $leftUser->id);

        $manager->sendToRecipient($left, 'hello', new FrameCodec);
        stream_set_blocking($left, false);
        stream_set_blocking($right, false);

        $this->assertNotSame('', (string) fread($left, 20));
        $this->assertSame('', (string) fread($right, 20));
        fclose($left);
        fclose($right);
    }

    /** @return array<string,mixed> */
    private function message(string $senderId, string $recipientId, string $sessionId, string $type, array $payload): array
    {
        return ['message_id' => (string) Str::uuid(), 'session_id' => $sessionId, 'sender_id' => $senderId, 'recipient_id' => $recipientId, 'sender_role' => 'student', 'type' => $type, 'occurred_at' => now()->toISOString(), 'client_operation_id' => null, 'payload' => $payload];
    }

    private function maskedFrame(string $payload): string
    {
        $mask = 'abcd';
        $length = strlen($payload);

        return chr(0x81).chr(0x80 | $length).$mask.$this->xorMask($payload, $mask);
    }

    private function xorMask(string $payload, string $mask): string
    {
        for ($index = 0; $index < strlen($payload); $index++) {
            $payload[$index] = $payload[$index] ^ $mask[$index % 4];
        }

        return $payload;
    }
}
