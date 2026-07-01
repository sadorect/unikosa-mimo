<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\ClaimProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SetController;
use App\Http\Controllers\TransparencyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return inertia('Welcome', [
        'stats' => [
            'members'  => \App\Models\User::where('status', 'approved')->count(),
            'chapters' => \App\Models\Chapter::count(),
            'sets'     => \App\Models\Set::count(),
            'events'   => \App\Models\Event::count(),
        ],
    ]);
});

Route::get('/claim-profile', [ClaimProfileController::class, 'show'])->name('claim-profile.show');
Route::post('/claim-profile/verify', [ClaimProfileController::class, 'verifyOtp'])
    ->middleware('throttle:5,1')
    ->name('claim-profile.verify');

Route::get('/transparency', [TransparencyController::class, 'index'])->name('transparency.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/directory', [DirectoryController::class, 'index'])->name('directory');

    Route::middleware('module:events')->group(function () {
        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
        Route::post('/events/{event}/rsvp', [EventController::class, 'rsvp'])->name('events.rsvp');
        Route::post('/events/{event}/ticket', [EventController::class, 'purchaseTicket'])->name('events.ticket');
    });

    Route::middleware('module:forum')->group(function () {
        Route::get('/forum', [ForumController::class, 'index'])->name('forum.index');
        Route::get('/forum/create', [ForumController::class, 'create'])->name('forum.create');
        Route::post('/forum', [ForumController::class, 'store'])->name('forum.store');
        Route::get('/forum/{post}', [ForumController::class, 'show'])->name('forum.show');
        Route::post('/forum/{post}/reply', [ForumController::class, 'storeReply'])->name('forum.reply');
        Route::get('/forum/group/{group:slug}', [ForumController::class, 'group'])->name('forum.group');
    });

    Route::middleware('module:blog')->group(function () {
        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/create', [BlogController::class, 'create'])->name('blog.create');
        Route::post('/blog', [BlogController::class, 'store'])->name('blog.store');
        Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
    });

    Route::middleware('module:job_board')->group(function () {
        Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/create', [JobController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [JobController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show');
        Route::post('/jobs/{job}/apply', [JobController::class, 'apply'])->name('jobs.apply');
        Route::get('/jobs/{job}/applications', [JobController::class, 'applications'])->name('jobs.applications');
    });

    Route::middleware('module:business_directory')->group(function () {
        Route::get('/business', [BusinessController::class, 'index'])->name('business.index');
        Route::get('/business/create', [BusinessController::class, 'create'])->name('business.create');
        Route::post('/business', [BusinessController::class, 'store'])->name('business.store');
        Route::get('/business/{business}', [BusinessController::class, 'show'])->name('business.show');
    });

    Route::middleware('module:gallery')->group(function () {
        Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
        Route::get('/gallery/{album}', [GalleryController::class, 'show'])->name('gallery.show');
        Route::post('/gallery', [GalleryController::class, 'store'])->name('gallery.store');
        Route::post('/gallery/{album}/upload', [GalleryController::class, 'uploadMedia'])->name('gallery.upload');
    });

    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');

    Route::get('/sets', [SetController::class, 'index'])->name('sets.index');
    Route::get('/sets/{set}', [SetController::class, 'show'])->name('sets.show');

    Route::get('/chapters', [ChapterController::class, 'index'])->name('chapters.index');
    Route::get('/chapters/{chapter}', [ChapterController::class, 'show'])->name('chapters.show');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::get('/payments/history', [PaymentController::class, 'history'])->name('payments.history');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::post('/payments/due/{due}', [PaymentController::class, 'payDue'])->name('payments.due');
    Route::post('/payments/campaign/{campaign}', [PaymentController::class, 'payCampaign'])->name('payments.campaign');
    Route::get('/payments/success', [PaymentController::class, 'success'])->name('payments.success');
    Route::get('/payments/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::post('/payments/verify/paystack', [PaymentController::class, 'verifyPaystack'])->name('payments.verify.paystack');

    Route::get('/profile', function () { return inertia('Profile/Edit'); })->name('profile.edit');
    Route::get('/profile/export', [DataExportController::class, 'index'])->name('profile.export');
    Route::get('/profile/export/download', [DataExportController::class, 'exportMyData'])->name('profile.export.download');
});

Route::get('/search', [SearchController::class, 'global'])->name('search.global');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware(fn ($req, $next) => $req->user()?->hasRole('super_admin') ? $next($req) : abort(403))->group(function () {
        Route::get('/admin/financial-reports', [FinancialReportController::class, 'index'])->name('admin.reports.index');
        Route::get('/admin/financial-reports/summary', [FinancialReportController::class, 'summary'])->name('admin.reports.summary');
        Route::get('/admin/financial-reports/export', [FinancialReportController::class, 'export'])->name('admin.reports.export');
        Route::get('/admin/export-users', [DataExportController::class, 'adminExportUsers'])->name('admin.export.users');
    });
});
