<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Paginator::useBootstrapFive();

        // Admin memegang semua wewenang.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
        Gate::define('admin', fn (User $user) => $user->isAdmin());
        Gate::define('pengurus', fn (User $user) => $user->isBoard());

        // Wewenang per jabatan dari config/rt.php
        foreach (config('rt.capabilities') as $ability => $positions) {
            Gate::define($ability, fn (User $user) => $user->role === 'pengurus' && $user->holds(...$positions));
        }

        // Batasi percobaan masuk: 5 kali per menit per username + IP.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('username')).'|'.$request->ip()));
    }
}
