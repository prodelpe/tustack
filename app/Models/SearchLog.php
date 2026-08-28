<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'technologies',
        'province',
    ];

    protected $casts = [
        'technologies' => 'array',
    ];
}
