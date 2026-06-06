<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Company extends Model
{
    protected $fillable = [
        'name',
        'location',
        'country',
        'city',
        'province_id',
        'latitude',
        'longitude',
    ];

    public function province(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }

    public function technologies(): HasManyThrough
    {
        return $this->hasManyThrough(
            Technology::class,
            JobOffer::class,
            'company_id',
            'id',
            'id',
            'id'
        );
    }
}
