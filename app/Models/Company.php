<?php

namespace App\Models;

use App\Support\CompanyName;
use App\Support\MapLocation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Company extends Model
{
    use Searchable;

    protected $casts = [
        'description' => 'array',
    ];

    protected $fillable = [
        'name',
        'slug',
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

    protected static function booted(): void
    {
        // Slugs are assigned once and never regenerated: a renamed company keeps
        // its url so existing links and rankings survive.
        static::saving(function (Company $company) {
            if (blank($company->slug)) {
                $company->slug = static::uniqueSlug($company->name, $company->id);
            }

            $company->name_normalized = CompanyName::normalize($company->name);
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base   = Str::slug($name) ?: 'company';
        $slug   = $base;
        $suffix = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, function (Builder $query, int $id) {
                    $query->whereKeyNot($id);
                })
                ->exists()
        ) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** The stored name is never rewritten: only how it is shown. */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => CompanyName::display($this->name ?? ''));
    }

    public function searchableAs(): string
    {
        return 'devstack_companies';
    }

    /**
     * A company nobody can find by stack has no place in a search by stack.
     * Otis and Radisson Hotel Group came in through offers that read as
     * technologies and read as nothing once that was corrected. The page stays
     * reachable and the rows are untouched: only the catalogue leaves them out.
     */
    public function shouldBeSearchable(): bool
    {
        $this->loadMissing('jobOffers.technologies');

        return $this->jobOffers->flatMap->technologies->isNotEmpty();
    }

    /** Companies with something to say about a stack. */
    public function scopeInCatalogue(Builder $query): void
    {
        $query->whereHas('jobOffers.technologies');
    }

    /**
     * The stack in the order that says something: the technology the company
     * publishes most first, and when it was last seen. A flat list of thirty
     * badges answers nothing.
     *
     * @return Collection<int, array{technology: Technology, offers: int, last_offer_at: ?Carbon}>
     */
    public function technologyStack(): Collection
    {
        return Technology::query()
            ->join('job_offer_technology', 'job_offer_technology.technology_id', '=', 'technologies.id')
            ->join('job_offers', 'job_offers.id', '=', 'job_offer_technology.job_offer_id')
            ->where('job_offers.company_id', $this->id)
            ->select(['technologies.id', 'technologies.name', 'technologies.slug'])
            ->selectRaw('COUNT(DISTINCT job_offers.id) as offers_count')
            ->selectRaw('MAX(job_offers.published_at) as last_offer_at')
            ->groupBy('technologies.id', 'technologies.name', 'technologies.slug')
            ->orderByDesc('offers_count')
            ->orderBy('technologies.name')
            ->get()
            ->map(fn (Technology $technology) => [
                'technology'    => $technology,
                'offers'        => (int) $technology->offers_count,
                'last_offer_at' => $technology->last_offer_at ? Carbon::parse($technology->last_offer_at) : null,
            ]);
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing(['province', 'jobOffers.technologies']);

        $technologies = $this->jobOffers
            ->flatMap->technologies
            ->unique('id');

        return [
            'id'               => $this->id,
            'name'             => $this->display_name,
            'slug'             => $this->slug,
            'city'             => $this->city,
            'province_id'      => $this->province_id,
            'province_name'    => $this->province?->name,
            'country'          => $this->country,
            'technology_ids'   => $technologies->pluck('id')->values()->all(),
            'technology_names' => $technologies->pluck('name')->values()->all(),
            'job_offers_count' => $this->jobOffers->count(),
            'active_offers_count' => $this->activeOffersCount(),
            'last_offer_at'    => $this->jobOffers->max('published_at'),
            'is_consultancy'   => $this->sector === 'it_consulting',
            'is_recruitment'   => $this->sector === 'recruitment',
            '_geo'             => MapLocation::for($this),
        ];
    }

    /**
     * Companies whose offers mention at least $minimum distinct technologies.
     * A single technology usually means a non-tech company that happened to
     * name a tool in an offer.
     */
    public function scopeWithMinimumTechnologies(Builder $query, int $minimum): void
    {
        $query->whereIn('id', function (QueryBuilder $subquery) use ($minimum) {
            $subquery->select('job_offers.company_id')
                ->from('job_offers')
                ->join('job_offer_technology', 'job_offer_technology.job_offer_id', '=', 'job_offers.id')
                ->groupBy('job_offers.company_id')
                ->havingRaw('COUNT(DISTINCT job_offer_technology.technology_id) >= ?', [$minimum]);
        });
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }

    public function activeJobOffers(): HasMany
    {
        return $this->jobOffers()->where('published_at', '>=', self::activeSince());
    }

    public static function activeSince(): Carbon
    {
        return now()->subMonths(config('jobs.active_offer_months'))->startOfDay();
    }

    private function activeOffersCount(): int
    {
        $since = self::activeSince();

        return $this->jobOffers
            ->filter(fn (JobOffer $offer) => $offer->published_at?->gte($since))
            ->count();
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

    public function similarCompanies(int $limit = 6): EloquentCollection
    {
        $technologyIds = $this->jobOffers
            ->flatMap->technologies
            ->unique('id')
            ->pluck('id');

        if ($technologyIds->isEmpty()) {
            return new EloquentCollection();
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
