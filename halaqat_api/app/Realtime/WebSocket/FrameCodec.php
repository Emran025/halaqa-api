<?php

namespace App\Realtime\WebSocket;

use App\Exceptions\RealtimeProtocolException;

class FrameCodec
{
    private const MAX_PAYLOAD_BYTES = 1024 * 1024;

    public function encodeText(string $payload): string
    {
        return $this->encodeFrame(0x1, $payload);
    }

    public function encodeClose(int $code = 1000, string $reason = ''): string
    {
        $reason = substr($reason, 0, 123);

        return $this->encodeFrame(0x8, pack('n', $code).$reason);
    }

    public function encodePong(string $payload = ''): string
    {
        return $this->encodeFrame(0xA, substr($payload, 0, 125));
    }

    /** @return array{opcode:int,payload:string,consumed:int}|null */
    public function decode(string $buffer): ?array
    {
        return $this->decodeFrame($buffer, true);
    }

    /** @return array{opcode:int,payload:string,consumed:int}|null */
    public function decodeServerFrame(string $buffer): ?array
    {
        return $this->decodeFrame($buffer, false);
    }

    /** @return array{opcode:int,payload:string,consumed:int}|null */
    private function decodeFrame(string $buffer, bool $requireMask): ?array
    {
        $length = strlen($buffer);
        if ($length < 2) {
            return null;
        }
        $first = ord($buffer[0]);
        $second = ord($buffer[1]);
        if (($first & 0x70) !== 0 || ($first & 0x80) === 0) {
            throw new RealtimeProtocolException('invalid_frame_flags', 'Only final WebSocket frames without extensions are supported.');
        }
        $opcode = $first & 0x0F;
        $masked = ($second & 0x80) !== 0;
        if ($requireMask && ! $masked) {
            throw new RealtimeProtocolException('unmasked_client_frame', 'Client WebSocket frames must be masked.');
        }
        if (! $requireMask && $masked) {
            throw new RealtimeProtocolException('masked_server_frame', 'Server WebSocket frames must not be masked.');
        }
        $payloadLength = $second & 0x7F;
        $offset = 2;
        if ($payloadLength === 126) {
            if ($length < 4) {
                return null;
            }
            $payloadLength = unpack('nlength', substr($buffer, 2, 2))['length'];
            $offset = 4;
        } elseif ($payloadLength === 127) {
            if ($length < 10) {
                return null;
            }
            $parts = unpack('Nhigh/Nlow', substr($buffer, 2, 8));
            if ($parts['high'] > 0) {
                throw new RealtimeProtocolException('frame_too_large', 'WebSocket frames larger than the supported limit are rejected.');
            }
            $payloadLength = $parts['low'];
            $offset = 10;
        }
        if ($payloadLength > self::MAX_PAYLOAD_BYTES) {
            throw new RealtimeProtocolException('frame_too_large', 'WebSocket frame payload exceeds the supported limit.');
        }
        $isControl = $opcode >= 0x8;
        if ($isControl && ($payloadLength > 125 || ($first & 0x80) === 0)) {
            throw new RealtimeProtocolException('invalid_control_frame', 'Control frames must be final and at most 125 bytes.');
        }
        $maskLength = $masked ? 4 : 0;
        if ($length < $offset + $maskLength + $payloadLength) {
            return null;
        }
        $mask = $masked ? substr($buffer, $offset, 4) : '';
        $payloadOffset = $offset + $maskLength;
        $payload = substr($buffer, $payloadOffset, $payloadLength);
        if ($masked) {
            for ($index = 0; $index < $payloadLength; $index++) {
                $payload[$index] = $payload[$index] ^ $mask[$index % 4];
            }
        }
        if (! in_array($opcode, [0x1, 0x8, 0x9, 0xA], true)) {
            throw new RealtimeProtocolException('unsupported_frame_opcode', 'Only text and WebSocket control frames are supported.');
        }

        return ['opcode' => $opcode, 'payload' => $payload, 'consumed' => $payloadOffset + $payloadLength];
    }

    private function encodeFrame(int $opcode, string $payload): string
    {
        $length = strlen($payload);
        if ($length > self::MAX_PAYLOAD_BYTES) {
            throw new RealtimeProtocolException('frame_too_large', 'WebSocket frame payload exceeds the supported limit.');
        }
        if ($length < 126) {
            return pack('CC', 0x80 | $opcode, $length).$payload;
        }
        if ($length <= 0xFFFF) {
            return pack('CCn', 0x80 | $opcode, 126, $length).$payload;
        }

        return pack('CCNN', 0x80 | $opcode, 127, 0, $length).$payload;
    }
}
