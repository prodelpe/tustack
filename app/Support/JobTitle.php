<?php

namespace App\Support;

use Illuminate\Support\Str;

class JobTitle
{
    public static function normalize(?string $title): ?string
    {
        if (blank($title)) {
            return null;
        }

        $title = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $title)));

        return Str::ascii($title);
    }
}
