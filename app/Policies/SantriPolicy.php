<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Santri;

class SantriPolicy
{
    /**
     * Cek apakah user boleh lihat data santri
     */
    public function view(User $user, Santri $santri): bool
    {
        // Super admin
        if ($user->role === 'admin') {
            return true;
        }

        // Santri wajib punya grade
        if (!$santri->grade) {
            return false;
        }

        return match ($user->role) {
            'adminMI'  => str_starts_with($santri->grade->nama_kelas, 'MI'),
            'adminMTS' => str_starts_with($santri->grade->nama_kelas, 'MTS'),
            'adminMA'  => str_starts_with($santri->grade->nama_kelas, 'MA'),
            default    => false,
        };
    }

    /**
     * Update = aturan sama dengan view
     */
    public function update(User $user, Santri $santri): bool
    {
        return $this->view($user, $santri);
    }

    /**
     * Delete (optional)
     */
    public function delete(User $user, Santri $santri): bool
    {
        return $this->view($user, $santri);
    }
}
