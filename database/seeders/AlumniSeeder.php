<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AlumniSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 30; $i++) {

            $startYear = rand(2015, 2023);
            $angkatan = $startYear . '-' . ($startYear + 1);

            DB::table('alumnis')->insert([
                'nama' => $faker->name,
                'angkatan' => $angkatan,
                'image' => 'sample.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
