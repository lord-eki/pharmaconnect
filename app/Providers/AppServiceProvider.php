<?php

namespace App\Providers;

use App\Http\Responses\CustomLoginResponse;
use Illuminate\Support\ServiceProvider;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
 
     public function register(): void
    {
        $this->app->bind(LoginResponse::class, CustomLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
