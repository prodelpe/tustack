<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'published_at' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'job_offer_technology');
    }
}
