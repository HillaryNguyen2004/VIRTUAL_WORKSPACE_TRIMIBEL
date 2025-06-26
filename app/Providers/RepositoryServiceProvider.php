<?php
// namespace App\Repositories;
namespace App\Providers;
use App\Repositories\UserRepositoryInterface;
use \App\Repositories\UserRepository;
use App\Repositories\UserPermissionRepositoryInterface;
use App\Repositories\UserPermissionRepository;


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
        $this->app->bind(UserPermissionRepositoryInterface::class, UserPermissionRepository::class);
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
