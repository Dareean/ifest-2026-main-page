<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LombaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

// Public routes with rate limiting
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,10');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:3,10');
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,10');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,10');
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);

Route::get('/lombas', [LombaController::class, 'index']);
Route::get('/lombas/{lomba}', [LombaController::class, 'show']);

// AI chat proxy (protected to prevent abuse)
Route::middleware('auth:sanctum')->post('/ai/chat', [\App\Http\Controllers\GeminiController::class, 'chat']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    Route::post('/lombas/{lomba}/daftar', [PendaftaranController::class, 'store']);
    Route::get('/pendaftarans', [PendaftaranController::class, 'index']);
    Route::get('/pendaftarans/{pendaftaran}', [PendaftaranController::class, 'show']);

    Route::post('/pendaftarans/{pendaftaran}/invite', [TeamController::class, 'invite']);
    Route::get('/pendaftarans/{pendaftaran}/team', [TeamController::class, 'myTeam']);
    Route::get('/pendaftarans/{pendaftaran}/invitations', [TeamController::class, 'byPendaftaran']);
    Route::delete('/pendaftarans/{pendaftaran}/members/{invitation}', [TeamController::class, 'removeMember']);
    Route::post('/pendaftarans/{pendaftaran}/request-unlock', [PendaftaranController::class, 'requestUnlock']);
    Route::post('/pendaftarans/{pendaftaran}/social-proof', [PendaftaranController::class, 'uploadSocialProof']);

    Route::post('/pendaftarans/{pendaftaran}/payment/upload', [PendaftaranController::class, 'uploadPayment']);

    Route::get('/invitations/pending', [TeamController::class, 'pendingInvitations']);
    Route::put('/invitations/{invitation}/accept', [TeamController::class, 'accept']);
    Route::put('/invitations/{invitation}/reject', [TeamController::class, 'reject']);

    Route::post('/pendaftarans/{pendaftaran}/submit', [SubmissionController::class, 'store']);
    Route::get('/submissions', [SubmissionController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/password', [ProfileController::class, 'updatePassword']);
    Route::post('/avatar', [ProfileController::class, 'uploadAvatar']);

    Route::get('/auth/google/connect', [AuthController::class, 'googleConnect']);
    Route::post('/auth/google/disconnect', [AuthController::class, 'googleDisconnect']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/stats', [AdminController::class, 'stats']);
    Route::get('/pendaftarans', [AdminController::class, 'pendaftarans']);
    Route::get('/pendaftarans/export', [AdminController::class, 'exportPendaftarans']);
    Route::put('/pendaftarans/batch/verify', [AdminController::class, 'batchVerify']);
    Route::put('/pendaftarans/batch/reject', [AdminController::class, 'batchReject']);
    Route::get('/pendaftarans/{pendaftaran}', [AdminController::class, 'pendaftaranDetail']);
    Route::put('/pendaftarans/{pendaftaran}/verify', [AdminController::class, 'verify']);
    Route::put('/pendaftarans/{pendaftaran}/reject', [AdminController::class, 'reject']);
    Route::put('/pendaftarans/{pendaftaran}/verify-payment', [AdminController::class, 'verifyPayment']);
    Route::put('/pendaftarans/{pendaftaran}/reject-payment', [AdminController::class, 'rejectPayment']);
    Route::put('/pendaftarans/{pendaftaran}/approve-unlock', [AdminController::class, 'approveUnlock']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/activity-logs', [AdminController::class, 'activityLogs']);
    Route::post('/notifications', [AdminController::class, 'broadcastNotification']);
    Route::get('/notifications', [AdminController::class, 'notifications']);
    Route::put('/users/{user}/role', [AdminController::class, 'updateRole']);
    Route::get('/super/admins', [AdminController::class, 'admins']);
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser']);
});
