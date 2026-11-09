<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cek koneksi CCTV tiap 5 menit (butuh cron / Task Scheduler yang menjalankan "php artisan schedule:run" tiap menit).
Schedule::command('rt:cek-cctv')->everyFiveMinutes()->withoutOverlapping();
