<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $query = Gallery::with('images');

        if ($request->search) {
            $query->where('judul', 'like', '%' . $request->search . '%');
        }

        return response()->json(
            $query->latest()->paginate(10)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal' => 'required|date',

            'images' => 'required|array|min:1|max:5',

            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096'
            ],

        ], [
            'images.required' => 'Minimal upload 1 gambar.',
            'images.array' => 'Format gambar tidak valid.',
            'images.min' => 'Minimal upload 1 gambar.',
            'images.max' => 'Maksimal 5 gambar.',

            'images.*.image' => 'File harus berupa gambar.',
            'images.*.mimes' => 'Format gambar wajib jpg, jpeg, png, atau webp.',
            'images.*.max' => 'Ukuran tiap gambar maksimal 4MB.',
        ]);

        DB::beginTransaction();

        try {

            $gallery = Gallery::create([
                'judul' => $request->judul,
                'deskripsi' => $request->deskripsi,
                'tanggal' => $request->tanggal,
            ]);

            foreach ($request->file('images') as $index => $img) {

                $path = $img->store('gallery', 'public');

                $gallery->images()->create([
                    'path' => $path,
                    'is_cover' => $index === 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Gallery berhasil ditambahkan',
                'data' => $gallery->load('images')
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menyimpan gallery',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $gallery = Gallery::with('images')->findOrFail($id);

        return response()->json($gallery);
    }

    public function update(Request $request, $id)
    {
        $gallery = Gallery::with('images')->findOrFail($id);

        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal' => 'required|date',

            'images' => 'nullable|array|max:5',

            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096'
            ],

            'old_images' => 'nullable|array',
            'old_images.*' => 'exists:gallery_images,id',

        ], [
            'images.max' => 'Maksimal 5 gambar.',
            'images.*.image' => 'File harus berupa gambar.',
            'images.*.mimes' => 'Format gambar wajib jpg, jpeg, png, atau webp.',
            'images.*.max' => 'Ukuran tiap gambar maksimal 4MB.',
        ]);

        DB::beginTransaction();

        try {

            $gallery->update([
                'judul' => $request->judul,
                'deskripsi' => $request->deskripsi,
                'tanggal' => $request->tanggal,
            ]);

            $keepIds = $request->old_images ?? [];

            $deletedImages = $gallery->images()
                ->whereNotIn('id', $keepIds)
                ->get();

            foreach ($deletedImages as $img) {

                if (
                    $img->path &&
                    Storage::disk('public')->exists($img->path)
                ) {
                    Storage::disk('public')->delete($img->path);
                }

                $img->delete();
            }

            if ($request->hasFile('images')) {

                foreach ($request->file('images') as $img) {

                    $path = $img->store('gallery', 'public');

                    $gallery->images()->create([
                        'path' => $path,
                        'is_cover' => false,
                    ]);
                }
            }

            $gallery->refresh();

            if ($gallery->images()->count() === 0) {

                DB::rollBack();

                return response()->json([
                    'message' => 'Minimal harus ada 1 gambar.'
                ], 422);
            }

            $gallery->images()->update([
                'is_cover' => false
            ]);

            $firstImage = $gallery->images()
                ->oldest()
                ->first();

            if ($firstImage) {
                $firstImage->update([
                    'is_cover' => true
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Gallery berhasil diupdate',
                'data' => $gallery->load('images')
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal update gallery',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $gallery = Gallery::with('images')
            ->findOrFail($id);

        DB::beginTransaction();

        try {

            foreach ($gallery->images as $img) {

                if (
                    $img->path &&
                    Storage::disk('public')->exists($img->path)
                ) {
                    Storage::disk('public')->delete($img->path);
                }
            }

            $gallery->images()->delete();
            $gallery->delete();
            DB::commit();

            return response()->json([
                'message' => 'Gallery berhasil dihapus'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus gallery',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
