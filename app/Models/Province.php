<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Province extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug'];

    protected static function booted(): void
    {
        static::saving(function (Province $province) {
            if (blank($province->slug)) {
                $province->slug = Str::slug($province->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
