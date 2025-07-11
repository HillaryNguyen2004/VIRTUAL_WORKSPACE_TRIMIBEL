<?php
namespace NguyenNguyen\CompanyHour;

use Illuminate\Support\ServiceProvider;

class CompanyHourServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Log::info('✅ CompanyHourServiceProvider loaded');
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/Views', 'companyhour');
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        $this->publishes([
            __DIR__.'/Views' => resource_path('views/vendor/companyhour'),
        ], 'views');
    }

    public function register()
    {
        //
    }
}
