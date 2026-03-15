<?php

namespace App\Console;

use App\Console\Commands\PayoutOrder;
use App\Console\Commands\ProjectInfo;
use App\Core\Commands\GenerateFilters;
use App\Core\Commands\CreateRepository;
use Illuminate\Console\Scheduling\Schedule;
use App\Core\Commands\CreateRepositoryContract;
use App\Console\Commands\UpdateAppointmentStatus;
use App\Console\Commands\SendAppointmentReminders;
use App\Console\Commands\MarkMissedAppointments;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected $commands = [
        CreateRepository::class,
        CreateRepositoryContract::class,
        GenerateFilters::class,
        ProjectInfo::class,
        UpdateAppointmentStatus::class,
        SendAppointmentReminders::class,
        MarkMissedAppointments::class
    ];
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('appointments:mark-missed')->everyMinute();
        $schedule->command('appointment:update-status')->everyMinute(); // or hourly etc.
        $schedule->command('reminder:send')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
