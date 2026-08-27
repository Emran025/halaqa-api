<?php

namespace App\Console\Commands\Realtime;

use App\Realtime\WebSocket\WebSocketServer;
use Illuminate\Console\Command;
use Throwable;

class RunWebSocketServerCommand extends Command
{
    protected $signature = 'realtime:websocket {--host=127.0.0.1} {--port=8081}';

    protected $description = 'Run the internal Laravel WebSocket server.';

    public function __construct(private readonly WebSocketServer $server)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $host = (string) $this->option('host');
        $port = (int) $this->option('port');
        if ($host === '' || $port < 1 || $port > 65535) {
            $this->error('A valid host and port are required.');

            return self::INVALID;
        }
        $this->info('Starting the internal Laravel WebSocket server on '.$host.':'.$port.'.');
        try {
            $this->server->run($host, $port);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
