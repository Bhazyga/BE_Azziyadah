<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GurusSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = [
            [
                "name" => "Ihram Al Fahrim S.Ip",
                "subject" => "Kepala Bagian TU MTS TMI",
                "experience" => "9 tahun",
                "bio" => "Biodata Kosong.",
                "image" => "struktur/20.png"
            ],
            [
                "name" => "Ust. Sanusi",
                "subject" => "Guru Madrasah Aliyah",
                "experience" => "10 tahun",
                "bio" => "Biodata Kosong.",
                "image" => "struktur/9.png"
            ],
            [
                "name" => "Ust. Ahmad Idris, S.Pd",
                "subject" => "Guru Madrasah Ibtidaiyah",
                "experience" => "12 tahun",
                "bio" => "Biodata Kosong.",
                "image" => "struktur/10.png"
            ],
            [
                "name" => "Ust. HM. Syukri Ghozali,S.Pd",
                "subject" => "Guru Madrasah Tsanawiyah",
                "experience" => "8 tahun",
                "bio" => "Biodata Kosong",
                "image" => "struktur/11.png"
            ],
            [
                "name" => "Ustadzah Badriyah, S.Pd",
                "subject" => "Bendahara Madrasah",
                "experience" => "7 tahun",
                "bio" => "Biodata Kosong",
                "image" => "struktur/12.png"
            ],
            [
                "name" => "Ustadzah Eva Dzilfiyah, S.Mat",
                "subject" => "Guru Madrasah Aliyah",
                "experience" => "9 tahun",
                "bio" => "Biodata Kosong",
                "image" => "struktur/13.png"
            ],
            [
                "name" => "Ust. Taufiq Amril, M.M",
                "subject" => "Guru Madrasah Aliyah",
                "experience" => "9 tahun",
                "bio" => "Biodata Kosong",
                "image" => "struktur/14.png"
            ],
            [
                "name" => "Us. Ahmad Qosasih",
                "subject" => "Guru Madrasah Tsanawiyah",
                "experience" => "9 tahun",
                "bio" => "Biodata Kosong",
                "image" => "struktur/15.png"
            ],
            [
                "name" => "Ust. Fakrurrozi",
                "subject" => "Guru Madrasah Aliyah",
                "experience" => "9 tahun",
                "bio" => "Biodata Kosong",
                "image" => "struktur/16.png"
            ],
            [
                "name" => "Ust. Ahmad Hetmatiyar, S.Pd",
                "subject" => "Guru Madrasah Aliyah",
                "experience" => "9 tahun",
                "bio" => "Biodata Kosong",
                "image" => "struktur/17.png"
            ],
            [
                "name" => "Ust. Ahmad Supian Hadi, S.Pd",
                "subject" => "Guru Madrasah Aliyah",
                "experience" => "9 tahun",
                "bio" => "Biodata Kosong",
                "image" => "struktur/18.png"
            ],
        ];

        foreach ($teachers as $teacher) {

            DB::table('gurus')->insert([
                'name' => $teacher['name'],
                'slug' => Str::slug($teacher['name']),
                'subject' => $teacher['subject'],
                'experience' => $teacher['experience'],
                'bio' => $teacher['bio'],
                'image' => $teacher['image'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

        }
    }
}
