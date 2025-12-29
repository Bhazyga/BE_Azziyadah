<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    $schedule->command('midtrans:check-pending')
        ->everyMinute() // UBAH dari everyFifteenMinutes
        ->appendOutputTo(storage_path('logs/midtrans_schedule.log'));
}

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
