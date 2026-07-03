<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAppAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.available', true)) {
            return $next($request);
        }

        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        return response()->view('unavailable', [], 503);
    }
}
