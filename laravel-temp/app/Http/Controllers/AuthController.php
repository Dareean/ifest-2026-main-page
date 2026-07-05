<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    private function frontendUrl(): string
    {
        return env('FRONTEND_URL', 'http://localhost:5173');
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'institution' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'institution' => $request->institution,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        // Kirim notifikasi selamat datang ke inbox dashboard
        Notification::create([
            'user_id' => $user->id,
            'judul'   => 'Selamat Datang di I-FEST 2026! 🎉',
            'pesan'   => "Halo, {$user->name}! Akun kamu berhasil dibuat. Yuk, mulai jelajahi kompetisi-kompetisi seru di I-FEST 2026 dan daftarkan tim kamu sekarang!",
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load(['pendaftarans.lomba']);

        return response()->json([
            'user' => array_merge($user->toArray(), [
                'unread_notifications_count' => $request->user()->notifications()->where('is_read', false)->count(),
            ]),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Link reset password telah dikirim ke email Anda']);
        }

        return response()->json(['message' => 'Gagal mengirim link reset password'], 500);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password berhasil direset. Silakan login.']);
        }

        return response()->json(['message' => 'Token reset password tidak valid atau sudah kadaluarsa'], 400);
    }

    public function googleRedirect(): JsonResponse
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    public function googleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect($this->frontendUrl() . '/login?error=google_failed');
        }

        // Detect connect mode via encrypted state
        $state = $request->input('state');
        $connectUserId = null;

        if ($state) {
            try {
                $data = json_decode(Crypt::decryptString($state), true);
                $connectUserId = $data['user_id'] ?? null;
            } catch (\Exception $e) {
                // Not a connect state, proceed as regular login
            }
        }

        if ($connectUserId) {
            // === CONNECT MODE: Link Google to existing account ===
            $existing = User::where('google_id', $googleUser->getId())
                ->where('id', '!=', $connectUserId)
                ->first();
            if ($existing) {
                return redirect($this->frontendUrl() . '/dashboard/profile?google=error&reason=taken');
            }

            $user = User::find($connectUserId);
            if (!$user) {
                return redirect($this->frontendUrl() . '/dashboard/profile?google=error&reason=user_not_found');
            }

            $user->update([
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken,
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            $userData = urlencode(json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'institution' => $user->institution,
                'google_id' => $user->google_id,
            ]));

            return redirect($this->frontendUrl() . '/auth/callback?action=connect&token=' . $token . '&user=' . $userData);
        }

        // === LOGIN MODE: Normal Google login ===
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        $isNewUser = !$user;

        if ($isNewUser) {
            $user = User::create([
                'name'                 => $googleUser->getName(),
                'email'                => $googleUser->getEmail(),
                'google_id'            => $googleUser->getId(),
                'avatar'               => $googleUser->getAvatar(),
                'password'             => Hash::make(str()->random(32)),
                'google_token'         => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken,
            ]);

            Notification::create([
                'user_id' => $user->id,
                'judul'   => 'Selamat Datang di I-FEST 2026! 🎉',
                'pesan'   => "Halo, {$user->name}! Akun kamu berhasil dibuat via Google. Yuk, mulai jelajahi kompetisi-kompetisi seru di I-FEST 2026 dan daftarkan diri kamu sekarang!",
            ]);
        } else {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken ?? $user->google_refresh_token,
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $userData = urlencode(json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
            'institution' => $user->institution,
            'google_id' => $user->google_id,
        ]));

        return redirect($this->frontendUrl() . '/auth/callback?token=' . $token . '&user=' . $userData);
    }

    public function googleConnect(Request $request): JsonResponse
    {
        $user = $request->user();
        $state = Crypt::encryptString(json_encode(['user_id' => $user->id]));
        $url = Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    public function googleDisconnect(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'google_id' => null,
            'avatar' => null,
            'google_token' => null,
            'google_refresh_token' => null,
        ]);

        return response()->json(['message' => 'Google berhasil diputuskan']);
    }
}
