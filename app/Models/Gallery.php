<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    protected $fillable = [
        'judul',
        'deskripsi',
        'tanggal'
    ];
    public function images()
    {
        return $this->hasMany(GalleryImage::class);
    }
}
