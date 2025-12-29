<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;
use Midtrans\Config;

class CheckMidtransPending extends Command
{
    protected $signature = 'midtrans:check-pending';
    protected $description = 'Cek transaksi pending di Midtrans dan update jika sudah selesai';

    public function handle()
    {
        // Set Midtrans server key dari config
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$serverKey = config('midtrans.server_key');

        // Ambil semua transaksi pending
        $transaksis = Transaction::where('status', 'pending')->get();

        if ($transaksis->isEmpty()) {
            $this->info("Tidak ada transaksi pending.");
            return Command::SUCCESS;
        }

        foreach ($transaksis as $trx) {
            try {
                $response = Http::withBasicAuth(Config::$serverKey, '')
                    ->get("https://api.sandbox.midtrans.com/v2/{$trx->midtrans_order_id}/status");

                if ($response->successful()) {
                    $status = $response['transaction_status'];

                    if ($status === 'settlement') {
                        $trx->status = 'success';
                        $trx->save();
                        $this->info("Order {$trx->order_id} berhasil diselesaikan (SUCCESS).");
                    } elseif (in_array($status, ['cancel', 'expire', 'deny'])) {
                        $trx->status = 'failed';
                        $trx->save();
                        $this->info("Order {$trx->order_id} gagal (FAILED) karena status: {$status}.");
                    } else {
                        $this->info("Order {$trx->order_id} status saat ini: {$status} (tidak diubah).");
                    }
                } else {
                    $this->error("Gagal menghubungi Midtrans untuk order {$trx->order_id}. Status HTTP: " . $response->status());
                }
            } catch (\Exception $e) {
                $this->error("Terjadi error saat memproses order {$trx->order_id}: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
