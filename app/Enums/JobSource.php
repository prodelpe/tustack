<?php

namespace App\Enums;

enum JobSource: string
{
    case Adzuna = 'adzuna';
    case Jooble = 'jooble';
    case Tecnoempleo = 'tecnoempleo';
}
