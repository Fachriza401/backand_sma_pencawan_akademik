<?php

use App\Support\AttendanceAutomation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(AttendanceAutomation::class)->markAlpaForToday())
    ->everyMinute()
    ->name('mark-daily-teacher-alpa');
