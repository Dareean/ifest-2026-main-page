<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

$throttle = env('APP_ENV') === 'local' ? 'throttle:1000,1' : 'throttle:10,1';

Route::post('/e2e/verify-user', function (Request $request) {
    Log::warning('E2E route called: verify-user', ['email' => $request->email, 'ip' => $request->ip()]);

    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user = \App\Models\User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $user->email_verified_at = now();
    $user->save();

    return response()->json([
        'message' => 'User verified',
        'user'    => $user->fresh(),
    ]);
})->middleware($throttle);

Route::post('/e2e/reset-token', function (Request $request) {
    Log::warning('E2E route called: reset-token', ['email' => $request->email, 'ip' => $request->ip()]);

    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user = \App\Models\User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $token = Password::createToken($user);
    return response()->json(['token' => $token, 'email' => $user->email]);
})->middleware($throttle);

Route::post('/e2e/set-admin', function (Request $request) {
    Log::warning('E2E route called: set-admin', ['email' => $request->email, 'ip' => $request->ip()]);

    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user = \App\Models\User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $user->role = 'admin';
    $user->save();

    return response()->json([
        'message' => 'User promoted to admin',
        'user'    => $user->fresh(),
    ]);
})->middleware($throttle);

Route::post('/e2e/force-verify', function (Request $request) {
    Log::warning('E2E route called: force-verify', ['pendaftaran_id' => $request->pendaftaran_id, 'ip' => $request->ip()]);

    $validator = Validator::make($request->all(), [
        'pendaftaran_id' => 'required|integer|exists:pendaftarans,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $pendaftaran = \App\Models\Pendaftaran::findOrFail($request->pendaftaran_id);
    $pendaftaran->update([
        'status' => 'verified',
        'payment_status' => 'verified',
        'payment_verified_at' => now(),
        'team_locked' => true,
    ]);

    return response()->json([
        'message' => 'Pendaftaran force-verified',
        'data' => $pendaftaran->fresh(),
    ]);
})->middleware($throttle);

