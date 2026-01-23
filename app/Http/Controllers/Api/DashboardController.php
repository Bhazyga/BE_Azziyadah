<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_keuangan' => DB::table('transactions')
                ->where('status', 'success')
                ->sum('total_harga'),

            // Santri yang sudah diterima adalah yang sudah memiliki akun user
            'total_santri_diterima' => DB::table('users')
                // ->whereNotNull('santri_id')
                // ->distinct('santri_id')
                ->count(),

            // Santri mendaftar = santri yang belum punya akun user
            'total_pendaftar' => DB::table('santris')
                ->whereNotIn('id', function ($query) {
                    // $query->select('santri_id')->from('users')->whereNotNull('santri_id');
                })
                ->count(),

            'belum_bayar' => $this->hitungPersentaseBelumBayar()
        ];

        $chart = DB::table('transactions')
            ->selectRaw('MONTHNAME(transaction_time) as bulan')
            ->selectRaw('COUNT(*) as total_transaksi')
            ->selectRaw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as diterima')
            ->selectRaw('SUM(CASE WHEN status = "success" THEN total_harga ELSE 0 END) as pemasukan')
            ->whereYear('transaction_time', now()->year)
            ->groupBy('bulan')
            ->orderByRaw('MONTH(transaction_time)')
            ->get();

        return response()->json([
            'stats' => $stats,
            'chart' => $chart,
        ]);
    }

    private function hitungPersentaseBelumBayar()
    {
        $total = DB::table('transactions')->count();
        $belum = DB::table('transactions')
            ->where('status', '!=', 'success')
            ->count();

        return $total > 0 ? round(($belum / $total) * 100, 2) : 0;
    }
}
