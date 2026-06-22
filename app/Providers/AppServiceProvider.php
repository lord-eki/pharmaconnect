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
use Illuminate\Database\LazyLoadingViolationException;
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
        // Model::preventLazyLoading();
        Model::preventSilentlyDiscardingAttributes();



        Prescription::observe(PrescriptionObserver::class);
        ClaimForm::observe(ClaimFormObserver::class);


        Model::handleLazyLoadingViolationUsing(function($model,$relation){
            $class = get_class($model);

            if(app()->isProduction()){
                logger()->warning("Lazy loading on [{$relation}] on [{$class}]");
            }else{
                throw new LazyLoadingViolationException($model,$relation);
            }
        });
    }
}
