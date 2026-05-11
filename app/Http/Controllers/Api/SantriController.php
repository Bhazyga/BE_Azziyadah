<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Santri;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class SantriController extends Controller
{
    /**
     * Tampilkan daftar santri.
     *  GET /api/santris?search=ahmad
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $santris = Santri::with('grade')
            ->when($search, function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nama_ayah', 'like', "%{$search}%")
                  ->orWhere('nama_ibu', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15); // atau ->get() kalau kamu gak pakai pagination

        return response()->json($santris, Response::HTTP_OK);
    }


    /**
     * Simpan santri baru.
     *  POST /api/santris
     */
    // public function store(Request $request)
    // {
    //     $data = $request->validate([
    //         'nama_lengkap'               => 'required|string|max:255',
    //         'tempat_lahir'               => 'required|string|max:100',
    //         'tanggal_lahir'              => 'required|date',
    //         'jenis_kelamin'              => 'required|string|max:20',
    //         'alamat_santri'              => 'required|string',
    //         'provinsi_santri'            => 'required|string|max:100',
    //         'kota_kabupaten_santri'      => 'required|string|max:100',
    //         'nama_ayah'                  => 'required|string|max:255',
    //         'telepon_ayah'               => 'required|string|max:50',
    //         'nama_ibu'                   => 'required|string|max:255',
    //         'telepon_ibu'                => 'required|string|max:50',
    //         'pekerjaan_ayah'             => 'nullable|string|max:100',
    //         'pekerjaan_ibu'              => 'nullable|string|max:100',
    //         'alamat_ortu'                => 'required|string',
    //         'nama_sekolah_asal'          => 'required|string|max:255',
    //         'jenjang_pendidikan_terakhir'=> 'required|string|max:50',
    //         'alamat_sekolah_asal'        => 'required|string',
    //         'foto_kk'                    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    //         'foto_akte'                  => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
    //     ]);

    //     if ($request->hasFile('foto_kk')) {
    //         $data['foto_kk'] = $request->file('foto_kk')
    //             ->store('santri/kk', 'public');
    //     }

    //     if ($request->hasFile('foto_akte')) {
    //         $data['foto_akte'] = $request->file('foto_akte')
    //             ->store('santri/akte', 'public');
    //     }

    //     $santri = Santri::create($data);

    //     return response()->json($santri, Response::HTTP_CREATED);
    // }

    /**
     * Tampilkan detail santri.
     *  GET /api/santris/{id}
     */
    public function show(Santri $santri)
    {
        return response()->json($santri, Response::HTTP_OK);
    }
    // public function biodata($id)
    // {
    //     $santri = Santri::with(['santri', 'item'])->findOrFail($id);
    //     return response()->json($santri);
    // }

    /**
     * Perbarui data santri.
     *  PUT/PATCH /api/santris/{id}
     */
    public function update(Request $request, Santri $santri)
    {

        if ($santri->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'nama_lengkap'               => 'required|string|max:255',
            'tempat_lahir'               => 'required|string|max:100',
            'tanggal_lahir'              => 'required|date',
            'jenis_kelamin'              => 'required|string|max:20',
            'alamat_santri'              => 'required|string',
            'provinsi_santri'            => 'required|string|max:100',
            'kota_kabupaten_santri'      => 'required|string|max:100',
            'nama_ayah'                  => 'required|string|max:255',
            'telepon_ayah'               => 'required|string|max:50',
            'nama_ibu'                   => 'required|string|max:255',
            'telepon_ibu'                => 'required|string|max:50',
            'pekerjaan_ayah'             => 'nullable|string|max:100',
            'pekerjaan_ibu'              => 'nullable|string|max:100',
            'alamat_ortu'                => 'required|string',
            'nama_sekolah_asal'          => 'required|string|max:255',
            'jenjang_pendidikan_terakhir'=> 'required|string|max:50',
            'alamat_sekolah_asal'        => 'required|string',
            'foto_kk'                    => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'foto_akte'                  => 'nullable|image|mimes:jpg,jpeg,png|max:4096'
        ]);

        if ($request->hasFile('foto_kk')) {
            if ($santri->foto_kk) {
                Storage::disk('public')->delete($santri->foto_kk);
            }

            $data['foto_kk'] = $request->file('foto_kk')
                ->store('santri/kk', 'public');
        }

        if ($request->hasFile('foto_akte')) {
            $data['foto_akte'] = $request->file('foto_akte')
                ->store('santri/akte', 'public');
        }

        $santri->update($data);

        return response()->json($santri, Response::HTTP_OK);
    }

    /**
     * Hapus santri.
     *  DELETE /api/santris/{id}
     */
    public function destroy(Santri $santri)
    {
        $santri->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function getUnpaidItems($santriId)
    {
        $paidItemIds = Transaction::where('santri_id', $santriId)
            ->whereIn('status', ['settlement', 'capture'])
            ->pluck('item_id');

        $unpaidItems = Item::whereNotIn('id', $paidItemIds)
            ->where('aktif', true)
            ->get();

        return response()->json($unpaidItems);
    }

    public function byUser()
    {
        $user = auth()->user();

        $santri = Santri::where('user_id', $user->id)->first();

        if (!$santri) {
            return response()->json([
                'exists' => false,
                'status' => null,
            ]);
        }

        return response()->json([
            'exists' => true,
            'status' => $santri->status,
            'santri_id' => $santri->id,
        ]);
    }

    public function biodata()
    {
        $user = auth()->user();

        $santri = Santri::where('user_id', $user->id)->first();

        if (!$santri) {
            return response()->json(null, 404);
        }

        return response()->json($santri);
    }


}
