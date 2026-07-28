<?php

namespace App\Support;

class JobTitle
{
    public static function normalize(?string $title): ?string
    {
        if (blank($title)) {
            return null;
        }

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $title)));
    }
}
