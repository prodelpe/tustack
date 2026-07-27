<?php

namespace App\Support;

class Robots
{
    public static function content(): string
    {
        return implode("\n", self::lines()) . "\n";
    }

    /**
     * @return array<int, string>
     */
    private static function lines(): array
    {
        $lines = ['User-agent: *'];

        if (config('app.noindex') || ! config('app.available')) {
            $lines[] = 'Disallow: /';

            return $lines;
        }

        foreach (['/admin', '/dashboard', '/login', '/register', '/profile'] as $private) {
            $lines[] = 'Disallow: ' . $private;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . route('sitemap');

        return $lines;
    }
}
