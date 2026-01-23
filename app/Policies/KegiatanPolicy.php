<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Kegiatan;

class KegiatanPolicy
{
    // lihat list
    public function viewAny(User $user)
    {
        return in_array($user->role, ['admin', 'adminMI', 'adminMTS', 'adminMA']);
    }

    // create
    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'adminMI', 'adminMTS', 'adminMA']);
    }

    // update & delete → hanya jenjang masing-masing (kecuali super admin)
    public function manage(User $user, Kegiatan $kegiatan)
    {
        if ($user->role === 'admin') return true;

        if ($user->role === 'adminMI' && $kegiatan->jenjang === 'MI') return true;
        if ($user->role === 'adminMTS' && $kegiatan->jenjang === 'MTS') return true;
        if ($user->role === 'adminMA' && $kegiatan->jenjang === 'MA') return true;

        return false;
    }
}
