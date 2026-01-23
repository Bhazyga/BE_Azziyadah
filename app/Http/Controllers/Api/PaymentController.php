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

        // Ambil item dari DB
        $item = Item::find($request->item_id);

        if (!$item || !$item->aktif) {
            return response()->json(['message' => 'Item tidak valid'], 400);
        }

        if ($request->item_name !== $item->nama) {
            return response()->json(['message' => 'Nama item tidak sesuai'], 400);
        }

        $amount = $item->harga;

        DB::beginTransaction();

        try {
            // FORMAT ORDER ID: FORM-{user_id}-{item_id}-{uniqid}
            $orderId = 'FORM-' . $user->id . '-' . $item->id . '-' . uniqid();

            $payload = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount,
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
                    // Prod
                    'finish' => 'https://www.azziyadahklender.id/payment-finish',

                    // Local
                    // 'finish' => 'http://localhost:3000/payment-finish',
                ],
            ];

            $snapToken = Snap::getSnapToken($payload);

            DB::commit();

            return response()->json([
                'snap_token' => $snapToken,
                'order_id'   => $orderId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal membuat Snap Token',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /* =====================================================
    * MIDTRANS NOTIFICATION
    * ===================================================== */
    public function handleNotification(Request $request)
    {
        Log::info('Incoming Midtrans Notification Payload:', $request->all());

        Config::$serverKey    = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;

        try {
            $notif = new SafeNotification();

            $orderId            = $notif->order_id ?? null;
            $transactionId     = $notif->transaction_id ?? null;
            $transactionStatus = $notif->transaction_status ?? null;
            $paymentType       = $notif->payment_type ?? null;
            $grossAmount       = $notif->gross_amount ?? 0;
            $transactionTime   = $notif->transaction_time ?? now();
            $paymentTime       = $notif->settlement_time ?? null;
            $fullResponse      = json_encode($notif);

            // FORMAT: FORM-userId-itemId-uniqid
            $orderParts = explode('-', $orderId);

            $userId = $orderParts[1] ?? null;
            $itemId = $orderParts[2] ?? null;

            Log::info('ORDER PARSED', [
                'order_id' => $orderId,
                'user_id'  => $userId,
                'item_id'  => $itemId,
            ]);

            // Pastikan user ada
            $user = User::find($userId);

            if (!$user) {
                Log::error('USER NOT FOUND FROM NOTIFICATION', ['user_id' => $userId]);
                return response()->json(['message' => 'User not found'], 404);
            }

            // =====================================================
            // CREATE / UPDATE TRANSACTION
            // =====================================================
            $transaction = Transaction::where('midtrans_order_id', $orderId)->first();

            if (!$transaction) {
                // CREATE BARU
                $transaction = Transaction::create([
                    'user_id'            => $userId,
                    'item_id'            => $itemId,
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

                Log::info('TRANSACTION CREATED', [
                    'transaction_id' => $transaction->id,
                    'status'         => $transactionStatus
                ]);

            } else {
                // UPDATE STATUS TERBARU
                $transaction->update([
                    'status'            => $transactionStatus,
                    'payment_type'      => $paymentType,
                    'payment_time'      => $paymentTime,
                    'midtrans_response' => $fullResponse,
                ]);

                Log::info('TRANSACTION UPDATED', [
                    'transaction_id' => $transaction->id,
                    'status'         => $transactionStatus
                ]);
            }

            // =====================================================
            // 🔥 PROSES SANTRI HANYA JIKA PEMBAYARAN SUKSES
            // =====================================================
            if (in_array($transactionStatus, ['settlement', 'capture'])) {

                Log::info('PAYMENT SUCCESS, PROCESS SANTRI', [
                    'user_id' => $userId,
                    'status'  => $transactionStatus
                ]);

                // CEK APAKAH USER SUDAH PUNYA SANTRI
                $santri = Santri::where('user_id', $userId)->first();

                if (!$santri) {

                    $santri = Santri::create([
                        'user_id' => $userId,
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
                        'status' => 1,
                    ]);

                    Log::info('SANTRI CREATED AFTER PAYMENT', [
                        'santri_id' => $santri->id,
                        'user_id'   => $userId,
                    ]);

                } else {

                    if ($santri->status < 1) {
                        $santri->update(['status' => 1]);

                        Log::info('SANTRI STATUS UPDATED TO ACTIVE', [
                            'santri_id' => $santri->id,
                        ]);
                    } else {
                        Log::info('SANTRI ALREADY ACTIVE', [
                            'santri_id' => $santri->id,
                        ]);
                    }
                }
            }

            return response()->json(['message' => 'Notification handled'], 200);

        } catch (\Exception $e) {
            Log::error('Midtrans Notification Error: ' . $e->getMessage());
            return response()->json(['message' => 'Notification error'], 500);
        }
    }




    /* =====================================================
     * USER TRANSACTIONS
     * ===================================================== */
    public function getPendingTransactions(Request $request)
    {
        $userId = $request->user()->id;

        $pendingTransactions = Transaction::with('item')
            ->where('user_id', $userId)
            ->whereNotIn('status', ['paid', 'success', 'settlement'])
            ->orderBy('transaction_time', 'desc')
            ->get();

        return response()->json($pendingTransactions);
    }

    public function getUserTransactions(Request $request)
    {
        $userId = $request->user()->id;

        $transactions = Transaction::with('item')
            ->where('user_id', $userId)
            ->orderBy('transaction_time', 'desc')
            ->get();

        return response()->json($transactions);
    }

    public function getUnpaidItems(Request $request)
    {
        $userId = $request->user()->id;

        $unpaidItems = Item::where('aktif', 1)
            ->whereNotIn('id', function ($query) use ($userId) {
                $query->select('item_id')
                    ->from('transactions')
                    ->where('user_id', $userId);
            })
            ->get();

        return response()->json($unpaidItems);
    }

}
