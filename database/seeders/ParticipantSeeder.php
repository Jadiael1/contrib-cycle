<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ParticipantSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();
        $ddds = [
            11, 12, 13, 14, 15, 16, 17, 18, 19, // SP
            21, 22, 24, // RJ
            31, 32, 33, 34, 35, 37, 38, // MG
            41, 42, 43, 44, 45, 46, // PR
            51, 53, 54, 55, // RS
            61, // DF
            71, 73, 74, 75, 77, // BA
            81, 87, // PE
            85, 88, // CE
        ];

        foreach (range(1, 100) as $i) {
            $ddd = $ddds[array_rand($ddds)];

            $phone = '55'
                . $ddd
                . '9'
                . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

            User::create([
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'phone' => $phone,
            ]);
        }
    }
}
