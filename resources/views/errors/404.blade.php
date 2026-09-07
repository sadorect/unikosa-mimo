{{-- Used when the framework renders the error itself, before our handler can:
     maintenance mode (`php artisan down`) is the case that matters. --}}
@include('errors.fallback', \App\Support\ErrorPage::for(404, $exception ?? null))
