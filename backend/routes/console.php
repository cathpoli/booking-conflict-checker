<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily deletion of bookings older than 30 days
Schedule::command('bookings:delete-old')
    ->daily()
    ->at('00:00')
    ->timezone('UTC')
    ->description('Delete bookings older than 30 days');
