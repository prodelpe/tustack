<?php

namespace App\Support;

use App\Models\Company;

/**
 * Where a company goes on the map. The text of the popup and the province filter
 * both come from the province a company is filed under, so the pin has to follow
 * it too. Coordinates used to come from Gemini, asked for a "main office": Amazon,
 * filed in Madrid with 142 offers there, sat on its data centres in Huesca, and
 * one pin in five was in the wrong province.
 */
class MapLocation
{
    private static ?array $capitals = null;

    /** @return array{lat: float, lng: float}|null */
    public static function for(Company $company): ?array
    {
        $capitals = self::capitals();
        $province = $company->province?->slug;
        $own      = self::ownCoordinates($company);

        if ($province === null || ! isset($capitals[$province])) {
            return $own;
        }

        // Precise coordinates are worth keeping, but only while they agree with
        // the province: the nearest capital has to be that province's own.
        if ($own !== null && self::nearestProvince($own['lat'], $own['lng']) === $province) {
            return $own;
        }

        [$lat, $lng] = $capitals[$province];

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * The province whose capital is closest. Distances are compared on a flat
     * projection corrected for latitude, which is plenty to tell provinces apart.
     */
    public static function nearestProvince(float $lat, float $lng): string
    {
        $nearest  = '';
        $shortest = INF;

        foreach (self::capitals() as $slug => [$capitalLat, $capitalLng]) {
            $distance = ($lat - $capitalLat) ** 2 + (($lng - $capitalLng) * cos(deg2rad($lat))) ** 2;

            if ($distance < $shortest) {
                $shortest = $distance;
                $nearest  = $slug;
            }
        }

        return $nearest;
    }

    /** @return array{lat: float, lng: float}|null */
    private static function ownCoordinates(Company $company): ?array
    {
        if ($company->latitude === null || $company->longitude === null) {
            return null;
        }

        return ['lat' => (float) $company->latitude, 'lng' => (float) $company->longitude];
    }

    private static function capitals(): array
    {
        return self::$capitals ??= require database_path('data/province_coordinates.php');
    }
}
