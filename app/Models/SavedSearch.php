<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    protected $fillable = ['user_id', 'filters', 'filters_hash', 'last_notified_at'];

    protected $casts = [
        'filters'          => 'array',
        'last_notified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function describe(): string
    {
        $parts = [];

        if (!empty($this->filters['technologies'])) {
            $parts[] = implode(', ', $this->filters['technologies']);
        }

        if (!empty($this->filters['provinces'])) {
            $parts[] = implode(', ', $this->filters['provinces']);
        }

        if (!empty($this->filters['query'])) {
            $parts[] = '"' . $this->filters['query'] . '"';
        }

        return implode(' · ', $parts) ?: 'All companies';
    }
}
