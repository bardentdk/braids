<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton lazy — instancié UNIQUEMENT quand BrevoService est injecté
        // Aucun crash au boot même sans clé API configurée
        $this->app->singleton(\App\Services\BrevoService::class, function ($app) {
            return new \App\Services\BrevoService();
        });
        $this->app->singleton(\App\Services\StripeService::class, fn() => new \App\Services\StripeService());
        $this->app->singleton(\App\Services\PaypalService::class, fn() => new \App\Services\PaypalService());
        $this->app->alias(\App\Services\BrevoService::class, 'brevo');
        $this->app->singleton(\App\Services\GroqService::class, fn() => new \App\Services\GroqService());
    }

    public function boot(): void
    {
        // HTTPS forcé en production (Laravel Forge)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Locale française
        \Carbon\Carbon::setLocale('fr');
        setlocale(LC_TIME, 'fr_FR.UTF-8');
    }
}