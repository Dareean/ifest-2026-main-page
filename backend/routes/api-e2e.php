<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

Route::post('/e2e/verify-user', function (Request $request) {
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
});

Route::post('/e2e/reset-token', function (Request $request) {
    $user = \App\Models\User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }
    $token = Password::createToken($user);
    return response()->json(['token' => $token, 'email' => $user->email]);
});

Route::post('/e2e/set-admin', function (Request $request) {
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
});
