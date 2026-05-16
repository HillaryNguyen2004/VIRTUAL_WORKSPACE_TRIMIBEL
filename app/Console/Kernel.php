<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    // protected function schedule(Schedule $schedule)
    // {
    //     // $schedule->command('inspire')->hourly();
    // }
    protected $commands = [
        \App\Console\Commands\SendScheduledCampaignEmails::class,
    ];

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
    protected function schedule(Schedule $schedule)
    {
        // Existing campaigns
        $schedule->command('campaigns:send-scheduled')->everyMinute()->withoutOverlapping();;
        $schedule->command('meeting:cleanup-recordings')->hourly()->withoutOverlapping();
        $schedule->command('emails:birthday')->dailyAt('08:00')->withoutOverlapping();;

        // 1. Run incremental ETL every night at 1:00 AM
        //    Keeps the data warehouse fresh with yesterday's check-ins and tasks
        $schedule->exec('python3 /opt/lampp/htdocs/DO_AN_CHUYEN_NGANH/etl/incremental_etl_pipeline.py')
                 ->dailyAt('01:00')
                 ->appendOutputTo(storage_path('logs/etl_daily.log'))
                 ->name('etl-daily')
                 ->withoutOverlapping();

        // 2. Refresh LSTM predictions every morning at 6:00 AM
        //    After ETL has loaded new data, regenerate all predictions
        $schedule->command('lstm:generate-predictions')
                 ->dailyAt('06:00')
                 ->appendOutputTo(storage_path('logs/lstm_predictions.log'))
                 ->name('lstm-predictions-daily')
                 ->withoutOverlapping();

        // 3. Rolling-window LSTM retraining (1st of each month at 2:00 AM)
        //    Recomputes train/val/test splits based on current date to avoid future-leakage.
        //    Outputs timestamped models to models/runs/YYYY-MM-DD/ for monthly comparison.
        //    Does NOT auto-update the production symlink; manual promotion required after review.
        $schedule->exec('python3 /opt/lampp/htdocs/DO_AN_CHUYEN_NGANH/ml/train_lstm_rolling.py')
                 ->monthlyOn(1, '02:00')
                 ->appendOutputTo(storage_path('logs/lstm_retrain.log'))
                 ->name('lstm-retrain-monthly')
                 ->withoutOverlapping();
    }
}
