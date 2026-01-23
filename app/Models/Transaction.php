<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'item_id',
        'transaction_id',
        'midtrans_order_id',
        'jumlah',
        'total_harga',
        'status',
        'payment_type',
        'midtrans_response',
        'transaction_time',
        'payment_time',
    ];

    protected $casts = [
        'midtrans_response' => 'array',
        'transaction_time' => 'datetime',
        'payment_time' => 'datetime',
    ];

    // 🔥 RELASI KE USER (INI YANG DIPAKE)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke Item
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}

