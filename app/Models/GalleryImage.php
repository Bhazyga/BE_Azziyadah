<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    protected $fillable = [
        'gallery_id',
        'path',
        'is_cover',
    ];

    public function gallery()
    {
        return $this->belongsTo(Gallery::class);
    }
}
