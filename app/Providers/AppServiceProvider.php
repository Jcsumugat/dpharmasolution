<?php

namespace App\Providers;

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
public function boot()
{
    // Debug: Catch auth guard calls
    \Auth::extend('debug_guard', function ($app, $name, $config) {
        if ($name === 'admin') {
            \Log::error('Auth guard [admin] was requested from:', [
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
            ]);
        }
        return null;
    });
}
}
