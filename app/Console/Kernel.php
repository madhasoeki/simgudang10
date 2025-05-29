<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The commands for the application.
     *
     * @var array
     */
    protected $commands = [
        // Daftarkan commandmu di sini jika perlu, tapi untuk scheduling tidak wajib
        Commands\GenerateOpnameReport::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // Tambahkan baris ini
        $schedule->command('opname:generate')->daily()->at('01:00');
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

// cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1