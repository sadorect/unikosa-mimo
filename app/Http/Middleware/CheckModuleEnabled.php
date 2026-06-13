<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $key = "{$module}_enabled";
        $enabled = Setting::where('key', $key)->value('value');

        if ($enabled === 'false' || $enabled === '0') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'This module is currently disabled.'], 503);
            }
            abort(503, 'This module is currently disabled.');
        }

        return $next($request);
    }
}
