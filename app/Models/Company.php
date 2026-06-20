<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Laravel\Scout\Searchable;

class Company extends Model
{
    use Searchable;

    protected $casts = [
        'description' => 'array',
    ];

    protected $fillable = [
        'name',
        'location',
        'country',
        'city',
        'province_id',
        'latitude',
        'longitude',
        'description',
        'sector',
        'employees',
        'website',
        'gemini_enriched',
    ];

public function searchableAs(): string
    {
        return 'devstack_companies';
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing(['province', 'jobOffers.technologies']);

        $technologies = $this->jobOffers
            ->flatMap->technologies
            ->unique('id');

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'city'             => $this->city,
            'province_id'      => $this->province_id,
            'province_name'    => $this->province?->name,
            'country'          => $this->country,
            'technology_ids'   => $technologies->pluck('id')->values()->all(),
            'technology_names' => $technologies->pluck('name')->values()->all(),
            'job_offers_count' => $this->jobOffers->count(),
            'last_offer_at'    => $this->jobOffers->max('published_at'),
            'is_consultancy'   => $this->sector === 'it_consulting',
            'is_recruitment'   => $this->sector === 'recruitment',
            '_geo'             => $this->latitude && $this->longitude
                                    ? ['lat' => (float) $this->latitude, 'lng' => (float) $this->longitude]
                                    : null,
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
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

    public function similarCompanies(int $limit = 6): \Illuminate\Database\Eloquent\Collection
    {
        $technologyIds = $this->jobOffers
            ->flatMap->technologies
            ->unique('id')
            ->pluck('id');

        if ($technologyIds->isEmpty()) {
            return collect();
        }

        return static::selectRaw('companies.*, COUNT(DISTINCT t.id) as shared_tech_count')
            ->join('job_offers as jo', 'jo.company_id', '=', 'companies.id')
            ->join('job_offer_technology as jot', 'jot.job_offer_id', '=', 'jo.id')
            ->join('technologies as t', 't.id', '=', 'jot.technology_id')
            ->whereIn('t.id', $technologyIds)
            ->where('companies.id', '!=', $this->id)
            ->groupBy('companies.id')
            ->orderByDesc('shared_tech_count')
            ->limit($limit)
            ->with('province')
            ->get();
    }
}
