{{-- Used when the framework renders the error itself, before our handler can:
     maintenance mode (`php artisan down`) is the case that matters. --}}
@include('errors.fallback', \App\Support\ErrorPage::for(500, $exception ?? null))
