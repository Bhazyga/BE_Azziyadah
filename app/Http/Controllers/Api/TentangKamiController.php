<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\TentangKami;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class TentangKamiController extends Controller
{
    // ================= PUBLIC =================
    public function publicIndex()
    {
        return TentangKami::orderBy('created_at', 'asc')
            ->get();
    }

    // ================= ADMIN =================
    public function index()
    {
        return TentangKami::orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
    {
        if ($request->user()->role === 'adminMI') {
            $request->merge(['category' => 'MI']);
        }
        if ($request->user()->role === 'adminMTS') {
            $request->merge(['category' => 'MTS']);
        }
        if ($request->user()->role === 'adminMA') {
            $request->merge(['category' => 'MA']);
        }


        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4048',
        ], [
            'title.required' => 'Judul wajib diisi.',
            'description.required' => 'Deskripsi wajib diisi.',

            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp disarankan webp.',
            'image.max' => 'Ukuran gambar maksimal 4MB.',
        ]);


        $slug = Str::slug($request->title);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('tentangkami', 'public');
        }

        $tentangkami = TentangKami::create([
            'title' => $request->title,
            'slug' => $slug,
            'description' => $request->description,
            'image' => $imagePath,
        ]);

        return response()->json([
            'message' => 'Tentang Kami berhasil ditambahkan',
            'data' => $tentangkami
        ], 201);
    }

    public function showById($id)
    {
        return TentangKami::findOrFail($id);
    }

    public function showBySlug($slug)
    {
        return TentangKami::where('slug', $slug)
            ->firstOrFail();
    }


    public function update(Request $request, $id)
    {
        $tentangkami = TentangKami::findOrFail($id);

        if ($request->user()->role === 'adminMI') {
            $request->merge(['category' => 'MI']);
        }
        if ($request->user()->role === 'adminMTS') {
            $request->merge(['category' => 'MTS']);
        }
        if ($request->user()->role === 'adminMA') {
            $request->merge(['category' => 'MA']);
        }

        $request->merge([
            'category' => strtoupper(trim($request->category))
        ]);

        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:4048',
        ]);

        $slug = Str::slug($request->title);

        if ($request->hasFile('image')) {
            if ($tentangkami->image && Storage::disk('public')->exists($tentangkami->image)) {
                Storage::disk('public')->delete($tentangkami->image);
            }

            $tentangkami->image = $request->file('image')->store('tentangkami', 'public');
        }

        $tentangkami->update([
            'title' => $request->title,
            'slug' => $slug,
            'description' => $request->description,
            'image' => $request->image,
        ]);

        return response()->json([
            'message' => 'Tentang Kami berhasil diupdate',
            'data' => $tentangkami
        ]);
    }


    public function destroy($id)
    {
        $tentangkami = TentangKami::findOrFail($id);

        if ($tentangkami->image && Storage::disk('public')->exists($tentangkami->image)) {
            Storage::disk('public')->delete($tentangkami->image);
        }

        $tentangkami->delete();

        return response()->json([
            'message' => 'Tentang Kami berhasil dihapus'
        ]);
    }
}
