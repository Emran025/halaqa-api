<?php

namespace App\Realtime\Signaling;

use App\Exceptions\RealtimeProtocolException;
use App\Models\User;
use Illuminate\Support\Str;

class WebRtcSignalingService
{
    /** @param array<string,mixed> $envelope @param array<string,mixed> $channel */
    public function validate(User $sender, array $envelope, array $channel): array
    {
        $required = ['message_id', 'session_id', 'sender_id', 'recipient_id', 'sender_role', 'type', 'payload'];
        foreach ($required as $field) {
            if (! array_key_exists($field, $envelope)) {
                throw new RealtimeProtocolException('invalid_message_shape', 'The realtime message is missing a required field.');
            }
        }
        foreach (['message_id', 'session_id', 'sender_id', 'recipient_id'] as $field) {
            if (! is_string($envelope[$field]) || ! Str::isUuid($envelope[$field])) {
                throw new RealtimeProtocolException('invalid_message_id', 'Realtime message identifiers must be UUIDs.');
            }
        }
        if ($envelope['session_id'] !== $channel['session_id'] || $envelope['sender_id'] !== (string) $sender->id || $envelope['recipient_id'] !== $channel['recipient_id'] || $envelope['recipient_id'] === $envelope['sender_id']) {
            throw new RealtimeProtocolException('message_participant_mismatch', 'The message sender and recipient must be the two participants of the channel.');
        }
        $clientTypes = ['webrtc.offer', 'webrtc.answer', 'webrtc.ice_candidate', 'webrtc.renegotiate', 'mushaf.page_changed', 'mushaf.ayah_selected', 'mistake.created', 'mistake.updated', 'mistake.deleted', 'guidance.request_repeat', 'task.changed'];
        if (! is_string($envelope['type']) || ! in_array($envelope['type'], $clientTypes, true)) {
            throw new RealtimeProtocolException('unsupported_signal_type', 'This realtime message type cannot be sent by a client.');
        }
        if (in_array($envelope['type'], ['guidance.request_repeat', 'task.changed'], true) && ! $sender->isTeacher()) {
            throw new RealtimeProtocolException('message_source_forbidden', 'This realtime message type is restricted to the teacher.');
        }
        if (isset($envelope['client_operation_id']) && $envelope['client_operation_id'] !== null && (! is_string($envelope['client_operation_id']) || ! Str::isUuid($envelope['client_operation_id']))) {
            throw new RealtimeProtocolException('invalid_client_operation_id', 'client_operation_id must be a UUID or null.');
        }

        $payload = $envelope['payload'];
        if (! is_array($payload)) {
            throw new RealtimeProtocolException('invalid_signal_payload', 'The signal payload must be an object.');
        }
        $envelope['sender_role'] = $sender->isTeacher() ? 'teacher' : 'student';
        $envelope['occurred_at'] = now()->toISOString();
        $envelope['payload'] = $this->validatePayload($envelope['type'], $payload);

        return $envelope;
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function validatePayload(string $type, array $payload): array
    {
        return match ($type) {
            'webrtc.offer', 'webrtc.answer' => $this->validateDescription($type, $payload),
            'webrtc.ice_candidate' => $this->validateIceCandidate($payload),
            'webrtc.renegotiate' => $this->validateRenegotiate($payload),
            'mushaf.page_changed' => $this->validatePageChanged($payload),
            'mushaf.ayah_selected' => $this->validateAyahSelected($payload),
            'mistake.created' => $this->validateMistakeCreated($payload),
            'mistake.updated' => $this->validateMistakeUpdated($payload),
            'mistake.deleted' => $this->validateMistakeDeleted($payload),
            'guidance.request_repeat' => $this->validateGuidance($payload),
            'task.changed' => $this->validateTaskChanged($payload),
            default => throw new RealtimeProtocolException('unsupported_signal_type', 'This realtime message type is not supported.'),
        };
    }

    /** @param array<string,mixed> $payload @return array{type:string,sdp:string} */
    private function validateDescription(string $type, array $payload): array
    {
        if (array_diff(array_keys($payload), ['type', 'sdp']) !== [] || ($payload['type'] ?? null) !== substr($type, 7) || ! is_string($payload['sdp'] ?? null) || trim($payload['sdp']) === '' || strlen($payload['sdp']) > 200000) {
            throw new RealtimeProtocolException('invalid_signal_payload', 'The WebRTC description payload is invalid.');
        }

        return ['type' => $payload['type'], 'sdp' => $payload['sdp']];
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function validateIceCandidate(array $payload): array
    {
        $allowed = ['candidate', 'sdp_mid', 'sdp_m_line_index', 'username_fragment'];
        if (array_diff(array_keys($payload), $allowed) !== [] || ! is_string($payload['candidate'] ?? null) || trim($payload['candidate']) === '' || strlen($payload['candidate']) > 10000 || ! array_key_exists('sdp_mid', $payload) || ($payload['sdp_mid'] !== null && ! is_string($payload['sdp_mid'])) || ! is_int($payload['sdp_m_line_index']) || $payload['sdp_m_line_index'] < 0 || $payload['sdp_m_line_index'] > 100 || ! array_key_exists('username_fragment', $payload) || ($payload['username_fragment'] !== null && ! is_string($payload['username_fragment']))) {
            throw new RealtimeProtocolException('invalid_ice_candidate', 'The ICE candidate payload is invalid.');
        }
        if (! preg_match('/\btyp\s+([a-z]+)/i', $payload['candidate'], $matches) || strtolower($matches[1]) !== 'host') {
            throw new RealtimeProtocolException('non_host_ice_candidate', 'Only host ICE candidates are accepted.');
        }

        return ['candidate' => $payload['candidate'], 'sdp_mid' => $payload['sdp_mid'], 'sdp_m_line_index' => $payload['sdp_m_line_index'], 'username_fragment' => $payload['username_fragment']];
    }

    /** @param array<string,mixed> $payload @return array{edition_id:int,page_number:int} */
    private function validatePageChanged(array $payload): array
    {
        if (array_diff(array_keys($payload), ['edition_id', 'page_number']) !== [] || ! is_int($payload['edition_id'] ?? null) || $payload['edition_id'] < 1 || ! is_int($payload['page_number'] ?? null) || $payload['page_number'] < 1 || $payload['page_number'] > 604) {
            throw new RealtimeProtocolException('invalid_mushaf_message', 'The Mushaf page payload is invalid.');
        }

        return ['edition_id' => $payload['edition_id'], 'page_number' => $payload['page_number']];
    }

    /** @param array<string,mixed> $payload @return array{edition_id:int,ayah_id:int,page_number:int} */
    private function validateAyahSelected(array $payload): array
    {
        if (array_diff(array_keys($payload), ['edition_id', 'ayah_id', 'page_number']) !== [] || ! is_int($payload['edition_id'] ?? null) || $payload['edition_id'] < 1 || ! is_int($payload['ayah_id'] ?? null) || $payload['ayah_id'] < 1 || $payload['ayah_id'] > 6236 || ! is_int($payload['page_number'] ?? null) || $payload['page_number'] < 1 || $payload['page_number'] > 604) {
            throw new RealtimeProtocolException('invalid_mushaf_message', 'The Mushaf ayah payload is invalid.');
        }

        return ['edition_id' => $payload['edition_id'], 'ayah_id' => $payload['ayah_id'], 'page_number' => $payload['page_number']];
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function validateMistakeCreated(array $payload): array
    {
        $this->assertMistakeType($payload);
        if (array_diff(array_keys($payload), ['task_id', 'edition_id', 'ayah_id', 'page_number', 'word_index', 'mistake_type', 'note']) !== [] || ! Str::isUuid((string) ($payload['task_id'] ?? '')) || ! is_int($payload['edition_id'] ?? null) || $payload['edition_id'] < 1 || ! is_int($payload['ayah_id'] ?? null) || $payload['ayah_id'] < 1 || $payload['ayah_id'] > 6236 || ! is_int($payload['page_number'] ?? null) || $payload['page_number'] < 1 || $payload['page_number'] > 604 || ! is_int($payload['word_index'] ?? null) || $payload['word_index'] < 1 || ! array_key_exists('note', $payload) || ($payload['note'] !== null && (! is_string($payload['note']) || strlen($payload['note']) > 1000))) {
            throw new RealtimeProtocolException('invalid_mistake_message', 'The created mistake payload is invalid.');
        }

        return $payload;
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function validateMistakeUpdated(array $payload): array
    {
        $this->assertMistakeType($payload);
        if (array_diff(array_keys($payload), ['task_id', 'mistake_id', 'mistake_type', 'note']) !== [] || ! Str::isUuid((string) ($payload['task_id'] ?? '')) || ! Str::isUuid((string) ($payload['mistake_id'] ?? '')) || ! array_key_exists('note', $payload) || ($payload['note'] !== null && (! is_string($payload['note']) || strlen($payload['note']) > 1000))) {
            throw new RealtimeProtocolException('invalid_mistake_message', 'The updated mistake payload is invalid.');
        }

        return $payload;
    }

    /** @param array<string,mixed> $payload @return array{task_id:string,mistake_id:string} */
    private function validateMistakeDeleted(array $payload): array
    {
        if (array_diff(array_keys($payload), ['task_id', 'mistake_id']) !== [] || ! Str::isUuid((string) ($payload['task_id'] ?? '')) || ! Str::isUuid((string) ($payload['mistake_id'] ?? ''))) {
            throw new RealtimeProtocolException('invalid_mistake_message', 'The deleted mistake payload is invalid.');
        }

        return ['task_id' => $payload['task_id'], 'mistake_id' => $payload['mistake_id']];
    }

    /** @param array<string,mixed> $payload @return array{task_id:string,ayah_id:int|null,reason:string|null} */
    private function validateGuidance(array $payload): array
    {
        if (array_diff(array_keys($payload), ['task_id', 'ayah_id', 'reason']) !== [] || ! array_key_exists('ayah_id', $payload) || ! array_key_exists('reason', $payload) || ! Str::isUuid((string) ($payload['task_id'] ?? '')) || ($payload['ayah_id'] !== null && (! is_int($payload['ayah_id']) || $payload['ayah_id'] < 1 || $payload['ayah_id'] > 6236)) || (($payload['reason'] ?? null) !== null && (! is_string($payload['reason']) || strlen($payload['reason']) > 1000))) {
            throw new RealtimeProtocolException('invalid_guidance_message', 'The guidance payload is invalid.');
        }

        return ['task_id' => $payload['task_id'], 'ayah_id' => $payload['ayah_id'] ?? null, 'reason' => $payload['reason'] ?? null];
    }

    /** @param array<string,mixed> $payload @return array{task_id:string,state:string,current_page:int|null,current_ayah_id:int|null} */
    private function validateTaskChanged(array $payload): array
    {
        if (array_diff(array_keys($payload), ['task_id', 'state', 'current_page', 'current_ayah_id']) !== [] || ! array_key_exists('current_page', $payload) || ! array_key_exists('current_ayah_id', $payload) || ! Str::isUuid((string) ($payload['task_id'] ?? '')) || ! is_string($payload['state'] ?? null) || ! in_array($payload['state'], ['draft', 'in_progress', 'completed', 'skipped', 'cancelled'], true) || (($payload['current_page'] ?? null) !== null && (! is_int($payload['current_page']) || $payload['current_page'] < 1 || $payload['current_page'] > 604)) || (($payload['current_ayah_id'] ?? null) !== null && (! is_int($payload['current_ayah_id']) || $payload['current_ayah_id'] < 1 || $payload['current_ayah_id'] > 6236))) {
            throw new RealtimeProtocolException('invalid_task_message', 'The task payload is invalid.');
        }

        return ['task_id' => $payload['task_id'], 'state' => $payload['state'], 'current_page' => $payload['current_page'] ?? null, 'current_ayah_id' => $payload['current_ayah_id'] ?? null];
    }

    /** @param array<string,mixed> $payload */
    private function assertMistakeType(array $payload): void
    {
        if (! is_string($payload['mistake_type'] ?? null) || ! in_array($payload['mistake_type'], ['none', 'memory', 'grammar', 'pronunciation', 'timing'], true)) {
            throw new RealtimeProtocolException('invalid_mistake_message', 'The mistake type is invalid.');
        }
    }

    /** @param array<string,mixed> $payload @return array{reason:string,attempt:int} */
    private function validateRenegotiate(array $payload): array
    {
        if (array_diff(array_keys($payload), ['reason', 'attempt']) !== [] || ! is_string($payload['reason'] ?? null) || trim($payload['reason']) === '' || strlen($payload['reason']) > 500 || ! is_int($payload['attempt']) || $payload['attempt'] < 1 || $payload['attempt'] > 10) {
            throw new RealtimeProtocolException('invalid_renegotiation', 'The renegotiation payload is invalid.');
        }

        return ['reason' => $payload['reason'], 'attempt' => $payload['attempt']];
    }
}
