<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AuthService;
use App\Services\FormService;
use App\Services\FeedbackService;
use App\Services\GoogleOAuthService;
use App\Services\GrokService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService();
        });
        $this->app->singleton(FormService::class, function ($app) {
            return new FormService();
        });
        $this->app->singleton(FeedbackService::class, function ($app) {
            return new FeedbackService();
        });
        $this->app->singleton(GoogleOAuthService::class, function ($app) {
            return new GoogleOAuthService();
        });
        $this->app->singleton(GrokService::class, function ($app) {
            return new GrokService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
