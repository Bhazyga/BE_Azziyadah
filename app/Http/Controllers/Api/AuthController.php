<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\LogoutRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Mail\SendOtpMail;

class AuthController extends Controller
{

        public function register(RegisterRequest $request)
        {
            $data = $request->validated();

            $user = User::create([
                'name' => $data['name'],
                'email'=> $data['email'],
                'password' => Hash::make($data['password']),
                'role'  => 'santri'
            ]);

            // Generate OTP langsung setelah register
            $user->generateEmailOtp();
            Mail::to($user->email)->send(new SendOtpMail($user->email_otp));

            $token = $user->createToken('main')->plainTextToken;

            return response(compact('user', 'token'));
        }

        public function login(LoginRequest $request)
        {
            $credentials = $request->validated();
            if (!Auth::attempt($credentials)){
                return response([
                    'message' => 'email atau password nya salah '
                ],422);
            }

            /** @var User  $user */

            $user = Auth::user();
            $token = $user->createToken('main')->plainTextToken;
            return response (compact('user' , 'token'));
        }

        public function logout(Request $request)
        {

            /** @var User $user */
            $user = $request->user();
            $user->currentAccessToken()->delete();
            return response('',204);
        }

        public function sendOtp(Request $request)
        {
            $user = $request->user();
            $user->generateEmailOtp();

            Mail::to($user->email)->send(new SendOtpMail($user->email_otp));

            return response()->json(['message' => 'OTP terkirim!']);
        }


        public function verifyOtp(Request $request)
        {
            $request->validate([
                'otp' => 'required|numeric'
            ]);

            $user = $request->user();

            if (!$user->email_otp || !$user->email_otp_expires_at) {
                return response()->json(['message' => 'OTP tidak ada, kirim ulang!'], 422);
            }

            if ($user->email_otp_expires_at < now()) {
                return response()->json(['message' => 'OTP kadaluarsa!'], 422);
            }

            if ($user->email_otp != $request->otp) {
                return response()->json(['message' => 'OTP salah!'], 422);
            }

            $user->email_verified_at = now();
            $user->email_otp = null;
            $user->email_otp_expires_at = null;
            $user->save();

            return response()->json([
                'message' => 'Email berhasil diverifikasi!',
                'user' => $user->makeHidden(['password', 'remember_token', 'email_otp', 'email_otp_expires_at'])
            ]);
        }


        // ================= FORGOT PASSWORD =================

        public function forgotPassword(Request $request)
        {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            return $status === Password::RESET_LINK_SENT
                ? response()->json(['message' => 'Link reset password dikirim ke email'])
                : response()->json(['message' => 'Gagal mengirim email'], 500);
        }

        public function resetPassword(Request $request)
        {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed',
            ]);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->update([
                        'password' => Hash::make($password),
                    ]);
                }
            );

            return $status === Password::PASSWORD_RESET
                ? response()->json(['message' => 'Password berhasil direset'])
                : response()->json(['message' => 'Token tidak valid / expired'], 422);
        }

}
