<?php

namespace App\Actions;

use App\Models\Province;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResolveProvinceAction
{
    private ?Collection $bySlug = null;

    public function handle(?string $province, ?string $city = null, ?string $location = null): ?int
    {
        foreach ($this->candidates($province, $city, $location) as $candidate) {
            $id = $this->match($candidate);

            if ($id !== null) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function candidates(?string $province, ?string $city, ?string $location): array
    {
        $candidates = [$province, $city];

        foreach (explode(',', (string) $location) as $part) {
            $candidates[] = $part;
        }

        return array_values(array_filter(array_map('trim', $candidates), 'filled'));
    }

    private function match(string $candidate): ?int
    {
        $slug = Str::slug($candidate);

        if (blank($slug)) {
            return null;
        }

        $provinces = $this->bySlug ??= Province::query()->pluck('id', 'slug');

        if ($provinces->has($slug)) {
            return $provinces->get($slug);
        }

        $alias = config('provinces.aliases.' . $slug);

        return $alias ? $provinces->get(Str::slug($alias)) : null;
    }
}
