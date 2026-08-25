<?php

namespace App\Providers;

use App\Models\LiveSession;
use App\Models\Mistake;
use App\Models\SessionTask;
use App\Models\User;
use App\Policies\LiveSessionPolicy;
use App\Policies\MistakePolicy;
use App\Policies\SessionTaskPolicy;
use App\Policies\StudentLearningPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, StudentLearningPolicy::class);
        Gate::policy(LiveSession::class, LiveSessionPolicy::class);
        Gate::policy(Mistake::class, MistakePolicy::class);
        Gate::policy(SessionTask::class, SessionTaskPolicy::class);
        JsonResource::withoutWrapping();
    }
}
