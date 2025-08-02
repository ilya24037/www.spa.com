<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Расписание для отправки напоминаний о бронированиях
Schedule::command('booking:send-reminders')
    ->dailyAt('10:00')
    ->withoutOverlapping()
    ->runInBackground();
