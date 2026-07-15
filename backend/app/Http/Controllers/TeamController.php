<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Pendaftaran;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

        if ($pendaftaran->team_locked) {
            return response()->json(['message' => 'Tim sedang terkunci. Ajukan permintaan buka kunci melalui dashboard untuk mengundang anggota baru.'], 403);
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
                'found' => (bool) $invitedUser,
            ], 201);
        }

        $invitedUser = User::where('email', $email)->first();

        // Only run user-specific conflict checks if the user exists (registered)
        if ($invitedUser) {
            // Check if invited user is the ketua themselves
            if ($invitedUser->id === $request->user()->id) {
                return response()->json(['message' => 'Tidak bisa mengundang diri sendiri'], 400);
            }

            // Check if user is already ketua of another team for this lomba
            $alreadyKetua = Pendaftaran::where('user_id', $invitedUser->id)
                ->where('lomba_id', $pendaftaran->lomba_id)
                ->exists();
            if ($alreadyKetua) {
                return response()->json(['message' => 'User sudah mendaftar sebagai ketua tim lain untuk lomba ini'], 400);
            }

            // Check if user is already an accepted member of another team for this lomba
            $alreadyMember = TeamInvitation::whereHas('pendaftaran', function ($q) use ($pendaftaran) {
                    $q->where('lomba_id', $pendaftaran->lomba_id);
                })
                ->where('invited_user_id', $invitedUser->id)
                ->where('status', 'accepted')
                ->exists();
            if ($alreadyMember) {
                return response()->json(['message' => 'User sudah menjadi anggota tim lain untuk lomba ini'], 400);
            }

            // Also block if user has a pending invitation for a different team in the same lomba
            $alreadyInvited = TeamInvitation::whereHas('pendaftaran', function ($q) use ($pendaftaran) {
                    $q->where('lomba_id', $pendaftaran->lomba_id)->where('id', '!=', $pendaftaran->id);
                })
                ->where('invited_user_id', $invitedUser->id)
                ->where('status', 'pending')
                ->exists();
            if ($alreadyInvited) {
                return response()->json(['message' => 'User sudah memiliki undangan pending untuk tim lain di lomba ini'], 400);
            }

            // Check per kategori: user already in another competition of same category
            $kategori = explode('-', $pendaftaran->lomba->kode)[0];
            $sameKategori = Pendaftaran::where('user_id', $invitedUser->id)
                ->whereHas('lomba', function ($q) use ($kategori, $pendaftaran) {
                    $q->where('kode', 'like', $kategori . '-%')->where('id', '!=', $pendaftaran->lomba_id);
                })
                ->exists();
            if (!$sameKategori) {
                $sameKategori = TeamInvitation::whereHas('pendaftaran', function ($q) use ($kategori, $pendaftaran) {
                        $q->whereHas('lomba', function ($qq) use ($kategori) {
                            $qq->where('kode', 'like', $kategori . '-%');
                        })->where('id', '!=', $pendaftaran->id);
                    })
                    ->where('invited_user_id', $invitedUser->id)
                    ->where('status', 'accepted')
                    ->exists();
            }
            if ($sameKategori) {
                return response()->json(['message' => 'User sudah terdaftar di lomba lain di kategori ini. 1 akun hanya bisa mendaftar 1 lomba per kategori.'], 400);
            }
        }

        $invitation = TeamInvitation::create([
            'pendaftaran_id' => $pendaftaran->id,
            'email' => $email,
            'invited_by_user_id' => $request->user()->id,
            'invited_user_id' => $invitedUser?->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(3),
        ]);

        if ($invitedUser) {
            Notification::create([
                'user_id' => $invitedUser->id,
                'judul' => 'Undangan Tim',
                'pesan' => 'Kamu diundang bergabung ke tim "' . ($pendaftaran->team_name ?: 'Tim ' . $request->user()->name) . '" oleh ' . $request->user()->name . '. Klik untuk menerima atau menolak.',
            ]);
        }

        return response()->json([
            'message' => 'Undangan berhasil dikirim ke ' . $email,
            'invitation' => $invitation->load('invitedUser'),
            'found' => (bool) $invitedUser,
        ], 201);
    }

    public function pendingInvitations(Request $request): JsonResponse
    {
        $invitations = TeamInvitation::where('email', $request->user()->email)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with(['pendaftaran.lomba', 'pendaftaran.user', 'invitedBy'])
            ->latest()
            ->get();

        return response()->json(['data' => $invitations]);
    }

    public function byPendaftaran(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        $user = $request->user();
        $isKetua = $pendaftaran->user_id === $user->id;
        $isMember = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
            ->where('invited_user_id', $user->id)
            ->where('status', 'accepted')
            ->exists();

        if (!$isKetua && !$isMember) {
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
        return DB::transaction(function () use ($request, $invitation) {
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

            // Lock the pendaftaran row to prevent race conditions
            $pendaftaran = Pendaftaran::lockForUpdate()->find($invitation->pendaftaran_id);
            if (!$pendaftaran) {
                return response()->json(['message' => 'Pendaftaran tidak ditemukan'], 404);
            }

            // Check team capacity within the lock
            $maxMembers = $pendaftaran->lomba->getMaxMembers();
            $acceptedCount = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
                ->where('status', 'accepted')
                ->count();
            if (1 + $acceptedCount >= $maxMembers) {
                return response()->json(['message' => 'Tim sudah penuh (maksimal ' . $maxMembers . ' anggota)'], 400);
            }

            // Check if user already ketua of another team for this lomba
            $user = $request->user();
            $lombaId = $pendaftaran->lomba_id;
            $alreadyKetua = Pendaftaran::where('user_id', $user->id)
                ->where('lomba_id', $lombaId)
                ->where('id', '!=', $pendaftaran->id)
                ->exists();
            if ($alreadyKetua) {
                return response()->json(['message' => 'Kamu sudah mendaftar sebagai ketua tim lain untuk lomba ini'], 400);
            }

            // Check if user already accepted member of another team for this lomba
            $alreadyMember = TeamInvitation::whereHas('pendaftaran', function ($q) use ($lombaId, $pendaftaran) {
                    $q->where('lomba_id', $lombaId)->where('id', '!=', $pendaftaran->id);
                })
                ->where('invited_user_id', $user->id)
                ->where('status', 'accepted')
                ->exists();
            if ($alreadyMember) {
                return response()->json(['message' => 'Kamu sudah menjadi anggota tim lain untuk lomba ini'], 400);
            }

            // Check per kategori: user already in another competition of same category
            $lomba = $pendaftaran->lomba;
            $kategori = explode('-', $lomba->kode)[0];
            $sameKategori = Pendaftaran::where('user_id', $user->id)
                ->whereHas('lomba', function ($q) use ($kategori, $lombaId) {
                    $q->where('kode', 'like', $kategori . '-%')->where('id', '!=', $lombaId);
                })
                ->exists();
            if ($sameKategori) {
                return response()->json(['message' => 'Kamu sudah terdaftar di lomba lain di kategori ini. 1 akun hanya bisa mendaftar 1 lomba per kategori.'], 400);
            }

            $invitation->update([
                'status' => 'accepted',
                'invited_user_id' => $user->id,
            ]);

            // Notify ketua
            Notification::create([
                'user_id' => $invitation->invited_by_user_id,
                'judul' => 'Undangan Diterima',
                'pesan' => $user->name . ' telah menerima undangan bergabung ke tim.',
            ]);

            return response()->json(['message' => 'Berhasil bergabung ke tim', 'invitation' => $invitation]);
        });
    }

    public function reject(Request $request, TeamInvitation $invitation): JsonResponse
    {
        $user = $request->user();
        $isInvitee = $invitation->email === $user->email;
        $isInviter = $invitation->invited_by_user_id === $user->id;

        if (!$isInvitee && !$isInviter) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Undangan sudah tidak aktif'], 400);
        }

        $invitation->update(['status' => 'rejected']);

        if ($isInvitee) {
            Notification::create([
                'user_id' => $invitation->invited_by_user_id,
                'judul' => 'Undangan Ditolak',
                'pesan' => $user->name . ' menolak undangan bergabung ke tim.',
            ]);
        } else {
            Notification::create([
                'user_id' => $invitation->invited_user_id,
                'judul' => 'Undangan Dibatalkan',
                'pesan' => 'Undangan untuk bergabung ke tim telah dibatalkan oleh ketua tim.',
            ]);
        }

        return response()->json(['message' => 'Undangan ditolak']);
    }

    public function removeMember(Request $request, Pendaftaran $pendaftaran, TeamInvitation $invitation): JsonResponse
    {
        if (!$this->checkOwnership($request, $pendaftaran)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($pendaftaran->team_locked) {
            return response()->json(['message' => 'Tim sedang terkunci. Ajukan permintaan buka kunci melalui dashboard untuk mengeluarkan anggota.'], 403);
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

    public function uploadSocialProof(Request $request, TeamInvitation $invitation): JsonResponse
    {
        $user = $request->user();

        // Only the invitation owner (invited user) can upload their social proof
        if ($invitation->invited_user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($invitation->status !== 'accepted') {
            return response()->json(['message' => 'Undangan belum diterima'], 400);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:follow,twibbon',
            'proof_url' => 'required|url|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $column = $request->type === 'follow' ? 'ig_follow_proof' : 'ig_twibbon_proof';
        $invitation->update([$column => $request->proof_url]);

        $fresh = $invitation->fresh();
        if ($fresh->ig_follow_proof && $fresh->ig_twibbon_proof) {
            $invitation->update(['social_validated' => true]);
        }

        return response()->json([
            'message' => 'Bukti ' . ($request->type === 'follow' ? 'follow' : 'twibbon') . ' berhasil disimpan',
            'data' => $fresh->load('invitedUser'),
        ]);
    }
}
