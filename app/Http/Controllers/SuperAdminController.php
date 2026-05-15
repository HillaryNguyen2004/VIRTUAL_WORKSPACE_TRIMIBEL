<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SuperAdminController extends Controller
{
    public function logs(Request $request)
    {
        Gate::authorize('view-logs');

        $logPath = storage_path('logs/laravel.log');
        $level = $request->query('level', 'all');

        $lines = file_exists($logPath) ? array_reverse(file($logPath)) : [];

        $logs = collect($lines)
            ->filter(fn($line) => str_contains($line, 'local.'))
            ->map(function ($line) {
                preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?\.(ERROR|WARNING|INFO|DEBUG): (.+)/', $line, $matches);
                return $matches
                    ? ['time' => $matches[1], 'level' => strtolower($matches[2]), 'message' => $matches[3]]
                    : null;
            })
            ->filter()
            ->when($level !== 'all', fn($collection) => $collection->where('level', $level))
            ->take(100);

        return view('super_admin.logs', compact('logs', 'level'));
    }

    public function clearLogs()
    {
        Gate::authorize('view-logs');
        file_put_contents(storage_path('logs/laravel.log'), '');

        return back()->with('success', 'Logs cleared.');
    }

    public function database()
    {
        Gate::authorize('manage-database');

        $tables = DB::select('SHOW TABLE STATUS');

        $tableStats = collect($tables)->map(fn($table) => [
            'name' => $table->Name,
            'rows' => number_format($table->Rows),
            'size' => round(($table->Data_length + $table->Index_length) / 1024 / 1024, 2) . ' MB',
            'engine' => $table->Engine,
        ]);

        $poolStatus = DB::select('SHOW STATUS WHERE Variable_name = "Threads_connected"');
        $connectionInfo = [
            'driver' => config('database.default'),
            'host' => config('database.connections.mysql.host'),
            'database' => config('database.connections.mysql.database'),
            'pool' => $poolStatus[0]->Value ?? 0,
        ];

        return view('super_admin.database', compact('tableStats', 'connectionInfo'));
    }

    public function queues()
    {
        Gate::authorize('manage-queues');

        $schema = DB::getSchemaBuilder();

        $queues = $schema->hasTable('jobs')
            ? DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as pending'))
                ->groupBy('queue')
                ->get()
            : collect();

        $failedJobs = $schema->hasTable('failed_jobs')
            ? DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->take(20)
                ->get()
            : collect();

        return view('super_admin.queues', compact('queues', 'failedJobs'));
    }

    public function retryFailedJob(Request $request, string $id)
    {
        Gate::authorize('manage-queues');

        Artisan::call('queue:retry', ['id' => [$id]]);

        return back()->with('success', 'Job queued for retry.');
    }

    public function retryAllFailed()
    {
        Gate::authorize('manage-queues');

        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('success', 'All failed jobs retried.');
    }

    public function health()
    {
        Gate::authorize('view-system-info');

        try {
            DB::connection()->getPdo();
            $dbStatus = 'online';
        } catch (\Exception $exception) {
            $dbStatus = 'offline';
        }

        try {
            Cache::put('health_check', true, 5);
            $cacheStatus = 'online';
        } catch (\Exception $exception) {
            $cacheStatus = 'offline';
        }

        return view('super_admin.health', [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'db_status' => $dbStatus,
            'cache_status' => $cacheStatus,
            'queue_driver' => config('queue.default'),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 1) . ' MB',
            'disk_free' => round(disk_free_space('/') / 1073741824, 1) . ' GB free',
            'uptime' => @shell_exec('uptime -p') ?? 'N/A',
        ]);
    }
}
