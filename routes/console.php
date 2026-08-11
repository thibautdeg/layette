<?php

use App\Console\Commands\SendPaymentRemindersCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SendPaymentRemindersCommand::class)
    ->dailyAt('10:00')
    ->timezone('Europe/Brussels');
