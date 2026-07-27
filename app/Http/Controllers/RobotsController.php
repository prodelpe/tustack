<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = ['User-agent: *'];

        if (config('app.noindex') || ! config('app.available')) {
            $lines[] = 'Disallow: /';

            return $this->plainText($lines);
        }

        foreach (['/admin', '/dashboard', '/login', '/register', '/profile'] as $private) {
            $lines[] = 'Disallow: ' . $private;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . route('sitemap');

        return $this->plainText($lines);
    }

    private function plainText(array $lines): Response
    {
        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain']);
    }
}
