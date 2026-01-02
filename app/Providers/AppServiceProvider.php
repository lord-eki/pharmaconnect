<?php

namespace App\Providers;

use App\Http\Responses\CustomLoginResponse;
use App\Models\ClaimForm;
use App\Models\Prescription;
use App\Observers\ClaimFormObserver;
use App\Services\CommissionService;
use App\Services\DeliveryTrackingService;
use App\Services\OrderFulfillmentService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\RiderAssignmentService;
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
        $this->app->singleton(RiderAssignmentService::class);
        $this->app->singleton(DeliveryTrackingService::class);
        $this->app->singleton(CommissionService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(OrderFulfillmentService::class);
        $this->app->singleton(PricingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
     

        Model::preventsAccessingMissingAttributes();

        Prescription::observe(PrescriptionObserver::class);
        ClaimForm::observe(ClaimFormObserver::class);
    }
}
