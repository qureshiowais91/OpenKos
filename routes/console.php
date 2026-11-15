<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:generate')
    ->dailyAt('01:00')
    ->withoutOverlapping(60);

Schedule::command('expenses:generate-recurring')
    ->dailyAt('01:05')
    ->withoutOverlapping(60)
    ->onOneServer();

Schedule::command('payments:reconcile')
    ->everyFiveMinutes()
    ->withoutOverlapping(15);

Schedule::command('rent:send-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping(60);

Schedule::command('app:send-lease-expiration-notifications')
    ->dailyAt('08:05')
    ->withoutOverlapping(60);

// Demo mode: password rotates at 00:00 UTC (every run re-applies the same
// date-derived password), data resets via 48h delete+recreate.
Schedule::command('app:demo-rotate')
    ->twiceDailyAt(0, 12, 5)
    ->withoutOverlapping(60);
