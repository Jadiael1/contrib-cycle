<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $username = env('ADMIN_USERNAME', 'admin');
        $password = env('ADMIN_PASSWORD', 'change-me-please');

        User::updateOrCreate(
            ['username' => $username],
            [
                'role' => 'admin',
                'password' => Hash::make($password),
                'phone' => null,
                'first_name' => null,
                'last_name' => null,
            ]
        );
    }
}
