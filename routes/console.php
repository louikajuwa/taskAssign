<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily cleanup: delete email delivery logs older than 30 days.
// (The Redis email queue itself is processed continuously by the queue worker container,
//  so no scheduled task is needed for sending — only for this housekeeping.)
Schedule::command('emails:prune-logs --days=30')
    ->dailyAt('02:00')
    ->withoutOverlapping();
