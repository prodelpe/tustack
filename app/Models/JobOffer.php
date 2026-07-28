<?php

namespace App\Models;

use App\Support\JobTitle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobOffer extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'description',
        'url',
        'source',
        'published_at',
        'salary_min',
        'salary_max',
        'salary_is_predicted',
    ];

    protected $casts = [
        'published_at'       => 'date',
        'salary_is_predicted' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (JobOffer $offer) {
            $offer->title_normalized = JobTitle::normalize($offer->title);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'job_offer_technology');
    }
}
