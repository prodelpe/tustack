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

        // The admin and the Telegram webhook must keep working while the site
        // is closed to the public.
        if (
            $request->is('admin') || $request->is('admin/*')
            || $request->is('telegram/webhook')
            || $request->is('robots.txt') || $request->is('humans.txt')
        ) {
            return $next($request);
        }

        return response()->view('unavailable', [], 503);
    }
}
