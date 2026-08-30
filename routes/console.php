<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('fees:send-whatsapp-reminders')->dailyAt('09:00');
