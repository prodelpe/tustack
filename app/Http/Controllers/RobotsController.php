<?php

namespace App\Http\Controllers;

use App\Support\Robots;
use Illuminate\Http\Response;

/**
 * Fallback for environments that route /robots.txt to the application. Web
 * servers usually special-case that path and serve it as a static file, which
 * is what `php artisan robots:build` writes — see the command for why.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        return response(Robots::content(), 200, ['Content-Type' => 'text/plain']);
    }
}
