<?php
namespace App\Repositories;
use App\Repositories\UserRepositoryInterface;
use \App\Repositories\UserRepository;
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
        UserRepositoryInterface::class,
        UserRepository::class
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
