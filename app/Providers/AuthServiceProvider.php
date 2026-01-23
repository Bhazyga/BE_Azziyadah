<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Kegiatan;
use App\Models\Santri;
use App\Policies\KegiatanPolicy;
use App\Policies\SantriPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Santri::class => SantriPolicy::class,
        Kegiatan::class => KegiatanPolicy::class
    ];


    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
