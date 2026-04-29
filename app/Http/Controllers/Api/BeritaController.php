<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class BeritaController extends Controller
{
    // ================= PUBLIC =================
    public function publicIndex()
    {
        return Berita::where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->get();
    }

    // ================= ADMIN =================
    public function index()
    {
        return Berita::orderBy('created_at', 'desc')->get();
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
            'content' => 'required',
            'category' => 'required|in:MA,MTS,MI',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4048',
        ]);

        $slug = Str::slug($request->title);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('berita', 'public');
        }

        $berita = Berita::create([
            'title' => $request->title,
            'slug' => $slug,
            'content' => $request->content,
            'category' => $request->category,
            'published_at' => $request->published_at,
            'is_published' => $request->is_published ?? false,
            'image' => $imagePath,
        ]);

        return response()->json([
            'message' => 'Berita berhasil ditambahkan',
            'data' => $berita
        ], 201);
    }

    public function showById($id)
    {
        return Berita::findOrFail($id);
    }

    public function showBySlug($slug)
    {
        return Berita::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
    }


    public function update(Request $request, $id)
    {
        $berita = Berita::findOrFail($id);

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
            'content' => 'required',
            'category' => 'required|in:MA,MTS,MI',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $slug = Str::slug($request->title);

        if ($request->hasFile('image')) {
            if ($berita->image && Storage::disk('public')->exists($berita->image)) {
                Storage::disk('public')->delete($berita->image);
            }

            $berita->image = $request->file('image')->store('berita', 'public');
        }

        $berita->update([
            'title' => $request->title,
            'slug' => $slug,
            'content' => $request->content,
            'category' => $request->category,
            'published_at' => $request->published_at,
            'is_published' => $request->is_published ?? false,
        ]);

        return response()->json([
            'message' => 'Berita berhasil diupdate',
            'data' => $berita
        ]);
    }


    public function destroy($id)
    {
        $berita = Berita::findOrFail($id);

        if ($berita->image && Storage::disk('public')->exists($berita->image)) {
            Storage::disk('public')->delete($berita->image);
        }

        $berita->delete();

        return response()->json([
            'message' => 'Berita berhasil dihapus'
        ]);
    }
}
