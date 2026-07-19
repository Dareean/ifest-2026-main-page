<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLombaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LombaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\FaqController;
use Illuminate\Support\Facades\Route;

$authThrottle = fn($limit) => [env('APP_ENV') === 'local' ? "throttle:1000,1" : "throttle:$limit"];
Route::post('/auth/register', [AuthController::class, 'register'])->middleware(...$authThrottle('5,10'));
Route::post('/auth/login', [AuthController::class, 'login'])->middleware(...$authThrottle('10,1'));
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp'])->middleware(...$authThrottle('3,10'));
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])->middleware(...$authThrottle('10,1'));
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware(...$authThrottle('3,10'));
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware(...$authThrottle('5,10'));
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect'])
    ->middleware($authThrottle('10,1'));
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])
    ->middleware($authThrottle('10,1'));

Route::post('/auth/2fa/verify', [AuthController::class, 'verifyTwoFactor'])->middleware(...$authThrottle('10,1'));
Route::post('/auth/2fa/recover', [AuthController::class, 'recoverTwoFactor'])->middleware(...$authThrottle('5,10'));

Route::get('/lombas', [LombaController::class, 'index'])->middleware(...$authThrottle('60,1'));
Route::get('/lombas/{lomba}', [LombaController::class, 'show'])->middleware(...$authThrottle('60,1'));

// AI chat proxy (protected to prevent abuse)
Route::middleware(array_merge(['auth:sanctum'], $authThrottle('30,1')))->post('/ai/chat', [\App\Http\Controllers\GeminiController::class, 'chat']);

// Protected routes — global rate limit 60 req/min
Route::middleware(array_merge(['auth:sanctum'], $authThrottle('60,1')))->group(function () use ($authThrottle) {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    Route::post('/lombas/{lomba}/daftar', [PendaftaranController::class, 'store']);
    Route::get('/pendaftarans', [PendaftaranController::class, 'index']);
    Route::get('/pendaftarans/{pendaftaran}', [PendaftaranController::class, 'show']);

    Route::get('/pendaftarans/{pendaftaran}/team', [TeamController::class, 'myTeam']);
    Route::get('/pendaftarans/{pendaftaran}/invitations', [TeamController::class, 'byPendaftaran']);
    Route::delete('/pendaftarans/{pendaftaran}/members/{invitation}', [TeamController::class, 'removeMember']);
    Route::post('/pendaftarans/{pendaftaran}/request-unlock', [PendaftaranController::class, 'requestUnlock']);
    Route::post('/pendaftarans/{pendaftaran}/social-proof', [PendaftaranController::class, 'uploadSocialProof']);

    Route::post('/pendaftarans/{pendaftaran}/payment/upload', [PendaftaranController::class, 'uploadPayment']);

    Route::get('/invitations/pending', [TeamController::class, 'pendingInvitations']);
    Route::put('/invitations/{invitation}/accept', [TeamController::class, 'accept']);
    Route::put('/invitations/{invitation}/reject', [TeamController::class, 'reject']);
    Route::post('/invitations/{invitation}/social-proof', [TeamController::class, 'uploadSocialProof']);

    Route::get('/submissions', [SubmissionController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::put('/profile', [ProfileController::class, 'update']);

    Route::get('/auth/google/connect', [AuthController::class, 'googleConnect']);
    Route::post('/auth/google/disconnect', [AuthController::class, 'googleDisconnect']);

    Route::get('/auth/2fa/status', [TwoFactorController::class, 'status']);
    Route::post('/auth/2fa/enable', [TwoFactorController::class, 'enable']);
    Route::post('/auth/2fa/verify-setup', [TwoFactorController::class, 'verify']);
    Route::post('/auth/2fa/disable', [TwoFactorController::class, 'disable']);
    Route::post('/auth/2fa/recovery-codes', [TwoFactorController::class, 'recover']);
});

// Sensitive endpoints with stricter rate limits
Route::middleware('auth:sanctum')->group(function () use ($authThrottle) {
    Route::post('/pendaftarans/{pendaftaran}/invite', [TeamController::class, 'invite'])->middleware(...$authThrottle('30,10'));
    Route::post('/pendaftarans/{pendaftaran}/submit', [SubmissionController::class, 'store'])->middleware(...$authThrottle('5,1'));
    Route::put('/password', [ProfileController::class, 'updatePassword'])->middleware(...$authThrottle('5,10'));
    Route::post('/avatar', [ProfileController::class, 'uploadAvatar'])->middleware(...$authThrottle('5,10'));
});

// E2E testing helpers (local environment only — never in production)
if (app()->environment('local')) {
    require __DIR__.'/api-e2e.php';
}

Route::get('/partners', [PartnerController::class, 'index']);
Route::get('/timeline', [TimelineController::class, 'index']);
Route::get('/faqs', [FaqController::class, 'index']);

// Admin routes
Route::middleware(array_merge(['auth:sanctum', 'admin'], $authThrottle('120,1')))->prefix('admin')->group(function () {
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
    Route::post('/users', [AdminController::class, 'createUser']);
    Route::get('/activity-logs', [AdminController::class, 'activityLogs']);
    Route::post('/notifications', [AdminController::class, 'broadcastNotification']);
    Route::get('/notifications', [AdminController::class, 'notifications']);
    Route::put('/users/{user}/role', [AdminController::class, 'updateRole']);
    Route::get('/super/admins', [AdminController::class, 'admins']);
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser']);

    Route::get('/lombas', [AdminLombaController::class, 'index']);
    Route::put('/lombas/{lomba}/toggle-submission', [AdminLombaController::class, 'toggleSubmission']);
    Route::put('/lombas/{lomba}/toggle-active', [AdminLombaController::class, 'toggleActive']);
    Route::put('/lombas/{lomba}', [AdminLombaController::class, 'update']);

    Route::get('/partners', [PartnerController::class, 'adminIndex']);
    Route::post('/partners', [PartnerController::class, 'store']);
    Route::get('/partners/{partner}', [PartnerController::class, 'show']);
    Route::put('/partners/{partner}', [PartnerController::class, 'update']);
    Route::delete('/partners/{partner}', [PartnerController::class, 'destroy']);

    Route::get('/timeline', [TimelineController::class, 'adminIndex']);
    Route::post('/timeline', [TimelineController::class, 'store']);
    Route::get('/timeline/{timelineEvent}', [TimelineController::class, 'show']);
    Route::put('/timeline/{timelineEvent}', [TimelineController::class, 'update']);
    Route::delete('/timeline/{timelineEvent}', [TimelineController::class, 'destroy']);

    Route::get('/faqs', [FaqController::class, 'adminIndex']);
    Route::post('/faqs', [FaqController::class, 'store']);
    Route::get('/faqs/{faqItem}', [FaqController::class, 'show']);
    Route::put('/faqs/{faqItem}', [FaqController::class, 'update']);
    Route::delete('/faqs/{faqItem}', [FaqController::class, 'destroy']);
});
