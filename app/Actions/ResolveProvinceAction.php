<?php

namespace App\Actions;

use App\Models\Province;

class ResolveProvinceAction
{
    public function handle(?string $provinceName): ?int
    {
        if (blank($provinceName)) {
            return null;
        }

        return Province::where('name', $provinceName)->value('id');
    }
}
