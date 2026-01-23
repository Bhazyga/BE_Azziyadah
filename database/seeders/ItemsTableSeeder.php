<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('items')->insert([
            [
                'kode_item' => 'ITM001',
                'nama' => 'Formulir Pendaftaran Santri',
                'harga' => 310000,
                'deskripsi' => 'Syarat Sebelum Pengisian Biodata Formulir Santri',
            ],
        ]);
    }
}
