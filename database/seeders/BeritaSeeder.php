<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BeritaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('beritas')->insert([
            [
                'title' => 'Santri Az-Ziyadah Raih Juara Lomba Tahfidz Nasional',
                'slug' => 'santri-juara-tahfidz',
                'image' => 'berita/logo.png',
                'content' => "Alhamdulillah, salah satu santri Pondok Pesantren Az-Ziyadah berhasil meraih juara dalam lomba tahfidz tingkat nasional.\n\nPrestasi ini menjadi bukti bahwa pendidikan Al-Qur’an di Az-Ziyadah terus berkembang dan melahirkan generasi unggul yang berakhlak mulia.\n\nSemoga prestasi ini menjadi motivasi bagi seluruh santri untuk terus meningkatkan kualitas hafalan dan adab.",
                'category' => 'Prestasi',
                'published_at' => Carbon::parse('2025-09-20'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Kegiatan Bakti Sosial Santri Az-Ziyadah di Bulan Muharram',
                'slug' => 'bakti-sosial-muharram',
                'image' => 'berita/logokecil.png',
                'content' => "Dalam rangka menyambut bulan Muharram, santri Az-Ziyadah melaksanakan kegiatan bakti sosial di lingkungan sekitar pondok.\n\nKegiatan ini bertujuan menanamkan kepedulian sosial dan semangat berbagi kepada sesama.",
                'category' => 'Kegiatan',
                'published_at' => Carbon::parse('2025-08-15'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Ustadzah Az-Ziyadah Berikan Kajian Tentang Pentingnya Adab Sebelum Ilmu',
                'slug' => 'kajian-adab-sebelum-ilmu',
                'image' => 'berita/logokecil.png',
                'content' => "Kajian ini menekankan bahwa adab adalah pondasi utama sebelum menuntut ilmu.\n\nPara santri diharapkan tidak hanya cerdas secara akademik, tetapi juga memiliki akhlak yang mulia dalam kehidupan sehari-hari.",
                'category' => 'Kajian',
                'published_at' => Carbon::parse('2025-07-10'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
