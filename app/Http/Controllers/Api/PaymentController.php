<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Santri;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;
use App\Services\Midtrans\SafeNotification;


class PaymentController extends Controller
{
    public function __construct()
    {
        // Set konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function getSnapToken(Request $request)
    {
        $request->validate([
            'user_name'  => 'required|string',
            'user_email' => 'required|email',
            'item_name'  => 'required|string',
            'item_id'    => 'required|numeric',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ambil data item dari database
        $item = Item::find($request->item_id);

        if (!$item || !$item->aktif) {
            return response()->json(['message' => 'Item tidak valid'], 400);
        }

        // Validasi nama item agar sesuai dengan item di DB
        if ($request->item_name !== $item->nama) {
            return response()->json(['message' => 'Nama item tidak sesuai'], 400);
        }

        $amount = $item->harga; // ambil harga dari DB, bukan dari request

        DB::beginTransaction();

        try {
            // Pastikan santri ada
            if (!$user->santri_id) {
                $santri = Santri::create([
                    'user_id' => $user->id,
                    'nama_lengkap' => $user->name,
                    'tempat_lahir' => '-',
                    'tanggal_lahir' => now(),
                    'jenis_kelamin' => '-',
                    'alamat_santri' => '-',
                    'provinsi_santri' => '-',
                    'kota_kabupaten_santri' => '-',
                    'nama_ayah' => '-',
                    'telepon_ayah' => '-',
                    'nama_ibu' => '-',
                    'telepon_ibu' => '-',
                    'alamat_ortu' => '-',
                    'nama_sekolah_asal' => '-',
                    'jenjang_pendidikan_terakhir' => '-',
                    'alamat_sekolah_asal' => '-',
                    'status' => 0,
                ]);

                $user->update(['santri_id' => $santri->id]);
            } else {
                $santri = Santri::findOrFail($user->santri_id);
            }

            $orderId = 'FORM-' . $santri->id . '-' . $user->id . '-' . $item->id . '-' . uniqid();

            $payload = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount, // pastikan pakai harga DB
                ],
                'customer_details' => [
                    'first_name' => $request->user_name,
                    'email' => $request->user_email,
                ],
                'item_details' => [
                    [
                        'id' => $item->id,
                        'price' => $amount,
                        'quantity' => 1,
                        'name' => $item->nama,
                    ]
                ],
                'callbacks' => [
                    // prod
                    // 'finish' => 'https://www.azziyadahklender.id/user/payment-finish',

                    // local
                    'finish' => 'http://localhost:3000/payment-finish',
                ],
            ];

            $snapToken = Snap::getSnapToken($payload);

            DB::commit();

            return response()->json([
                'snap_token' => $snapToken,
                'santri_id' => $santri->id,
                'order_id' => $orderId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal membuat Snap Token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    public function handleNotification(Request $request)
    {
        Log::info('Incoming Midtrans Notification Payload:', $request->all());

        // Setup Midtrans config lagi (jaga-jaga kalau belum di-boot)
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        try {
            $notif = new SafeNotification();
            $orderId = $notif->order_id ?? null;
            $transactionId = $notif->transaction_id ?? null;
            $transactionStatus = $notif->transaction_status ?? null;
            $paymentType = $notif->payment_type ?? null;
            $grossAmount = $notif->gross_amount ?? 0;
            $transactionTime = $notif->transaction_time ?? now();
            $paymentTime = $notif->settlement_time ?? null;
            $statusCode = $notif->status_code ?? null;
            $fullResponse = json_encode($notif);

            $orderParts = explode('-', $orderId);

            $santriId = $orderParts[1] ?? null;
            $userId   = $orderParts[2] ?? null;
            $itemId   = $orderParts[3] ?? null;

            Log::info('ORDER PARSED', [
                'order_id' => $orderId,
                'santri_id' => $santriId,
                'user_id' => $userId,
                'item_id' => $itemId,
            ]);


            // 🔥 TAMBAHKAN KODE SYNC USER ↔ SANTRI DI SINI
            $user = User::find($userId);

            if ($user) {
                Log::info('USER FOUND IN NOTIFICATION', [
                    'user_id' => $user->id,
                    'current_santri_id' => $user->santri_id,
                ]);

                if (!$user->santri_id) {
                    $user->update([
                        'santri_id' => $santriId,
                    ]);

                    Log::info('USER SANTRI_ID UPDATED FROM NOTIFICATION', [
                        'user_id' => $user->id,
                        'santri_id' => $santriId,
                    ]);
                }
            } else {
                Log::error('USER NOT FOUND FROM NOTIFICATION', [
                    'user_id' => $userId,
                ]);
            }



            // Cek apakah transaksi ini sudah ada (hindari duplikasi)
            $existing = Transaction::where('midtrans_order_id', $orderId)->first();

            // update / create transaction
            if (!$existing) {
                Transaction::create([
                    'santri_id'         => $santriId,
                    'item_id'           => $itemId,
                    'transaction_id'    => $transactionId,
                    'midtrans_order_id' => $orderId,
                    'jumlah'            => 1,
                    'total_harga'       => $grossAmount,
                    'status'            => $transactionStatus,
                    'payment_type'      => $paymentType,
                    'midtrans_response' => $fullResponse,
                    'transaction_time'  => $transactionTime,
                    'payment_time'      => $paymentTime,
                ]);
            } else {
                $existing->update([
                    'status'            => $transactionStatus,
                    'payment_type'      => $paymentType,
                    'payment_time'      => $paymentTime,
                    'midtrans_response' => $fullResponse,
                ]);
            }

            // 🔥 UPDATE SANTRI STATUS HARUS DI SINI
            if (in_array($transactionStatus, ['settlement', 'capture'])) {
                $santri = Santri::find($santriId);

                if ($santri && $santri->status < 1) {
                    $santri->update([
                        'status' => 1, // FORM PAID
                    ]);
                }
            }


            return response()->json(['message' => 'Notification handled'], 200);

        } catch (\Exception $e) {
            Log::error('Midtrans Notification Error: ' . $e->getMessage());
            return response()->json(['message' => 'Notification error'], 500);
        }
    }

    public function getPendingTransactions(Request $request)
    {
        $santriId = $request->user()->santri_id;

        $pendingTransactions = Transaction::with('item')
            ->where('santri_id', $santriId)
            ->whereNotIn('status', ['paid', 'success', 'settlement'])
            ->orderBy('transaction_time', 'desc')
            ->get();

        return response()->json($pendingTransactions);
    }


    public function getUserTransactions(Request $request)
    {
        $santriId = $request->user()->santri_id;
        $transactions = Transaction::with('item')
            ->where('santri_id', $santriId)
            ->orderBy('transaction_time', 'desc')
            ->get();

        return response()->json($transactions);
    }

    public function getUnpaidItems(Request $request)
    {
        $santriId = $request->user()->santri_id;

        $unpaidItems = Item::where('aktif', 1)
            ->whereNotIn('id', function ($query) use ($santriId) {
                $query->select('item_id')
                    ->from('transactions')
                    ->where('santri_id', $santriId);
            })
            ->get();

        return response()->json($unpaidItems);
    }



}
