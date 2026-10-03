<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Source of truth / note for production CronJobs.
// Production k3s chạy artisan trực tiếp — không dùng schedule:run.
// See k3s/apps/wallets.nvnhan0810.com/jobs/
Schedule::command('loans:auto-pay-due')
    ->dailyAt('23:00')
    ->timezone('Asia/Ho_Chi_Minh')
    ->name('loans:auto-pay-due')
    ->withoutOverlapping();
