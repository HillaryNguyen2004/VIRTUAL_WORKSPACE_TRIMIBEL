<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\TaskRepository;
use App\Repositories\TaskRepositoryInterface;
use App\Repositories\TeamRepositoryInterface;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Pagination\Paginator;
use App\Repositories\ProjectRepository;
use App\Repositories\ProjectRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Aws\S3\S3Client;
use Aws\Middleware;
use Aws\CommandInterface;


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
        $this->app->bind(
            \App\Repositories\CheckInRepositoryInterface::class,
            \App\Repositories\CheckInRepository::class
        );
        $this->app->bind(
            ProjectRepositoryInterface::class,
            ProjectRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useTailwind();

        // S3 bucket has ACLs disabled (Bucket owner enforced).
        // Strip ACL from every upload so PutObject doesn't get rejected.
        Storage::extend('s3', function ($app, $config) {
            $client = new S3Client([
                'version'     => 'latest',
                'region'      => $config['region'],
                'credentials' => [
                    'key'    => $config['key'],
                    'secret' => $config['secret'],
                ],
                'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? false,
            ]);

            $client->getHandlerList()->appendInit(
                Middleware::mapCommand(function (CommandInterface $cmd) {
                    if (in_array($cmd->getName(), ['PutObject', 'CreateMultipartUpload'])) {
                        $cmd->offsetUnset('ACL');
                    }
                    return $cmd;
                }),
                'remove-acl'
            );

            $adapter = new AwsS3V3Adapter($client, $config['bucket'], $config['prefix'] ?? '');

            return new FilesystemAdapter(
                new Filesystem($adapter),
                $adapter,
                $config
            );
        });
    }
}
