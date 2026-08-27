<?php

namespace App\Realtime\WebSocket;

use App\Models\User;
use RuntimeException;
use Throwable;

class ConnectionManager
{
    /** @var array<int,array{socket:resource,user_id:string,session_id:string,recipient_id:string}> */
    private array $connections = [];

    /** @param resource $socket */
    public function add($socket, User $user, string $sessionId, string $recipientId): int
    {
        if (! is_resource($socket)) {
            throw new RuntimeException('A valid socket resource is required.');
        }
        $id = (int) $socket;
        $this->connections[$id] = ['socket' => $socket, 'user_id' => (string) $user->id, 'session_id' => $sessionId, 'recipient_id' => $recipientId];

        return $id;
    }

    public function remove(int $connectionId): void
    {
        unset($this->connections[$connectionId]);
    }

    /** @param resource $socket */
    public function sendToRecipient($socket, string $payload, FrameCodec $codec): bool
    {
        $connection = $this->connections[(int) $socket] ?? null;
        if ($connection === null) {
            return false;
        }

        return $this->sendToParticipant($connection['session_id'], $connection['recipient_id'], $payload, $codec);
    }

    public function hasParticipant(string $sessionId, string $userId): bool
    {
        foreach ($this->connections as $connection) {
            if ($connection['session_id'] === $sessionId && $connection['user_id'] === $userId) {
                return true;
            }
        }

        return false;
    }

    public function sendToParticipant(string $sessionId, string $userId, string $payload, FrameCodec $codec): bool
    {
        foreach ($this->connections as $connectionId => $candidate) {
            if ($candidate['session_id'] !== $sessionId || $candidate['user_id'] !== $userId) {
                continue;
            }
            try {
                $this->writeAll($candidate['socket'], $codec->encodeText($payload));
            } catch (Throwable) {
                unset($this->connections[$connectionId]);

                return false;
            }

            return true;
        }

        return false;
    }

    /** @return array<int,array{socket:resource,user_id:string,session_id:string,recipient_id:string}> */
    public function all(): array
    {
        return $this->connections;
    }

    /** @param resource $socket */
    private function writeAll($socket, string $data): void
    {
        $offset = 0;
        $length = strlen($data);
        while ($offset < $length) {
            $written = fwrite($socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Unable to write a WebSocket frame.');
            }
            $offset += $written;
        }
    }
}
