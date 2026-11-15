<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('helloasso:sync')->everyFiveMinutes()->withoutOverlapping();
