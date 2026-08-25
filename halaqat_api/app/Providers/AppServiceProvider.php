<?php

namespace App\Providers;

use App\Console\Commands\Realtime\RunWebSocketServerCommand;
use App\Events\LiveSession\LiveSessionRealtimeEvent;
use App\Events\Notifications\SessionEnded;
use App\Events\Notifications\SessionReportApproved;
use App\Events\Notifications\SessionScheduled;
use App\Events\Reports\SessionReportRealtimeUpdated;
use App\Listeners\LiveSession\PublishLiveSessionRealtimeEvent;
use App\Listeners\Notifications\CreateSessionEndedNotifications;
use App\Listeners\Notifications\CreateSessionReportReadyNotification;
use App\Listeners\Notifications\CreateSessionScheduledNotification;
use App\Listeners\Reports\PublishSessionReportRealtimeUpdated;
use App\Models\FollowUpItem;
use App\Models\LiveSession;
use App\Models\Mistake;
use App\Models\Notification;
use App\Models\SessionReport;
use App\Models\SessionTask;
use App\Models\User;
use App\Policies\FollowUpItemPolicy;
use App\Policies\LiveSessionPolicy;
use App\Policies\MistakePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\SessionReportPolicy;
use App\Policies\SessionTaskPolicy;
use App\Policies\StudentLearningPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([RunWebSocketServerCommand::class]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(LiveSessionRealtimeEvent::class, PublishLiveSessionRealtimeEvent::class);
        Event::listen(SessionReportRealtimeUpdated::class, PublishSessionReportRealtimeUpdated::class);
        Event::listen(SessionScheduled::class, CreateSessionScheduledNotification::class);
        Event::listen(SessionEnded::class, CreateSessionEndedNotifications::class);
        Event::listen(SessionReportApproved::class, CreateSessionReportReadyNotification::class);

        Gate::policy(User::class, StudentLearningPolicy::class);
        Gate::policy(FollowUpItem::class, FollowUpItemPolicy::class);
        Gate::policy(LiveSession::class, LiveSessionPolicy::class);
        Gate::policy(SessionReport::class, SessionReportPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(Mistake::class, MistakePolicy::class);
        Gate::policy(SessionTask::class, SessionTaskPolicy::class);
        JsonResource::withoutWrapping();
    }
}
