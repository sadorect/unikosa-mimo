<?php

use App\Jobs\ProcessLoanLifecycle;
use App\Jobs\SendBirthdayReminders;
use App\Jobs\SendBlogDigest;
use App\Jobs\SendDuesReminders;
use App\Jobs\SendEventDigest;
use App\Jobs\SendLoanReminders;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SendBirthdayReminders)->dailyAt('08:00')->name('birthday-reminders');
Schedule::job(new SendEventDigest)->daily()->at('07:00')->name('event-digest');
Schedule::job(new SendDuesReminders)->monthlyOn(1, '09:00')->name('dues-reminders');
Schedule::job(new SendBlogDigest)->weekly()->mondays()->at('08:00')->name('blog-digest');
// Reminders first, then the lifecycle pass: a borrower should never receive
// "due in 1 day" and "you are overdue" from the same morning.
Schedule::job(new SendLoanReminders)->dailyAt('09:00')->name('loan-reminders');
Schedule::job(new ProcessLoanLifecycle)->dailyAt('09:15')->name('loan-lifecycle');
Schedule::command('db:backup')->daily()->at('02:00')->name('db-backup');
Schedule::command('elections:close-expired')->everyFiveMinutes()->name('close-expired-elections');
