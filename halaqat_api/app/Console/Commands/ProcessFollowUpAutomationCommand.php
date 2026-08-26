<?php

namespace App\Console\Commands;

use App\Services\Progress\FollowUpAutomationService;
use Illuminate\Console\Command;

class ProcessFollowUpAutomationCommand extends Command
{
    protected $signature = 'follow-up:process';

    protected $description = 'Generate upcoming follow-up items and notify users about due items.';

    public function handle(FollowUpAutomationService $service): int
    {
        $created = $service->process();
        $this->info("Follow-up automation processed; created {$created} item(s).");

        return self::SUCCESS;
    }
}
