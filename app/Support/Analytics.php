<?php

namespace App\Support;

class Analytics
{
    public static function isEnabled(): bool
    {
        return config('analytics.enabled')
            && app()->isProduction()
            && ! self::isAdmin();
    }

    public static function shouldLoadUmami(): bool
    {
        return self::isEnabled()
            && filled(config('analytics.umami.script_url'))
            && filled(config('analytics.umami.website_id'));
    }

    private static function isAdmin(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }
}
