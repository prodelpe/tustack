<?php

namespace App\Exceptions;

use RuntimeException;

class JoobleQuotaExceededException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Jooble daily request limit exceeded');
    }
}
