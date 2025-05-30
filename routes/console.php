
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Artisan::call(command: 'posts:process-scheduled');

$this->command('posts:process-scheduled')->everyMinute();
// ✅ Schedule your custom command here

// ✅ Use the global `schedule()` function — this is the correct one in Laravel 12
// schedule(function (Schedule $schedule) {
// });
