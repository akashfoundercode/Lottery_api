<?php

use App\Jobs\MarkExpiredBooksUnsoldByAdmin;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Har minute check karo: game live hue 1 ghanta guzra? ASSIGNED books lock karo
Schedule::job(new MarkExpiredBooksUnsoldByAdmin)->everyMinute();
