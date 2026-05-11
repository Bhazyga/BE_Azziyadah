<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KegiatanImage extends Model
{
    protected $fillable = [
        'kegiatan_id',
        'image',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class);
    }
}
