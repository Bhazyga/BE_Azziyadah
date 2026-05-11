<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul',
        'slug',
        'deskripsi',
        'gambar',
        'jenjang',
        'is_active',
    ];

    public function images()
    {
        return $this->hasMany(KegiatanImage::class);
    }
}
