<?php

namespace App\Realtime\WebSocket;

use App\Exceptions\RealtimeProtocolException;
use App\Realtime\Channels\LiveSessionChannelAuthorizer;
use App\Realtime\Signaling\WebRtcSignalingService;
use Illuminate\Contracts\Auth\Authenticatable;
use Throwable;

class WebSocketServer
{
    /** @var array<int,array{socket:resource,user:Authenticatable,channel:array<string,mixed>,buffer:string}> */
    private array $clients = [];

    public function __construct(
        private readonly HandshakeService $handshake,
        private readonly LiveSessionChannelAuthorizer $authorizer,
        private readonly WebRtcSignalingService $signaling,
        private readonly FrameCodec $codec,
        private readonly ConnectionManager $connections,
    ) {}

    public function run(string $host, int $port): void
    {
        $server = stream_socket_server('tcp://'.$host.':'.$port, $errorNumber, $errorMessage, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        if ($server === false) {
            throw new \RuntimeException('Unable to start the internal WebSocket server: '.$errorMessage.' ('.$errorNumber.')');
        }
        stream_set_blocking($server, false);

        try {
            while (true) {
                $read = [$server];
                foreach ($this->clients as $client) {
                    $read[] = $client['socket'];
                }
                $write = null;
                $except = null;
                if (stream_select($read, $write, $except, 1) === false) {
                    continue;
                }
                foreach ($read as $socket) {
                    if ($socket === $server) {
                        $this->accept($server);

                        continue;
                    }
                    $this->readClient($socket);
                }
            }
        } finally {
            foreach (array_keys($this->clients) as $connectionId) {
                $this->closeClient($connectionId);
            }
            fclose($server);
        }
    }

    /** @param resource $server */
    private function accept($server): void
    {
        $client = @stream_socket_accept($server, 0);
        if ($client === false) {
            return;
        }
        try {
            stream_set_timeout($client, 3);
            $request = $this->readHandshake($client);
            $context = $this->handshake->validate($request);
            $query = parse_url($context['path'], PHP_URL_QUERY);
            parse_str(is_string($query) ? $query : '', $parameters);
            $channelName = isset($parameters['channel']) && is_string($parameters['channel']) ? $parameters['channel'] : '';
            if ($channelName === '') {
                throw new RealtimeProtocolException('missing_channel', 'The WebSocket handshake must identify a private session channel.');
            }
            $channel = $this->authorizer->authorize($context['user'], $channelName);
            stream_set_blocking($client, false);
            $connectionId = $this->connections->add($client, $context['user'], $channel['session_id'], $channel['recipient_id']);
            $this->clients[$connectionId] = ['socket' => $client, 'user' => $context['user'], 'channel' => $channel, 'buffer' => ''];
            $this->writeAll($client, $this->handshake->response($context['key']));
        } catch (Throwable $exception) {
            $this->reject($client, $exception);
        }
    }

    /** @param resource $client */
    private function readHandshake($client): string
    {
        $request = '';
        while (! str_contains($request, "\r\n\r\n") && strlen($request) <= 16384) {
            $chunk = fread($client, 4096);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $request .= $chunk;
        }
        if (! str_contains($request, "\r\n\r\n")) {
            throw new RealtimeProtocolException('incomplete_handshake', 'The WebSocket handshake is incomplete.');
        }

        return $request;
    }

    /** @param resource $socket */
    private function readClient($socket): void
    {
        $connectionId = (int) $socket;
        $chunk = fread($socket, 1024 * 1024);
        if ($chunk === false || ($chunk === '' && feof($socket))) {
            $this->closeClient($connectionId);

            return;
        }
        $this->clients[$connectionId]['buffer'] .= $chunk;
        try {
            while (($frame = $this->codec->decode($this->clients[$connectionId]['buffer'])) !== null) {
                $this->clients[$connectionId]['buffer'] = substr($this->clients[$connectionId]['buffer'], $frame['consumed']);
                $this->handleFrame($connectionId, $frame['opcode'], $frame['payload']);
                if (! isset($this->clients[$connectionId])) {
                    return;
                }
            }
        } catch (Throwable $exception) {
            $this->closeWithProtocolError($connectionId, $exception);
        }
    }

    private function handleFrame(int $connectionId, int $opcode, string $payload): void
    {
        $socket = $this->clients[$connectionId]['socket'];
        if ($opcode === 0x8) {
            $this->writeAll($socket, $this->codec->encodeClose());
            $this->closeClient($connectionId);

            return;
        }
        if ($opcode === 0x9) {
            $this->writeAll($socket, $this->codec->encodePong($payload));

            return;
        }
        if ($opcode !== 0x1) {
            throw new RealtimeProtocolException('unsupported_frame_opcode', 'Only text messages are accepted by the realtime server.');
        }
        $message = json_decode($payload, true);
        if (! is_array($message)) {
            throw new RealtimeProtocolException('invalid_json_message', 'Realtime messages must be JSON objects.');
        }
        $validated = $this->signaling->validate($this->clients[$connectionId]['user'], $message, $this->clients[$connectionId]['channel']);
        $encoded = json_encode($validated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->connections->sendToRecipient($socket, $encoded, $this->codec);
    }

    private function closeWithProtocolError(int $connectionId, Throwable $exception): void
    {
        if (! isset($this->clients[$connectionId])) {
            return;
        }
        $this->writeAll($this->clients[$connectionId]['socket'], $this->codec->encodeClose(1008, $exception instanceof RealtimeProtocolException ? $exception->getMessage() : 'Invalid realtime message.'));
        $this->closeClient($connectionId);
    }

    /** @param resource $client */
    private function reject($client, Throwable $exception): void
    {
        $status = $exception instanceof RealtimeProtocolException ? 400 : 500;
        $reason = $exception instanceof RealtimeProtocolException ? $exception->getMessage() : 'WebSocket handshake failed.';
        @fwrite($client, 'HTTP/1.1 '.$status.' Bad Request\r\nConnection: close\r\nContent-Length: 0\r\n\r\n');
        @fclose($client);
        unset($reason);
    }

    private function closeClient(int $connectionId): void
    {
        $client = $this->clients[$connectionId] ?? null;
        if ($client === null) {
            return;
        }
        $this->connections->remove($connectionId);
        fclose($client['socket']);
        unset($this->clients[$connectionId]);
    }

    /** @param resource $socket */
    private function writeAll($socket, string $data): void
    {
        $offset = 0;
        while ($offset < strlen($data)) {
            $written = fwrite($socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new \RuntimeException('Unable to write to the WebSocket connection.');
            }
            $offset += $written;
        }
    }
}
