<?php

namespace App\Realtime\WebSocket;

use App\Exceptions\RealtimeProtocolException;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;

class HandshakeService
{
    /** @return array{path:string,key:string,headers:array<string,string>,user:Authenticatable} */
    public function validate(string $request): array
    {
        [$requestLine, $headers] = $this->parse($request);
        $parts = explode(' ', $requestLine, 3);
        if (count($parts) !== 3 || $parts[0] !== 'GET' || parse_url($parts[1], PHP_URL_PATH) !== '/ws') {
            throw new RealtimeProtocolException('invalid_handshake_request', 'The WebSocket endpoint requires a GET request to /ws.');
        }
        if (strtolower($headers['upgrade'] ?? '') !== 'websocket' || ! $this->hasToken($headers['connection'] ?? '', 'upgrade')) {
            throw new RealtimeProtocolException('invalid_upgrade_headers', 'A WebSocket Upgrade request is required.');
        }
        if (($headers['sec-websocket-version'] ?? '') !== '13') {
            throw new RealtimeProtocolException('unsupported_websocket_version', 'Only WebSocket version 13 is supported.');
        }
        $key = $headers['sec-websocket-key'] ?? '';
        if ($key === '' || base64_decode($key, true) === false || strlen((string) base64_decode($key, true)) !== 16) {
            throw new RealtimeProtocolException('invalid_websocket_key', 'A valid Sec-WebSocket-Key is required.');
        }
        $user = $this->authenticate($headers['authorization'] ?? null);

        return ['path' => $parts[1], 'key' => $key, 'headers' => $headers, 'user' => $user];
    }

    public function response(string $key): string
    {
        $accept = base64_encode(sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        return "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: {$accept}\r\n\r\n";
    }

    /** @return array{0:string,1:array<string,string>} */
    private function parse(string $request): array
    {
        $headerBlock = strstr($request, "\r\n\r\n", true);
        if ($headerBlock === false) {
            throw new RealtimeProtocolException('incomplete_handshake', 'The WebSocket handshake is incomplete.');
        }
        $lines = explode("\r\n", $headerBlock);
        $requestLine = array_shift($lines);
        $headers = [];
        foreach ($lines as $line) {
            $separator = strpos($line, ':');
            if ($separator === false) {
                throw new RealtimeProtocolException('invalid_handshake_header', 'The WebSocket handshake contains an invalid header.');
            }
            $headers[strtolower(trim(substr($line, 0, $separator)))] = trim(substr($line, $separator + 1));
        }

        return [$requestLine, $headers];
    }

    private function authenticate(?string $authorization): Authenticatable
    {
        if (! is_string($authorization) || ! preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            throw new RealtimeProtocolException('missing_realtime_authentication', 'A Bearer token is required for the WebSocket handshake.');
        }
        $accessToken = PersonalAccessToken::findToken($matches[1]);
        $user = $accessToken?->tokenable;
        if (! $user instanceof Authenticatable) {
            throw new RealtimeProtocolException('invalid_realtime_authentication', 'The Bearer token is invalid.');
        }

        return $user;
    }

    private function hasToken(string $header, string $expected): bool
    {
        return in_array(strtolower(trim($expected)), array_map('trim', array_map('strtolower', explode(',', $header))), true);
    }
}
