<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => config('admin.email')],
            [
                'name'              => config('admin.name'),
                'password'          => Hash::make(config('admin.password')),
                'is_admin'          => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
