# UNIKOSA Alumni Platform — Build Prompt for VS Code Claude Agent

## Project Overview
Build a global alumni platform for "UNIKOSA" (a Nigerian secondary school alumni association, name may change later — keep branding configurable). Members are spread across multiple continents, organized by graduating set/year and diaspora chapters.

## Tech Stack
- **Backend:** Laravel (latest stable)
- **Frontend:** Vue 3 (via Inertia.js — preferred for tight Laravel integration; use Vue SFCs)
- **Database:** PostgreSQL 16
- **Cache/Queue/Sessions:** Redis 7
- **File Storage:** Cloudflare R2 (S3-compatible driver)
- **Admin Panel:** Filament v3
- **Permissions:** spatie/laravel-permission
- **Payments:** Paystack (NGN) + Stripe (international/diaspora)
- **Containerization:** Docker + Docker Compose (for VPS deployment)
- **Queue worker:** Laravel queue with Redis driver, run as separate container

## Docker Compose Services Required
1. `app` — PHP-FPM (Laravel)
2. `nginx` — web server / reverse proxy
3. `postgres` — PostgreSQL 16
4. `redis` — Redis 7
5. `queue` — Laravel queue worker (same image as app, different command)
6. `scheduler` — Laravel scheduler (cron-driven `php artisan schedule:run`)

Use `.env` for all credentials (DB, Redis, R2 keys, Paystack/Stripe keys, mail). Provide `.env.example` with placeholders.

## Database Design Considerations
- Use JSONB columns for flexible profile fields (skills, social links)
- Store all monetary amounts as integers (smallest currency unit) with separate currency column
- Use PostgreSQL full-text search (tsvector) for: member directory, job listings, blog posts
- Soft deletes on members, posts, jobs (for moderation/audit trail)

---

## CORE MODULES TO BUILD

