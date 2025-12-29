<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Http\Request;

class SantriActivationController extends Controller
{
    public function activate(Request $request, $userId)
    {
        $request->validate([
            'grade_id' => 'required|exists:grades,id',
        ]);

        $user = User::whereNull('santri_id')->findOrFail($userId);

        $santri = Santri::create([
            'nama_lengkap' => $user->name,
            'tempat_lahir' => 'kosong',
            'tanggal_lahir' => '2025-12-12',
            'jenis_kelamin' => 'kosong',
            'alamat_santri' => 'kosong',
            'provinsi_santri' => 'kosong',
            'kota_kabupaten_santri' => 'kosong',
            'nama_ayah' => 'kosong',
            'telepon_ayah' => 'kosong',
            'nama_ibu' => 'kosong',
            'telepon_ibu' => 'kosong',
            'alamat_ortu' => 'kosong',
            'nama_sekolah_asal' => 'kosong',
            'jenjang_pendidikan_terakhir' => 'kosong',
            'alamat_sekolah_asal' => 'kosong',
            'email' => $user->email,
            'grade_id' => $request->grade_id,
            'status' => 'aktif',
        ]);

        $user->santri_id = $santri->id;
        $user->save();

        return response()->json([
            'message' => 'User berhasil diaktifkan menjadi santri',
            'santri' => $santri,
        ]);
    }
}
