<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('philarmylab:sync --since-last')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => filled(config('services.philarmylab_legacy.secret')));
