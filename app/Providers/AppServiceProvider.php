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
use Illuminate\Filesystem\AwsS3V3Adapter as IlluminateS3Adapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as FlysystemS3Adapter;
use League\Flysystem\Filesystem;
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
        // Returns the proper Illuminate S3 adapter so url() and temporaryUrl() work.
        Storage::extend('s3', function ($app, $config) {
            $s3Config = array_merge($config, ['version' => 'latest']);

            if (!empty($s3Config['key']) && !empty($s3Config['secret'])) {
                $s3Config['credentials'] = [
                    'key'    => $s3Config['key'],
                    'secret' => $s3Config['secret'],
                ];
            }

            $client = new S3Client($s3Config);

            $client->getHandlerList()->appendInit(
                Middleware::mapCommand(function (CommandInterface $cmd) {
                    if (\in_array($cmd->getName(), ['PutObject', 'CreateMultipartUpload'], true)) {
                        $cmd->offsetUnset('ACL');
                    }
                    return $cmd;
                }),
                'remove-acl'
            );

            $flysystemAdapter = new FlysystemS3Adapter(
                $client,
                $config['bucket'],
                $config['prefix'] ?? '',
            );

            return new IlluminateS3Adapter(
                new Filesystem($flysystemAdapter),
                $flysystemAdapter,
                $config,
                $client
            );
        });
    }
}
