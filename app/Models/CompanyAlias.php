<?php

namespace App\Models;

use App\Support\CompanyName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A decision about whether two company names are the same employer, remembered
 * so it is only made once. The nightly import reads the approved ones, which is
 * what keeps a merged company from being recreated on the next fetch.
 */
class CompanyAlias extends Model
{
    public const PENDING  = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'name_normalized',
        'company_id',
        'pair_key',
        'status',
        'source',
        'moved_offer_ids',
    ];

    protected $casts = [
        'moved_offer_ids' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (CompanyAlias $alias) {
            $alias->name_normalized ??= CompanyName::normalize($alias->name);
        });
    }

    /** Both names in one key, order independent, so a pair is asked about once. */
    public static function pairKey(?string $first, ?string $second): string
    {
        $names = [
            CompanyName::normalize($first) ?? '',
            CompanyName::normalize($second) ?? '',
        ];

        sort($names);

        return implode('|', $names);
    }

    /** The surviving company a name points to, or null if nobody decided yet. */
    public static function companyFor(?string $normalized): ?Company
    {
        if (blank($normalized)) {
            return null;
        }

        return static::query()
            ->where('name_normalized', $normalized)
            ->where('status', self::APPROVED)
            ->first()?->company;
    }

    /** The company about to disappear. Only exists while the merge is pending. */
    public function absorbedCompany(): ?Company
    {
        return Company::query()
            ->where('name_normalized', $this->name_normalized)
            ->first();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', self::PENDING);
    }
}
