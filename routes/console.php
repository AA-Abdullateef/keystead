<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('loans:refresh-overdue')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('idempotency:prune')->dailyAt('02:00')->withoutOverlapping();
