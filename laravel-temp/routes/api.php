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

// Debug
Route::get('/debug/brevo', function () {
    $apiKey = env('BREVO_API_KEY');
    if (!$apiKey) return 'BREVO_API_KEY not set';

    $res = Http::withHeaders([
        'api-key' => $apiKey,
        'Content-Type' => 'application/json',
    ])->post('https://api.brevo.com/v3/smtp/email', [
        'sender' => ['email' => 'noreply@ifest2026.com', 'name' => 'I-FEST 2026'],
        'to' => [['email' => 'dmardin@gmail.com']],
        'subject' => 'Test from Render via Brevo API',
        'htmlContent' => '<h1>Test</h1><p>If you see this, API works!</p>',
    ]);

    return "Status: {$res->status()}\nBody: {$res->body()}";
});

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);
Route::get('/auth/google/callback/connect', [AuthController::class, 'googleConnectCallback']);

Route::get('/lombas', [LombaController::class, 'index']);
Route::get('/lombas/{lomba}', [LombaController::class, 'show']);

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
    Route::post('/pendaftarans/{pendaftaran}/request-changes', [TeamController::class, 'requestChanges']);

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
    Route::get('/pendaftarans/{pendaftaran}', [AdminController::class, 'pendaftaranDetail']);
    Route::put('/pendaftarans/{pendaftaran}/verify', [AdminController::class, 'verify']);
    Route::put('/pendaftarans/{pendaftaran}/reject', [AdminController::class, 'reject']);
    Route::put('/pendaftarans/{pendaftaran}/approve-unlock', [AdminController::class, 'approveUnlock']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::put('/users/{user}/role', [AdminController::class, 'updateRole']);
    Route::post('/notifications', [AdminController::class, 'broadcastNotification']);
    Route::get('/notifications', [AdminController::class, 'notifications']);
});
