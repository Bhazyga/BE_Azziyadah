<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use Illuminate\Http\Request;

class AdminSantriController extends Controller
{
    /**
     * LIST santri sesuai policy (admin / adminMI / adminMTS / adminMA)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Santri::with('grade');

        if ($user->role !== 'admin') {
            $prefix = match ($user->role) {
                'adminMI'  => 'MI',
                'adminMTS' => 'MTS',
                'adminMA'  => 'MA',
            };

            $query->whereHas('grade', fn ($q) =>
                $q->where('nama_kelas', 'LIKE', "$prefix%")
            );
        }

        return response()->json($query->paginate(15));
    }

    /**
     * DETAIL santri (policy aware)
     */
    public function show(Santri $santri)
    {
        $this->authorize('view', $santri);

        return response()->json($santri->load('grade'));
    }

    /**
     * UPDATE data santri oleh admin
     */
    public function update(Request $request, Santri $santri)
    {
        $this->authorize('update', $santri);

        $santri->update($request->all());

        return response()->json(['message' => 'Santri updated']);
    }
}
