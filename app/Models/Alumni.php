<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{

    use HasFactory;

    protected $fillable = [
        'nama',
        'angkatan',
        'image',
        'tanggal_lahir',
        'nomor_wa',
        'email',
        'alamat'
    ];

}
