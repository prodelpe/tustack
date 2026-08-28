<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [
            'Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias',
            'Ávila', 'Badajoz', 'Barcelona', 'Burgos', 'Cáceres',
            'Cádiz', 'Cantabria', 'Castellón', 'Ciudad Real', 'Córdoba',
            'Cuenca', 'Girona', 'Granada', 'Guadalajara', 'Guipúzcoa',
            'Huelva', 'Huesca', 'Illes Balears', 'Jaén', 'A Coruña',
            'La Rioja', 'Las Palmas', 'León', 'Lleida', 'Lugo',
            'Madrid', 'Málaga', 'Murcia', 'Navarra', 'Ourense',
            'Palencia', 'Pontevedra', 'Salamanca', 'Santa Cruz de Tenerife',
            'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Teruel',
            'Toledo', 'Valencia', 'Valladolid', 'Vizcaya', 'Zamora',
            'Zaragoza', 'Ceuta', 'Melilla',
        ];

        // The slug is set here rather than left to the model event: province
        // matching and the seo urls both read it, and a fresh install that
        // silently seeded it empty broke both without any error.
        foreach ($provinces as $name) {
            Province::query()->updateOrCreate(
                ['name' => $name],
                ['slug' => Str::slug($name)],
            );
        }
    }
}
