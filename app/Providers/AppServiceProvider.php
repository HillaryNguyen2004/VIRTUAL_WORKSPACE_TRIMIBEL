<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\TaskRepository;
use App\Repositories\TaskRepositoryInterface;
use App\Repositories\TeamRepositoryInterface;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\UserRepository;




class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('fileuploadhelper', function ($app) {
            return new \App\Helpers\FileUploadHelper();
        });
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(TeamRepositoryInterface::class, TeamRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
