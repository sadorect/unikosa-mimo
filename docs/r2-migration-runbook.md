# Switching asset storage to Cloudflare R2 — unikosa-mimo (national)

This app stores uploaded assets on a configurable disk (`filesystems.media_disk`),
defaulting to the local `public` disk. This runbook moves everything to
Cloudflare **R2** with only environment changes + backfill commands.

> **How assets are stored here:** upload controllers save files with
> `Storage::disk(config('filesystems.media_disk'))` and persist a **fully
> qualified URL** (built at upload time) into the database. So switching the
> disk covers *new* uploads, but *existing* rows keep their old
> `https://<app-url>/storage/...` URLs and must be rewritten (step 4).

## The commands (what each does)

| Command | What it does |
|---|---|
| `php artisan assets:sync-r2` | Copies every file from the local `public` disk to `r2`. Idempotent, resumable. Options: `--dry-run`, `--overwrite`, `--from=`, `--to=`. |
| `php artisan assets:rewrite-urls` | Rewrites stored absolute asset URLs from the old `/storage` host to the R2 public URL, across these columns: `users.avatar`, `candidates.photo`, `events.cover_image`, `gallery_media.path`, `job_applications.cv_path`. Options: `--dry-run`, `--from=`, `--to=`. |

Defaults: `--from` = `APP_URL/storage`, `--to` = the `r2` disk's `url`. Run
`php artisan <command> --help` for details at any time.

## Cutover steps

```bash
# 1. Set the R2 credentials in .env (leave MEDIA_DISK=public for now):
#    R2_ACCESS_KEY_ID=...
#    R2_SECRET_ACCESS_KEY=...
#    R2_ENDPOINT=https://<accountid>.r2.cloudflarestorage.com
#    R2_BUCKET=unikosa
#    R2_URL=https://<your-public-r2-domain>   # bucket public URL or custom domain

# 2. Rebuild config cache so the r2 disk picks up the new env.
#    (IMPORTANT: config is cached — changes won't apply without this.)
php artisan optimize

# 3. Copy existing files up. Preview first, then run for real.
php artisan assets:sync-r2 --dry-run
php artisan assets:sync-r2

# 4. Rewrite stored absolute URLs to the R2 host. Preview first, then run.
php artisan assets:rewrite-urls --dry-run
php artisan assets:rewrite-urls

# 5. Flip the switch, then rebuild cache again.
#    Set MEDIA_DISK=r2 in .env, then:
php artisan optimize
```

**Order matters:** sync files → rewrite DB URLs → flip `MEDIA_DISK` → rebuild cache.

Note the SSO avatar returned to satellite sites (e.g. Unikosana) is whatever is
stored in `users.avatar`; after step 4 it becomes an R2 URL, so satellites pick
up the new host automatically on the next login.

## Rollback

If something looks wrong after step 5: set `MEDIA_DISK=public`, run
`php artisan optimize`, and reverse the URL rewrite with
`php artisan assets:rewrite-urls --from=<R2_URL> --to=<APP_URL>/storage`.
Local files are untouched by the sync (it only copies *up*).

## Notes

- `assets:sync-r2` also copies `storage/app/public/.gitignore` — harmless.
- `SiteSettings` logo/favicon store *relative* paths and resolve via `media_disk`,
  so they flip automatically once files are synced — not part of the URL rewrite.
- The `BackupDatabase` command already writes to the `r2` disk; confirm the same
  R2 credentials are valid there after the switch.
