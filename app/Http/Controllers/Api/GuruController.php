<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Guru;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class GuruController extends Controller
{

    // ================= PUBLIC =================
    public function publicIndex()
    {
        // return Guru::where('created_at', true)
        //     ->orderBy('published_at', 'desc')
        //     ->get();
        return Guru::orderBy('created_at', 'desc')->get();
    }

    // ================= ADMIN =================
    public function index(Request $request)
    {
        $query = Guru::query();

        if ($request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return $query->paginate(10);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'experience' => 'required|string',
            'bio' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        $slug = Str::slug($request->name);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('guru', 'public');
        }

        $guru = Guru::create([
            'name' => $request->name,
            'slug' => $slug,
            'subject' => $request->subject,
            'experience' => $request->experience,
            'bio' => $request->bio,
            'image' => $imagePath,
        ]);

        return response()->json([
            'message' => 'Guru berhasil ditambahkan',
            'data' => $guru
        ], 201);
    }

    public function showById($id)
    {
        return Guru::findOrFail($id);
    }

    public function showBySlug($slug)
    {
        return Guru::where('slug', $slug)
            ->firstOrFail();
    }


    public function update(Request $request, $id)
    {
        $guru = Guru::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'subject' => 'required',
            'experience' => 'required',
            'bio' => 'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096'
        ]);

        $slug = Str::slug($request->name);


        if ($request->hasFile('image')) {
            if ($guru->image && Storage::disk('public')->exists($guru->image)) {
                Storage::disk('public')->delete($guru->image);
            }

            $guru->image = $request->file('image')->store('guru', 'public');
        }

        $guru->update([
            'name' => $request->name,
            'slug' => $slug,
            'subject' => $request->subject,
            'experience' => $request->experience,
            'bio' => $request->bio
        ]);

        return response()->json([
            'message' => 'Guru berhasil diupdate',
            'data' => $guru
        ]);
    }


    public function destroy($id)
    {
        $guru = Guru::findOrFail($id);

        if ($guru->image && Storage::disk('public')->exists($guru->image)) {
            Storage::disk('public')->delete($guru->image);
        }

        $guru->delete();

        return response()->json([
            'message' => 'Guru berhasil dihapus'
        ]);
    }

}
