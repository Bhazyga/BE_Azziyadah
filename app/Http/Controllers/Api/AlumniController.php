<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Alumni;
use Illuminate\Support\Facades\Storage;

class AlumniController extends Controller
{

    // ADMIN

    public function index(Request $request)
    {
        $query = Alumni::query();

        if ($request->search) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        if ($request->angkatan) {
            $query->where('angkatan', $request->angkatan);
        }

        $alumni = $query->latest()->paginate(5);

        $angkatans = Alumni::select('angkatan')
            ->distinct()
            ->orderBy('angkatan', 'desc')
            ->pluck('angkatan');

        return response()->json([
            'data' => $alumni->items(),
            'current_page' => $alumni->currentPage(),
            'last_page' => $alumni->lastPage(),
            'from' => $alumni->firstItem(),
            'to' => $alumni->lastItem(),
            'total' => $alumni->total(),
            'prev_page_url' => $alumni->previousPageUrl(),
            'next_page_url' => $alumni->nextPageUrl(),
            'angkatans' => $angkatans
        ]);
    }

    // public function index()
    // {
    //     // return Alumni::orderBy('angkatan', 'desc')->get();
    //     return Alumni::latest()->paginate(5);
    // }


    // PUBLIC
    public function publicIndex()
    {
        $alumni = Alumni::orderBy('angkatan', 'desc')
            ->paginate(30);

        return response()->json($alumni);
    }


    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'angkatan' => 'required|string|max:20',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('alumni', 'public');
        }

        $alumni = Alumni::create([
            'nama' => $request->nama,
            'angkatan' => $request->angkatan,
            'image' => $imagePath,
        ]);

        return response()->json([
            'message' => 'Alumni berhasil ditambahkan',
            'data' => $alumni
        ], 201);
    }


    public function show($id)
    {
        return Alumni::findOrFail($id);
    }


    public function update(Request $request, $id)
    {
        $alumni = Alumni::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:255',
            'angkatan' => 'required|string|max:20',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('image')) {

            if ($alumni->image && Storage::disk('public')->exists($alumni->image)) {
                Storage::disk('public')->delete($alumni->image);
            }

            $alumni->image = $request->file('image')->store('alumni', 'public');
        }

        $alumni->update([
            'nama' => $request->nama,
            'angkatan' => $request->angkatan,
        ]);

        return response()->json([
            'message' => 'Alumni berhasil diupdate',
            'data' => $alumni
        ]);
    }


    public function destroy($id)
    {
        $alumni = Alumni::findOrFail($id);

        if ($alumni->image && Storage::disk('public')->exists($alumni->image)) {
            Storage::disk('public')->delete($alumni->image);
        }

        $alumni->delete();

        return response()->json([
            'message' => 'Alumni berhasil dihapus'
        ]);
    }
}
