<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // 'http://localhost:8000/api/login'
        // "https://api.azziyadahklender.id/login",
        // "https://api.azziyadahklender.id/api/users",
        // "https://api.azziyadahklender.id/api/materials",
        // "https://11dbed8dd942.ngrok-free.app/api/midtrans/notification",
    ];
}
