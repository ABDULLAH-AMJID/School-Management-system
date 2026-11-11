<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('school:messages:dispatch')->everyMinute()->withoutOverlapping();
Schedule::command('school:messages:retry')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('school:fees:apply-fines')->dailyAt('00:30')->withoutOverlapping();
Schedule::command('school:backup')->dailyAt('01:30')->withoutOverlapping();
