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
        // #region agent log
        Gate::define('teacher', function ($user) {
            $result = $user->isTeacher();
            $roleSlugs = $user->roles->pluck('slug')->toArray();
            file_put_contents('/var/www/html/.cursor/debug-f4ed0e.log', json_encode(['sessionId'=>'f4ed0e','hypothesisId'=>'H5','location'=>'AppServiceProvider.php:gate-teacher','message'=>'Teacher gate evaluated','data'=>['user_id'=>$user->id,'user_name'=>$user->name,'role_slugs'=>$roleSlugs,'roles_count'=>count($roleSlugs),'isTeacher_result'=>$result,'has_trainer'=>in_array('trainer',$roleSlugs)],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND);
            return $result;
        });
        // #endregion
        Gate::define('manager', fn ($user) => $user->isManager());

        Event::listen(Login::class, AssignCareerPathOnLogin::class);
    }
}
