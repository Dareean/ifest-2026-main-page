<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LombaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
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
    Route::get('/pendaftarans/invitations', [\App\Http\Controllers\TeamController::class, 'invitations']);
    Route::get('/pendaftarans', [PendaftaranController::class, 'index']);
    Route::get('/pendaftarans/{pendaftaran}', [PendaftaranController::class, 'show']);

    Route::post('/pendaftarans/{pendaftaran}/invite', [\App\Http\Controllers\TeamController::class, 'invite']);
    Route::post('/pendaftarans/{pendaftaran}/accept-invite', [\App\Http\Controllers\TeamController::class, 'acceptInvite']);
    Route::post('/pendaftarans/{pendaftaran}/decline-invite', [\App\Http\Controllers\TeamController::class, 'declineInvite']);
    Route::delete('/pendaftarans/{pendaftaran}/members/{user_id}', [\App\Http\Controllers\TeamController::class, 'removeMember']);
    Route::post('/pendaftarans/{pendaftaran}/lock', [\App\Http\Controllers\TeamController::class, 'lockTeam']);
    Route::post('/pendaftarans/{pendaftaran}/request-unlock', [\App\Http\Controllers\TeamController::class, 'requestUnlock']);
    Route::post('/pendaftarans/{pendaftaran}/admin-approve-unlock', [\App\Http\Controllers\TeamController::class, 'adminApproveUnlock']);

    Route::post('/pendaftarans/{pendaftaran}/submit', [SubmissionController::class, 'store']);
    Route::get('/submissions', [SubmissionController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/password', [ProfileController::class, 'updatePassword']);

    Route::get('/auth/google/connect', [AuthController::class, 'googleConnect']);
    Route::post('/auth/google/disconnect', [AuthController::class, 'googleDisconnect']);
});
