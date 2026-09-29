<?php

namespace App\Providers;

use App\Classes\Settings;
use App\Services\Activation\ApplicationActivationService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Settings::class, function () {
            return Settings::make(storage_path('app/settings.json'));
        });

        // Register activation service as singleton — verified once per request cycle.
        // The singleton ensures we don't re-read and re-verify the activation file
        // on every call within a single request.
        $this->app->singleton(ApplicationActivationService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();
    }


}
