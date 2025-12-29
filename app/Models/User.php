<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;


class User extends Authenticatable implements MustVerifyEmail {
    use HasApiTokens, HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'santri_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
    ];


    public function generateEmailOtp()
    {
        $this->email_otp = rand(100000, 999999);
        $this->email_otp_expires_at = now()->addMinutes(60);
        $this->save();
    }

    public function sendPasswordResetNotification($token)
    {
        $url = config('app.frontend_url')
            . '/reset-password'
            . '?token=' . $token
            . '&email=' . urlencode($this->email);

        $this->notify(new CustomResetPassword($token, $url));
    }

    public function santri()
    {
        return $this->hasOne(Santri::class);
    }
}
