<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

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

        foreach ($provinces as $name) {
            Province::query()->firstOrCreate(['name' => $name]);
        }
    }
}
