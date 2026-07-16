<?php

namespace App\Http\Controllers;

use App\Models\EmailVerification;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    private function frontendUrl(): string
    {
        return config('app.frontend_url', 'http://localhost:5173');
    }

    private function sendOtpEmail(string $email, string $name, string $otp, string $expiresAt): void
    {
        if (app()->environment('local')) {
            Log::info('OTP for ' . $email . ': ' . $otp . ' (expires at ' . $expiresAt . ')');
            return;
        }

        try {
            $html = view('emails.otp', compact('name', 'otp', 'expiresAt'))->render();

            Http::withHeaders([
                'api-key' => config('services.brevo.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => ['email' => config('mail.from.address', 'noreply@ifest2026.com'), 'name' => 'I-FEST 2026'],
                'to' => [['email' => $email]],
                'subject' => 'Kode Verifikasi Email — I-FEST 2026',
                'htmlContent' => $html,
            ]);
        } catch (\Exception $e) {
            Log::error('Send OTP email failed: ' . $e->getMessage(), ['to' => $email]);
        }
    }

    private function generateAndSendOtp(string $email, string $name): string
    {
        EmailVerification::where('email', $email)->delete();

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        EmailVerification::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => $expiresAt,
        ]);

        $this->sendOtpEmail($email, $name, $otp, $expiresAt->format('H:i'));

        return $otp;
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
            'email_verified_at' => null,
        ]);

        $this->generateAndSendOtp($user->email, $user->name);

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan verifikasi email Anda dengan kode OTP yang telah dikirim.',
            'user'    => $user->only(['id', 'name', 'email']),
            'needs_verification' => true,
        ], 201);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user || $user->email_verified_at) {
            return response()->json(['message' => 'Jika email terdaftar, kode OTP telah dikirim']);
        }

        $this->generateAndSendOtp($user->email, $user->name);

        return response()->json(['message' => 'Jika email terdaftar, kode OTP telah dikirim']);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $otpKey = 'otp_attempts_' . $request->email;
        $attempts = (int) Cache::get($otpKey, 0);

        if ($attempts >= 3) {
            Log::warning('OTP brute force blocked', ['email' => $request->email]);
            return response()->json(['message' => 'Terlalu banyak percobaan. Silakan minta kode OTP baru.'], 429);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Kode OTP tidak valid atau sudah kadaluarsa'], 400);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email sudah diverifikasi'], 400);
        }

        $records = EmailVerification::where('email', $request->email)
            ->valid()
            ->get();

        $record = $records->first(fn($r) => $r->verify($request->otp));

        if (!$record) {
            Cache::put($otpKey, $attempts + 1, now()->addHour());
            Log::warning('OTP verification failed', ['email' => $request->email]);
            return response()->json(['message' => 'Kode OTP tidak valid atau sudah kadaluarsa'], 400);
        }

        Cache::forget($otpKey);
        $user->update(['email_verified_at' => now()]);

        $record->delete();

        Notification::create([
            'user_id' => $user->id,
            'judul'   => 'Selamat Datang di I-FEST 2026! 🎉',
            'pesan'   => "Halo, {$user->name}! Akun kamu berhasil diverifikasi. Yuk, mulai jelajahi kompetisi-kompetisi seru di I-FEST 2026 dan daftarkan tim kamu sekarang!",
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Email berhasil diverifikasi',
            'user'    => $user->only(['id', 'name', 'email', 'role']),
        ]);
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

        $limiter = app(RateLimiter::class);
        $loginKey = 'login:' . $request->input('email');

        if ($limiter->tooManyAttempts($loginKey, 5)) {
            $seconds = $limiter->availableIn($loginKey);
            Log::warning('Login rate limited', ['email' => $request->input('email'), 'ip' => $request->ip()]);
            return response()->json([
                'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.'
            ], 429);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            $limiter->hit($loginKey, 900);
            Log::warning('Login failed', ['email' => $request->input('email'), 'ip' => $request->ip()]);
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        $limiter->clear($loginKey);

        $request->session()->regenerate();

        $user = Auth::user();

        if (is_null($user->email_verified_at) && $user->role !== 'admin') {
            Auth::logout();
            $request->session()->invalidate();
            return response()->json([
                'message' => 'Email belum diverifikasi. Silakan cek kode OTP di email Anda.',
                'needs_verification' => true,
                'email' => $user->email,
            ], 403);
        }

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user->only(['id', 'name', 'email', 'avatar', 'role']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoke token if using Bearer auth
        $token = $request->user()?->currentAccessToken();
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        // Logout session if using SPA auth
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

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
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Link reset password telah dikirim ke email Anda jika email terdaftar']);
        }

        // Always return same message to prevent user enumeration
        return response()->json(['message' => 'Link reset password telah dikirim ke email Anda jika email terdaftar']);
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
            Log::error('Google login callback failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect($this->frontendUrl() . '/login?error=google_failed');
        }

        // Detect connect mode via signed state parameter
        $state = $request->input('state');
        $connectUserId = null;

        if ($state) {
            $signed = json_decode(base64_decode($state), true);
            if (is_array($signed) && isset($signed['state'], $signed['hmac'])) {
                $expectedHmac = hash_hmac('sha256', $signed['state'], config('app.key'));
                if (hash_equals($expectedHmac, $signed['hmac'])) {
                    $data = json_decode(base64_decode($signed['state']), true);
                    if (is_array($data) && isset($data['user_id'], $data['exp'])) {
                        if (now()->timestamp <= $data['exp']) {
                            $connectUserId = $data['user_id'];
                        }
                    }
                }
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
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            Notification::create([
                'user_id' => $user->id,
                'judul' => 'Akun Google Terhubung',
                'pesan' => "Akun Google {$googleUser->getEmail()} berhasil dihubungkan ke akun I-FEST 2026 kamu. Kamu sekarang bisa login menggunakan Google kapan saja.",
            ]);

            Auth::login($user);
            $request->session()->regenerate();

            return redirect($this->frontendUrl() . '/dashboard/profile?google=connected');
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
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->frontendUrl() . '/auth/callback?login=success');
    }

    public function googleConnect(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $payload = json_encode([
                'user_id' => $user->id,
                'exp' => now()->addMinutes(10)->timestamp,
            ]);
            $state = base64_encode($payload);
            $hmac = hash_hmac('sha256', $payload, config('app.key'));
            $signed = base64_encode(json_encode(['state' => $state, 'hmac' => $hmac]));

            $url = Socialite::driver('google')
                ->stateless()
                ->with(['state' => $signed])
                ->redirect()
                ->getTargetUrl();

            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            Log::error('googleConnect error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Gagal menghubungkan Google. Silakan coba lagi.'], 500);
        }
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
