<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\NoShowService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reservations:mark-no-shows', function (NoShowService $service) {
    $this->info("Marked {$service->markExpired()} reservation(s) as No-Show.");
})->purpose('Mark reservations past the check-in deadline as No-Show');

Schedule::command('reservations:mark-no-shows')->hourly();
