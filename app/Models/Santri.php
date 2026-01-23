<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Santri extends Model
{
    use HasFactory;

    // Nama tabel (opsional kalau nama tabel sesuai konvensi plural santris)
    protected $table = 'santris';

    // Kolom yang boleh diisi mass assignment (fillable)
    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat_santri',
        'provinsi_santri',
        'kota_kabupaten_santri',
        'nama_ayah',
        'telepon_ayah',
        'nama_ibu',
        'telepon_ibu',
        'pekerjaan_ayah',
        'pekerjaan_ibu',
        'alamat_ortu',
        'nama_sekolah_asal',
        'jenjang_pendidikan_terakhir',
        'alamat_sekolah_asal',
        'grade_id',
        'status',
        'foto_kk',
        'foto_akte'

    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

}
