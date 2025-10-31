<?php

namespace App\Providers;

use App\Http\Responses\CustomLoginResponse;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Model;
use App\Observers\PrescriptionObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        if(app()->isProduction()){
            Model::preventLazyLoading();
        }

        Model::preventsAccessingMissingAttributes();

        Prescription::observe(PrescriptionObserver::class);
    }
}
