<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\KegiatanImage;

class KegiatanController extends Controller
{

    private function generateSlug($judul, $excludeId = null)
    {
        $baseSlug = Str::slug($judul);

        if (!$baseSlug) {
            $baseSlug = 'kegiatan-' . now()->timestamp;
        }

        $slug = $baseSlug;
        $count = 1;

        while (
            Kegiatan::where('slug', $slug)
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $count++;
        }

        return $slug;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Kegiatan::query();

        if ($user->role === 'adminMI') {
            $query->where('jenjang', 'MI');
        } elseif ($user->role === 'adminMTS') {
            $query->where('jenjang', 'MTS');
        } elseif ($user->role === 'adminMA') {
            $query->where('jenjang', 'MA');
        } elseif ($user->role === 'admin') {
            $query->where('jenjang', 'YAYASAN');
        }

        if ($request->search) {
            $query->where('judul', 'like', '%'.$request->search.'%');
        }

        return $query->with('images')->latest()->paginate(10);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'judul' => 'required',
            'deskripsi' => 'required',
            'jenjang' => 'required|in:MI,MTS,MA,YAYASAN',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'gambar.max' => 'Ukuran gambar maksimal 2 MB',
            'gambar.image' => 'File harus berupa gambar',
            'gambar.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp',
        ]);

        if (
            ($user->role === 'adminMI' && $request->jenjang !== 'MI') ||
            ($user->role === 'adminMTS' && $request->jenjang !== 'MTS') ||
            ($user->role === 'adminMA' && $request->jenjang !== 'MA') ||
            ($user->role === 'admin' && $request->jenjang !== 'YAYASAN')
        ) {
            return response()->json([
                'message' => 'Anda tidak berhak menambahkan kegiatan untuk jenjang ini'
            ], 403);
        }

        // ✅ GAMBAR UTAMA
        $mainImagePath = null;

        if ($request->hasFile('gambar')) {
            $mainImagePath = $request->file('gambar')->store('kegiatan', 'public');
        }

        // ✅ CREATE KEGIATAN
        $kegiatan = Kegiatan::create([
            'judul' => $request->judul,
            'slug' => $this->generateSlug($request->judul),
            'deskripsi' => $request->deskripsi,
            'jenjang' => $request->jenjang,
            'gambar' => $mainImagePath,
            'is_active' => 1,
        ]);

        // ✅ MULTIPLE IMAGES (SLIDER)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $imgPath = $img->store('kegiatan', 'public');

                KegiatanImage::create([
                    'kegiatan_id' => $kegiatan->id,
                    'image' => $imgPath,
                ]);
            }
        }

        return response()->json(['message' => 'Berhasil']);
    }

    public function show(Kegiatan $kegiatan)
    {
        $this->authorize('manage', $kegiatan);

        return response()->json(
            $kegiatan->load('images')
        );
    }

    public function showBySlug($slug)
    {
        $kegiatan = Kegiatan::with('images')
            ->where('slug', $slug)
            ->firstOrFail();

        $allImages = [];

        // cover dulu
        if ($kegiatan->gambar) {
            $allImages[] = $kegiatan->gambar;
        }

        // gallery
        foreach ($kegiatan->images as $img) {
            $allImages[] = $img->image;
        }

        return response()->json([
            'id' => $kegiatan->id,
            'judul' => $kegiatan->judul,
            'slug' => $kegiatan->slug,
            'deskripsi' => $kegiatan->deskripsi,
            'jenjang' => $kegiatan->jenjang,
            'is_active' => $kegiatan->is_active,
            'created_at' => $kegiatan->created_at,
            'images' => $allImages,
        ]);
    }

    public function update(Request $request, Kegiatan $kegiatan)
    {
        $user = request()->user();

        // 🔥 VALIDASI ROLE
        if (
            ($user->role === 'adminMI' && $kegiatan->jenjang !== 'MI') ||
            ($user->role === 'adminMTS' && $kegiatan->jenjang !== 'MTS') ||
            ($user->role === 'adminMA' && $kegiatan->jenjang !== 'MA') ||
            ($user->role === 'admin' && $kegiatan->jenjang !== 'YAYASAN')
        ) {
            abort(403, 'Tidak punya akses ke kegiatan ini');
        }

        $this->authorize('manage', $kegiatan);

        $request->validate([
            'judul' => 'required',
            'deskripsi' => 'required',
            'jenjang' => 'required|in:MI,MTS,MA,YAYASAN',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'gambar.max' => 'Ukuran gambar maksimal 2 MB',
            'gambar.image' => 'File harus berupa gambar',
            'gambar.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp',
        ]);

        // ✅ HANDLE GAMBAR UTAMA
        $mainImagePath = $kegiatan->gambar;

        if ($request->hasFile('gambar')) {

            // hapus lama
            if ($kegiatan->gambar && Storage::disk('public')->exists($kegiatan->gambar)) {
                Storage::disk('public')->delete($kegiatan->gambar);
            }

            // simpan baru
            $mainImagePath = $request->file('gambar')->store('kegiatan', 'public');
        }

        // 🔥 ambil id gambar lama yg masih dipakai
        $keepIds = $request->input('old_images', []);

        // ✅ HAPUS GAMBAR YANG TIDAK DIPILIH
        foreach ($kegiatan->images as $img) {
            if (!in_array($img->id, $keepIds)) {

                if (Storage::disk('public')->exists($img->image)) {
                    Storage::disk('public')->delete($img->image);
                }

                $img->delete();
            }
        }

        // ✅ TAMBAH GAMBAR BARU (kalau ada)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $imgPath = $img->store('kegiatan', 'public');

                KegiatanImage::create([
                    'kegiatan_id' => $kegiatan->id,
                    'image' => $imgPath,
                ]);
            }
        }

        if ($request->remove_cover) {

            if ($kegiatan->gambar && Storage::disk('public')->exists($kegiatan->gambar)) {
                Storage::disk('public')->delete($kegiatan->gambar);
            }

            $mainImagePath = null; // 🔥 FIX DI SINI
        }

        $kegiatan->update([
            'judul' => $request->judul,
            'slug' => $this->generateSlug($request->judul, $kegiatan->id),
            'deskripsi' => $request->deskripsi,
            'jenjang' => $request->jenjang,
            'gambar' => $mainImagePath,
            'is_active' => $request->is_active ?? $kegiatan->is_active,
        ]);

        if (!$kegiatan->gambar) {
            $firstImage = $kegiatan->images()->first();

            if ($firstImage) {
                $kegiatan->update([
                    'gambar' => $firstImage->image
                ]);
            }
        }

        return response()->json([
            'message' => 'Berhasil update',
            'data' => $kegiatan
        ]);
    }


    public function destroy(Kegiatan $kegiatan)
    {
        $user = request()->user();
        if (
            ($user->role === 'adminMI' && $kegiatan->jenjang !== 'MI') ||
            ($user->role === 'adminMTS' && $kegiatan->jenjang !== 'MTS') ||
            ($user->role === 'adminMA' && $kegiatan->jenjang !== 'MA') ||
            ($user->role === 'admin' && $kegiatan->jenjang !== 'YAYASAN')
        ) {
            abort(403, 'Tidak punya akses ke kegiatan ini');
        }

        $this->authorize('manage', $kegiatan);

        if ($kegiatan->gambar && Storage::disk('public')->exists($kegiatan->gambar)) {
            Storage::disk('public')->delete($kegiatan->gambar);
        }

        foreach ($kegiatan->images as $img) {
            if (Storage::disk('public')->exists($img->image)) {
                Storage::disk('public')->delete($img->image);
            }
            $img->delete();
        }

        $kegiatan->delete();

        return response()->json(['message' => 'Kegiatan deleted']);
    }

    public function publicList()
    {
        $data = Kegiatan::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('jenjang')
            ->map(function ($items) {
                return $items->sortByDesc('created_at')->values();
            });

        return response()->json($data);
    }
}
