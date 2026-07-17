<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'enabled' => !is_null($user->two_factor_secret),
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_secret) {
            return response()->json(['message' => '2FA sudah aktif'], 400);
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = json_encode(
            collect(range(1, 8))->map(fn() => strtoupper(str()->random(10)))->toArray()
        );
        $user->save();

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'message' => '2FA berhasil diaktifkan. Scan QR code dengan Google Authenticator.',
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'recovery_codes' => json_decode($user->two_factor_recovery_codes),
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

        if (!$valid) {
            return response()->json(['message' => 'Kode 2FA tidak valid'], 400);
        }

        $request->session()->put('two_factor_verified', true);

        return response()->json(['message' => 'Kode 2FA valid']);
    }

    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

        if (!$valid) {
            return response()->json(['message' => 'Kode 2FA tidak valid'], 400);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        $request->session()->forget('two_factor_verified');

        return response()->json(['message' => '2FA berhasil dinonaktifkan']);
    }

    public function recover(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'recovery_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $codes = json_decode($user->two_factor_recovery_codes ?? '[]', true);

        $index = array_search($request->recovery_code, $codes);

        if ($index === false) {
            return response()->json(['message' => 'Kode recovery tidak valid'], 400);
        }

        unset($codes[$index]);
        $user->two_factor_recovery_codes = json_encode(array_values($codes));
        $user->save();

        $request->session()->put('two_factor_verified', true);

        return response()->json(['message' => 'Akses diberikan menggunakan kode recovery']);
    }
}
