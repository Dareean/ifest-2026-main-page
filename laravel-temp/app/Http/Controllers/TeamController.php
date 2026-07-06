<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Pendaftaran;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    private function checkOwnership(Request $request, Pendaftaran $pendaftaran): bool
    {
        return $pendaftaran->user_id === $request->user()->id;
    }

    public function invite(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if (!$this->checkOwnership($request, $pendaftaran)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $pendaftaran->load('lomba');

        if ($pendaftaran->status === 'rejected') {
            return response()->json(['message' => 'Pendaftaran telah ditolak'], 400);
        }

        $request->validate(['email' => 'required|email']);

        $maxMembers = $pendaftaran->lomba->getMaxMembers();
        $acceptedCount = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
            ->where('status', 'accepted')
            ->count();

        // +1 for the ketua (user_id)
        if ($acceptedCount + 1 >= $maxMembers) {
            return response()->json(['message' => 'Tim sudah penuh (maksimal ' . $maxMembers . ' anggota)'], 400);
        }

        $email = $request->email;

        // Check if already invited
        $existing = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
            ->where('email', $email)
            ->first();

        if ($existing) {
            if ($existing->status === 'pending') {
                return response()->json(['message' => 'Anggota ini sudah diundang, tunggu konfirmasi'], 400);
            }
            if ($existing->status === 'accepted') {
                return response()->json(['message' => 'Anggota ini sudah bergabung'], 400);
            }
            // If rejected, allow re-invite by updating
            $invitedUser = User::where('email', $email)->first();
            $existing->update(['status' => 'pending', 'invited_user_id' => $invitedUser?->id, 'expires_at' => now()->addDays(3)]);
            if ($invitedUser) {
                Notification::create([
                    'user_id' => $invitedUser->id,
                    'judul' => 'Undangan Tim',
                    'pesan' => 'Kamu diundang bergabung ke tim "' . ($pendaftaran->team_name ?: 'Tim ' . $request->user()->name) . '" oleh ' . $request->user()->name . '. Klik untuk menerima atau menolak.',
                ]);
            }
            return response()->json([
                'message' => 'Undangan berhasil dikirim ke ' . $email,
                'invitation' => $existing->load('invitedUser'),
                'found' => true,
            ], 201);
        }

        $invitedUser = User::where('email', $email)->first();

        if (!$invitedUser) {
            return response()->json([
                'message' => 'Email ' . $email . ' belum terdaftar di sistem',
                'found' => false,
            ], 404);
        }

        // Check if invited user is the ketua themselves
        if ($invitedUser->id === $request->user()->id) {
            return response()->json(['message' => 'Tidak bisa mengundang diri sendiri'], 400);
        }

        // Check if user already has a pendaftaran for this lomba (already on another team)
        $alreadyRegistered = Pendaftaran::where('user_id', $invitedUser->id)
            ->where('lomba_id', $pendaftaran->lomba_id)
            ->where('status', 'verified')
            ->exists();
        if ($alreadyRegistered) {
            return response()->json(['message' => 'User sudah terdaftar di tim lain untuk lomba ini'], 400);
        }

        $invitation = TeamInvitation::create([
            'pendaftaran_id' => $pendaftaran->id,
            'email' => $email,
            'invited_by_user_id' => $request->user()->id,
            'invited_user_id' => $invitedUser->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(3),
        ]);

        Notification::create([
            'user_id' => $invitedUser->id,
            'judul' => 'Undangan Tim',
            'pesan' => 'Kamu diundang bergabung ke tim "' . ($pendaftaran->team_name ?: 'Tim ' . $request->user()->name) . '" oleh ' . $request->user()->name . '. Klik untuk menerima atau menolak.',
        ]);

        return response()->json([
            'message' => 'Undangan berhasil dikirim ke ' . $email,
            'invitation' => $invitation->load('invitedUser'),
            'found' => true,
        ], 201);
    }

    public function pendingInvitations(Request $request): JsonResponse
    {
        $invitations = TeamInvitation::where('email', $request->user()->email)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with(['pendaftaran.lomba', 'invitedBy'])
            ->latest()
            ->get();

        return response()->json(['data' => $invitations]);
    }

    public function byPendaftaran(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if (!$this->checkOwnership($request, $pendaftaran)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $invitations = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
            ->with(['invitedUser', 'invitedBy'])
            ->latest()
            ->get();

        return response()->json(['data' => $invitations]);
    }

    public function myTeam(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if (!$this->checkOwnership($request, $pendaftaran)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $pendaftaran->load('lomba', 'user');
        $ketua = $pendaftaran->user;
        $accepted = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
            ->where('status', 'accepted')
            ->with('invitedUser')
            ->get();

        $pending = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
            ->where('status', 'pending')
            ->with('invitedUser')
            ->get();

        $maxMembers = $pendaftaran->lomba->getMaxMembers();

        return response()->json([
            'pendaftaran' => $pendaftaran,
            'ketua' => $ketua,
            'accepted_members' => $accepted->map(fn($i) => [
                'invitation_id' => $i->id,
                'id' => $i->invitedUser->id,
                'name' => $i->invitedUser->name,
                'email' => $i->invitedUser->email,
            ]),
            'pending_invitations' => $pending->map(fn($i) => [
                'id' => $i->id,
                'email' => $i->email,
                'name' => $i->invitedUser?->name,
                'created_at' => $i->created_at,
                'expires_at' => $i->expires_at,
            ]),
            'max_members' => $maxMembers,
            'current_count' => 1 + $accepted->count(),
            'status' => $pendaftaran->status,
        ]);
    }

    public function accept(Request $request, TeamInvitation $invitation): JsonResponse
    {
        if ($invitation->email !== $request->user()->email) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Undangan sudah tidak aktif'], 400);
        }

        if ($invitation->expires_at && now()->greaterThan($invitation->expires_at)) {
            $invitation->update(['status' => 'rejected']);
            return response()->json(['message' => 'Undangan sudah kedaluwarsa'], 400);
        }

        $invitation->update([
            'status' => 'accepted',
            'invited_user_id' => $request->user()->id,
        ]);

        // Notify ketua
        Notification::create([
            'user_id' => $invitation->invited_by_user_id,
            'judul' => 'Undangan Diterima',
            'pesan' => $request->user()->name . ' telah menerima undangan bergabung ke tim.',
        ]);

        return response()->json(['message' => 'Berhasil bergabung ke tim', 'invitation' => $invitation]);
    }

    public function reject(Request $request, TeamInvitation $invitation): JsonResponse
    {
        if ($invitation->email !== $request->user()->email) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Undangan sudah tidak aktif'], 400);
        }

        $invitation->update(['status' => 'rejected']);

        // Notify ketua
        Notification::create([
            'user_id' => $invitation->invited_by_user_id,
            'judul' => 'Undangan Ditolak',
            'pesan' => $request->user()->name . ' menolak undangan bergabung ke tim.',
        ]);

        return response()->json(['message' => 'Undangan ditolak']);
    }

    public function removeMember(Request $request, Pendaftaran $pendaftaran, TeamInvitation $invitation): JsonResponse
    {
        if (!$this->checkOwnership($request, $pendaftaran)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($invitation->pendaftaran_id !== $pendaftaran->id) {
            return response()->json(['message' => 'Anggota tidak terdaftar di tim ini'], 400);
        }

        if ($invitation->status !== 'accepted') {
            return response()->json(['message' => 'Anggota belum bergabung'], 400);
        }

        $userName = $invitation->invitedUser?->name ?? 'Anggota';
        $invitation->update(['status' => 'rejected']);

        // Notify removed member
        if ($invitation->invited_user_id) {
            Notification::create([
                'user_id' => $invitation->invited_user_id,
                'judul' => 'Dikeluarkan dari Tim',
                'pesan' => 'Kamu telah dikeluarkan dari tim "' . ($pendaftaran->team_name ?: 'Tim') . '" oleh ketua tim.',
            ]);
        }

        return response()->json(['message' => $userName . ' berhasil dikeluarkan dari tim']);
    }


}
