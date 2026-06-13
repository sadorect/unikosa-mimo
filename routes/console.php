<?php

use App\Jobs\SendBirthdayReminders;
use App\Jobs\SendBlogDigest;
use App\Jobs\SendDuesReminders;
use App\Jobs\SendEventDigest;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SendBirthdayReminders)->dailyAt('08:00')->name('birthday-reminders');
Schedule::job(new SendEventDigest)->daily()->at('07:00')->name('event-digest');
Schedule::job(new SendDuesReminders)->monthlyOn(1, '09:00')->name('dues-reminders');
Schedule::job(new SendBlogDigest)->weekly()->mondays()->at('08:00')->name('blog-digest');
Schedule::command('db:backup')->daily()->at('02:00')->name('db-backup');
