# Email & the queue worker — unikosa-mimo (national)

## How mail is sent

- **Transport:** SMTP to the local mail server (`MAIL_URL=smtp://127.0.0.1:25`).
  Verified working — the host listens on `:25`/`:587` and accepts messages.
- **Sender:** `noreply@sadorect.com`. This domain has valid **SPF**, **DKIM**
  (`mail._domainkey.sadorect.com`), and **DMARC** (`p=quarantine`) records, so
  mail authenticates and lands in the inbox.

## ⚠️ The queue worker is required for mail

Eight notifications/mailables implement `ShouldQueue` (event reminders, digests,
RSVP confirmations, dues reminders, member-approved, forum replies, birthdays,
blog digests). They are pushed onto the **Redis** queue (`QUEUE_CONNECTION=redis`)
and are **only delivered when a queue worker is running.** With no worker, this
mail queues silently and is never sent.

Pick **one** of the three options below to keep a worker running.

### Option A — systemd (preferred for production)

```bash
sudo cp deploy/unikosa-mimo-worker.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now unikosa-mimo-worker
sudo systemctl status unikosa-mimo-worker
# after each deploy that changes code:
sudo systemctl restart unikosa-mimo-worker
```

### Option B — Supervisor (matches other apps on this host)

```bash
sudo cp deploy/supervisor-unikosa-mimo.conf /etc/supervisor/conf.d/unikosa-mimo-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
# after each deploy:
sudo supervisorctl restart unikosa-mimo-worker:*
```

### Option C — user crontab (no root required)

Runs a short-lived worker every minute that drains the queue and exits; `flock`
prevents overlapping runs. Add to the `deploy` user's crontab
(`crontab -e`):

```cron
* * * * * flock -n /home/unikosa/unikosa-mimo/storage/framework/mimo-worker.lock /usr/bin/php /home/unikosa/unikosa-mimo/artisan queue:work redis --stop-when-empty --max-time=55 --tries=3 --sleep=1 >> /home/unikosa/unikosa-mimo/storage/logs/worker.log 2>&1
```

Trade-off: up to ~1 minute of delivery latency (vs. near-instant with A/B), but
needs no root and is self-healing.

## Verifying

```bash
# Is a worker running?
ps aux | grep 'unikosa-mimo.*queue:work' | grep -v grep

# Anything stuck / failed?
php artisan queue:failed        # list failed jobs
php artisan queue:retry all     # re-attempt them
php artisan queue:flush         # discard them permanently

# End-to-end send test (bypasses the queue, tests transport only):
php artisan tinker --execute='Illuminate\Support\Facades\Mail::raw("test", fn($m)=>$m->to("you@example.com")->subject("test"));'
```

> The chapter app (unikosana) sends mail **synchronously** (no `ShouldQueue`),
> so it does **not** need a worker for email.
