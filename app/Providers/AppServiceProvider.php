<?php

namespace App\Providers;

use App\Listeners\AssignCareerPathOnLogin;
use App\Services\AsanaService;
use App\Services\GoogleCalendarService;
use App\Services\PersonioService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleCalendarService::class);
        $this->app->singleton(AsanaService::class);
        $this->app->singleton(PersonioService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') || str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        Gate::define('admin', fn ($user) => $user->isAdmin());
        Gate::define('manager', fn ($user) => $user->isManager());
        Gate::define('schulungsmanager', fn ($user) => $user->isSchulungsmanager());
        Gate::define('trainer', fn ($user) => $user->isTrainer());

        Event::listen(Login::class, AssignCareerPathOnLogin::class);
    }
}
