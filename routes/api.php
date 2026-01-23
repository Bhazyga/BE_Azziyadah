<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SantriController;
use App\Http\Controllers\Api\SantriActivationController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AdminSantriController;
use App\Http\Controllers\api\BeritaController;
use App\Http\Controllers\Api\KegiatanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Semua route untuk API disusun berdasarkan fungsinya:
| - Auth & User
| - Santri (biodata, unpaid items)
| - Payment (Midtrans)
| - Admin & master data (grades, items, teams, dll)
| Semua route yang sensitif pakai middleware auth:sanctum
|--------------------------------------------------------------------------
*/


// ===============================
// 🧑 AUTH & USER
// ===============================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/index', [AuthController::class,'index']); // (??) optional login?
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user());
    Route::apiResource('/users', UserController::class);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/belum-aktif', [UserController::class, 'santriBelumAktif']); // List santri belum aktif
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

// ===============================
// 🧕 SANTRI
// ===============================
Route::apiResource('/santris', SantriController::class);
// Route::get('/santris/{id}', [SantriController::class, 'biodata']);
Route::get('/santris/{id}/unpaid-items', [SantriController::class, 'getUnpaidItems']); // Unpaid by Santri ID

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/activate-santri/{santri}', [SantriActivationController::class, 'activate']);
});


// ===============================
// 💸 PAYMENT (Midtrans)
// ===============================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/transactions/token', [PaymentController::class, 'getSnapToken']);
});

Route::post('/midtrans/notification', [PaymentController::class, 'handleNotification']); // Callback URL

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-transactions', [PaymentController::class, 'getUserTransactions']);
    Route::get('/pending-transactions', [PaymentController::class, 'getPendingTransactions']);
    Route::get('/unpaid-items', [PaymentController::class, 'getUnpaidItems']);
    Route::get('/all-transactions', [TransaksiController::class, 'allTransactions']);
});

Route::get('/transactions/{id}', [TransaksiController::class, 'show']); // detail transaksi

// ===============================
// 📊 DASHBOARD
// ===============================
Route::get('/dashboard-stats', [DashboardController::class, 'index']);

// ===============================
// 📦 ITEMS, MATERIALS, TEAMS
// ===============================
Route::apiResource('/items', ItemController::class);
Route::apiResource('/materials', MaterialController::class);
Route::get('/materials/{id}', [MaterialController::class, 'show']);

Route::apiResource('/teams', TeamController::class);
Route::get('/teams/{id}', [TeamController::class, 'show']);

// ===============================
// 🏫 GRADES
// ===============================
Route::prefix('grades')->group(function () {
    Route::get('/', [GradeController::class, 'index']);
    Route::post('/', [GradeController::class, 'store']);
    Route::get('{id}', [GradeController::class, 'show']);
    Route::put('{id}', [GradeController::class, 'update']);
    Route::delete('{id}', [GradeController::class, 'destroy']);
});


Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/santris', [AdminSantriController::class, 'index']);
    Route::get('/santris/{santri}', [AdminSantriController::class, 'show']);
    Route::put('/santris/{santri}', [AdminSantriController::class, 'update']);
});

Route::middleware('auth:sanctum')->get('/my-santri', [SantriController::class, 'byUser']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/kegiatan', [KegiatanController::class, 'index']);
    Route::post('/admin/kegiatan', [KegiatanController::class, 'store']);
    Route::get('/admin/kegiatan/{kegiatan}', [KegiatanController::class, 'show']);
    Route::put('/admin/kegiatan/{kegiatan}', [KegiatanController::class, 'update']);
    Route::delete('/admin/kegiatan/{kegiatan}', [KegiatanController::class, 'destroy']);
});

// public
Route::get('/kegiatan', [KegiatanController::class, 'publicList']);

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/berita', [BeritaController::class, 'index']);
    Route::post('/berita', [BeritaController::class, 'store']);
    Route::get('/berita/{id}', [BeritaController::class, 'showById']);   // 🔥 khusus admin by id
    Route::put('/berita/{id}', [BeritaController::class, 'update']);
    Route::delete('/berita/{id}', [BeritaController::class, 'destroy']);
});

Route::get('/berita', [BeritaController::class, 'publicIndex']);
Route::get('/berita/slug/{slug}', [BeritaController::class, 'showBySlug']);  // 🔥 khusus slug
