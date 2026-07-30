<?php

namespace App\Support;

class JobUrl
{
    public static function canonical(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '';
        }

        return preg_replace('/[?#].*$/', '', $url);
    }
}
