<?php

namespace App\Providers;

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
        Gate::define('manage-inventory', fn ($user): bool => $user->role === 'administrator');
        Gate::define('record-payments', fn ($user): bool => in_array($user->role, ['administrator', 'receptionist'], true));
    }
}
