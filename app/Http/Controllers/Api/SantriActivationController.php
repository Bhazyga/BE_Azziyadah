<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Http\Request;

class SantriActivationController extends Controller
{
    public function activate(Request $request, Santri $santri)
    {
        if (!in_array(auth()->user()->role, ['admin', 'adminMI', 'adminMTS', 'adminMA'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'grade_id' => 'required|exists:grades,id',
        ]);

        $santri->update([
            'grade_id' => $request->grade_id,
            'status'   => 2,
        ]);

        return response()->json([
            'message' => 'Santri berhasil diaktifkan',
            'santri'  => $santri->load('grade'),
        ]);
    }
}