### 1. Authentication & Member Profiles
- Registration with: full name, email, phone, graduating set/year, house (if applicable), current country/city, profession, bio, skills (tags), social links
- New registrations enter "pending" status — require Set Rep or Admin approval before directory visibility
- Profile privacy controls (member chooses what's publicly visible)
- Auto-assign to diaspora chapter based on country
- Email verification required

### 2. Member Directory
- Searchable/filterable by: graduating set, chapter/country, profession, skills
- "Alumni near me" using country/city data
- Full-text search via PostgreSQL tsvector

### 3. Sets & Chapters Management
- Sets = graduating year groups (e.g., "Set of 2005")
- Chapters = geographic groupings (e.g., "UK Chapter", "US Chapter", "Nigeria Chapter")
- Each set/chapter has its own page, rep(s), and can post announcements to their members only
- New graduating sets can be added by admin; pipeline for current students' association to submit incoming sets

### 4. Financials & Dues Module
- Recurring dues (configurable frequency: annual, per-event, etc.) per set/chapter
- One-off project-based fundraising campaigns with progress bar (target vs raised)
- Dual payment gateway: Paystack (NGN) and Stripe (USD/GBP/EUR etc.) — detect/let user choose
- Payment history per member, downloadable receipts (PDF)
- Public financial transparency reports (aggregate, admin-published)
- Admin dashboard: total raised per campaign, dues compliance per set/chapter

### 5. Fellowship & Community
- Discussion forum/groups — organized by set, chapter, or interest topic
- Posts require moderation approval if from unverified members
- Birthday reminders/shoutouts (based on profile DOB, optional)
- Condolence/prayer request announcements section
- Photo/video gallery — albums (e.g., per reunion/event), upload to R2

### 6. Events
- Event calendar (global + chapter-specific)
- RSVP functionality
- Paid event ticketing via Paystack/Stripe
- Embed field for livestream links (YouTube/Zoom)
- Past events archive with photo gallery links

### 7. Jobs & Career
- Job board — alumni post/apply, admin/moderator approval before listing goes live
- Referral system — alumni can refer candidates, referral tracked against referrer
- "Hire an Alumnus" — separate listing type for alumni offering services
- CV/resume upload (stored on R2)

### 8. Business Directory & Collaboration
- Alumni-owned business listings (name, description, category, contact, link)
- Skill-sharing/volunteer board (members offering free/paid expertise to community)

### 9. Blog/News
- Admin + approved contributor publishing
- Categories/tags, full-text search
- Set-specific newsletter digest capability (future: email digest via queued jobs)

### 10. Notifications
- In-app notification center (mentions, approvals, dues reminders, event RSVPs)
- Email notifications via queued jobs (Redis queue)
- Notification preferences per member

---

## ADMIN PANEL (Filament v3)

### Roles (via spatie/laravel-permission)
- **Super Admin** — full system access
- **Set Representative** — manage own set's members/announcements
- **Chapter Head** — manage own chapter's events/announcements
- **Content Moderator** — approve forum posts, blog submissions, job/business listings
- **Finance Admin** — view/manage dues & campaigns, generate reports (no member deletion rights)
- **Member** — default role

### Admin Features
1. **Full CRUD** on: Members, Sets, Chapters, Events, Jobs, Business Directory, Blog Posts, Forum Posts, Payments/Campaigns
2. **Member verification queue** — approve/reject new registrations
3. **Moderation queue** — pending forum posts, job listings, business listings, blog submissions
4. **Audit log** — track all admin actions (who changed what, when) — use a package like `spatie/laravel-activitylog`
5. **Feature flags / module toggles** — settings table controlling visibility of: Job Board, Business Directory, Mentorship, Forum, Events, Blog — toggleable without deployment
6. **Site Settings panel:**
   - Branding: site name (configurable away from "UNIKOSA" if it changes), logo upload (R2), favicon
   - Theme: light/dark/auto mode default, accent color picker
   - Typography: font selection from curated Google Fonts list, applied site-wide via CSS custom properties
   - Payment gateway keys (Paystack/Stripe) — encrypted storage
   - Currency defaults & exchange rate source (manual or API-based)
   - Email templates (welcome, dues reminder, event RSVP confirmation)
   - Social media links footer
7. **Financial reports** — exportable (CSV/PDF) per set/chapter/campaign/date range
8. **Data export tool** — per-member "export my data" (GDPR-style), admin-triggerable or self-service

---

## THEME/APPEARANCE SYSTEM (Frontend)
- Store theme settings (mode, accent color, font family) in `settings` table
- Apply via CSS custom properties injected into root layout (`:root { --accent: ...; --font-family: ...; }`)
- Member-level override: each member can choose light/dark/auto for their own session (stored in localStorage + optional profile preference)
- Font options: curated list (e.g., Inter, Poppins, Roboto, Lora, Merriweather, Nunito) loaded via Google Fonts CDN, selectable in admin settings

---

## API STRUCTURE
- Build all data-fetching through API Resources (Laravel API Resources / Inertia props) so a future mobile app (Capacitor, per existing GrinMuzik approach) can reuse endpoints
- Versioned API routes under `/api/v1/` for anything intended for future mobile consumption (auth, directory, events, jobs, notifications)

---

## DEPLOYMENT NOTES
- Target: self-managed VPS via Docker Compose
- Nginx config for SSL (Let's Encrypt/Certbot — include setup script or instructions)
- Automated DB backups: scheduled `pg_dump` cron job, upload backup files to Cloudflare R2
- Queue worker and scheduler as separate long-running containers (supervisor or simple docker restart policy)
- `.env.example` with all required variables documented

---

## BUILD ORDER (Phased)
1. **Phase 1 — Foundation:** Docker setup, Laravel+Vue+Inertia scaffold, auth, member profiles, directory, basic admin panel (Filament), roles/permissions, settings table + theme system
2. **Phase 2 — Community:** Forum/fellowship, sets/chapters management, blog, photo gallery, notifications
3. **Phase 3 — Financial:** Dues module, campaigns, Paystack + Stripe integration, receipts, financial reports
4. **Phase 4 — Career & Events:** Job board, referrals, business directory, events + RSVP + ticketing
5. **Phase 5 — Polish:** Audit logs, data export, email digests, full-text search refinement, mobile-API readiness

---

## DELIVERABLES EXPECTED FROM AGENT
- Full Docker Compose setup (working `docker compose up` for local dev matching VPS prod structure)
- Database migrations for all modules above
- Seeders for: roles/permissions, sample sets/chapters, settings defaults
- Filament admin resources for all entities
- Vue/Inertia pages for all member-facing modules
- README with setup instructions, environment variable documentation, and deployment steps for the VPS

Build incrementally per the phased order above, confirming each phase's migrations and core functionality before moving to the next.




# ADDENDUM: Legacy Member Data Import & Profile Claiming

## Context
An existing datasheet of past member registrations exists. To reduce registration friction/apathy, admins should be able to bulk-import this data to pre-populate member profiles. Members then "claim" their pre-loaded profile rather than registering from scratch.

## Features to Build

### 1. Admin Bulk Import Tool (Filament)
- Dedicated "Import Members" page accepting CSV/XLSX upload
- Provide a downloadable template (columns: full name, email, phone, graduating set/year, chapter/country, profession, etc.)
- Column-mapping step — let admin map source spreadsheet columns to system fields (source columns may not match exactly)
- Validation & preview step before commit — flag missing emails, duplicates, invalid formats; allow admin to fix and re-upload
- Import should be repeatable (different sets/batches imported at different times), not a one-off migration

### 2. Imported Member Records
- Imported profiles marked with `imported = true` and `account_claimed = false`
- Skip the normal new-registration approval queue (treated as pre-verified/trusted data)
- Directory-visible immediately (subject to normal privacy defaults)
- No password/login credentials initially

### 3. Profile Claiming Flow
- "Claim your profile" entry point on login/registration page
- Member searches by email/name + graduating set to locate their pre-loaded record
- Verification via email OTP/magic link sent to the email on file
- On verification, member sets a password — record updates to `account_claimed = true` and becomes a normal active account

### 4. Duplicate Handling
- Match logic on email or (name + graduating set) to prevent duplicate profiles if an imported member later self-registers separately
- Admin tooling to merge/reconcile duplicate records if they occur

### 5. Admin Edit Capability
- Imported records editable by admin before and after claiming (correct typos, update set/chapter assignment, etc.)

## Database Notes
- Add `imported` (boolean), `account_claimed` (boolean), `imported_at`, `claimed_at` columns to members table
- Nullable password field (null until claimed)
