<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grade;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $jenjang = ['MI', 'MTS', 'MA'];
        $kelas = [1, 2, 3];
        $huruf = ['A', 'B', 'C', 'D', 'E'];
        $tahunAjaran = '2025/2026';

        foreach ($jenjang as $j) {
            foreach ($kelas as $k) {
                foreach ($huruf as $h) {
                    Grade::create([
                        'nama_kelas'   => "{$j}_{$k}_{$h}",   // Contoh: MI_1_A
                        'tahun_ajaran' => $tahunAjaran,
                    ]);
                }
            }
        }
    }
}
