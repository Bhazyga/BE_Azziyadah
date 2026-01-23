<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KegiatanController extends Controller
{
    // LIST (admin lihat sesuai jenjang)
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Kegiatan::query();

        // filter by role
        if ($user->role === 'adminMI') {
            $query->where('jenjang', 'MI');
        } elseif ($user->role === 'adminMTS') {
            $query->where('jenjang', 'MTS');
        } elseif ($user->role === 'adminMA') {
            $query->where('jenjang', 'MA');
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'judul' => 'required',
            'deskripsi' => 'required',
            'jenjang' => 'required|in:MI,MTS,MA',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'gambar.max' => 'Ukuran gambar maksimal 2 MB',
            'gambar.image' => 'File harus berupa gambar',
            'gambar.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp',
        ]);


        // 🔥 VALIDASI ROLE vs JENJANG
        if (
            ($user->role === 'adminMI' && $request->jenjang !== 'MI') ||
            ($user->role === 'adminMTS' && $request->jenjang !== 'MTS') ||
            ($user->role === 'adminMA' && $request->jenjang !== 'MA')
        ) {
            return response()->json([
                'message' => 'Anda tidak berhak menambahkan kegiatan untuk jenjang ini'
            ], 403);
        }

        $path = null;

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('kegiatan', 'public');
        }

        Kegiatan::create([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'jenjang' => $request->jenjang,
            'gambar' => $path,
            'is_active' => 1,
        ]);

        return response()->json(['message' => 'Berhasil']);
    }


    // SHOW
    public function show(Kegiatan $kegiatan)
    {
        $this->authorize('manage', $kegiatan);
        return response()->json($kegiatan);
    }


    public function update(Request $request, Kegiatan $kegiatan)
    {
        $user = request()->user();

        if (
            ($user->role === 'adminMI' && $kegiatan->jenjang !== 'MI') ||
            ($user->role === 'adminMTS' && $kegiatan->jenjang !== 'MTS') ||
            ($user->role === 'adminMA' && $kegiatan->jenjang !== 'MA')
        ) {
            abort(403, 'Tidak punya akses ke kegiatan ini');
        }

        $this->authorize('manage', $kegiatan);

        $request->validate([
            'judul' => 'required',
            'deskripsi' => 'required',
            'jenjang' => 'required|in:MI,MTS,MA',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'gambar.max' => 'Ukuran gambar maksimal 2 MB',
            'gambar.image' => 'File harus berupa gambar',
            'gambar.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp',
        ]);

        $path = $kegiatan->gambar; // default tetap gambar lama

        // kalau upload gambar baru
        if ($request->hasFile('gambar')) {

            // 🔥 HAPUS GAMBAR LAMA DULU
            if ($kegiatan->gambar && Storage::disk('public')->exists($kegiatan->gambar)) {
                Storage::disk('public')->delete($kegiatan->gambar);
            }

            // simpan gambar baru
            $path = $request->file('gambar')->store('kegiatan', 'public');
        }

        $kegiatan->update([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'jenjang' => $request->jenjang,
            'gambar' => $path,
            'is_active' => $request->is_active ?? $kegiatan->is_active,
        ]);

        return response()->json(['message' => 'Berhasil update', 'data' => $kegiatan]);
    }


    public function destroy(Kegiatan $kegiatan)
    {

        $user = request()->user();

        if (
            ($user->role === 'adminMI' && $kegiatan->jenjang !== 'MI') ||
            ($user->role === 'adminMTS' && $kegiatan->jenjang !== 'MTS') ||
            ($user->role === 'adminMA' && $kegiatan->jenjang !== 'MA')
        ) {
            abort(403, 'Tidak punya akses ke kegiatan ini');
        }

        $this->authorize('manage', $kegiatan);

        if ($kegiatan->gambar && Storage::disk('public')->exists($kegiatan->gambar)) {
            Storage::disk('public')->delete($kegiatan->gambar);
        }

        $kegiatan->delete();

        return response()->json(['message' => 'Kegiatan deleted']);
    }

    // PUBLIC (buat halaman Programs.jsx)
    public function publicList()
    {
        $data = Kegiatan::where('is_active', true)
            ->orderBy('jenjang')
            ->get()
            ->groupBy('jenjang');

        return response()->json($data);
    }
}
