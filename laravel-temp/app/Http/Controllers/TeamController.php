<?php

namespace App\Http\Controllers;

use App\Models\Pendaftaran;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    private function getMaxMembers($req): int
    {
        if (!$req) return 3;
        if (stripos($req, 'individu') !== false) {
            return 1;
        }
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $req, $matches)) {
            return (int)$matches[2];
        }
        if (preg_match('/(\d+)/', $req, $matches)) {
            return (int)$matches[1];
        }
        return 3;
    }

    public function invitations(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Find pendaftarans where team_members has a pending invite for this user
        $pendaftarans = Pendaftaran::with('lomba', 'user')
            ->where('status', 'verified')
            ->whereNotNull('team_members')
            ->get()
            ->filter(function ($pendaftaran) use ($user) {
                $members = $pendaftaran->team_members;
                if (!is_array($members)) return false;
                foreach ($members as $m) {
                    if (isset($m['email']) && strtolower($m['email']) === strtolower($user->email) && isset($m['status']) && $m['status'] === 'pending') {
                        return true;
                    }
                }
                return false;
            })
            ->values();

        // Format return values
        $formatted = $pendaftarans->map(function ($p) {
            return [
                'id' => $p->id,
                'team_name' => $p->team_name,
                'lomba' => $p->lomba,
                'leader' => $p->user,
            ];
        });

        return response()->json(['data' => $formatted]);
    }

    public function invite(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        $user = $request->user();

        if ($pendaftaran->user_id !== $user->id) {
            return response()->json(['message' => 'Hanya ketua tim yang dapat mengundang anggota.'], 403);
        }

        if ($pendaftaran->status !== 'verified') {
            return response()->json(['message' => 'Pendaftaran harus diverifikasi terlebih dahulu oleh admin.'], 400);
        }

        if ($pendaftaran->team_locked) {
            return response()->json(['message' => 'Tim sudah dikunci. Silakan ajukan pembukaan kunci jika ingin mengubah anggota.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $targetUser = User::where('email', $request->email)->first();

        if (!$targetUser) {
            return response()->json(['message' => 'Email tidak terdaftar di sistem. Silakan minta rekan Anda membuat akun terlebih dahulu.'], 404);
        }

        if ($targetUser->id === $pendaftaran->user_id) {
            return response()->json(['message' => 'Anda adalah ketua tim.'], 400);
        }

        $maxSize = $this->getMaxMembers($pendaftaran->lomba->team_requirements);
        if ($maxSize <= 1) {
            return response()->json(['message' => 'Kompetisi ini adalah kompetisi individu.'], 400);
        }

        $currentMembers = $pendaftaran->team_members ?: [];
        $currentSize = 1 + count($currentMembers);

        if ($currentSize >= $maxSize) {
            return response()->json(['message' => 'Jumlah anggota tim sudah mencapai batas maksimal (' . $maxSize . ' orang).'], 400);
        }

        // Check duplicate in this team
        foreach ($currentMembers as $m) {
            if (strtolower($m['email']) === strtolower($targetUser->email)) {
                return response()->json(['message' => 'Rekan Anda sudah diundang atau bergabung dalam tim ini.'], 400);
            }
        }

        // Check if already registered in the same competition as leader
        $isLeader = Pendaftaran::where('user_id', $targetUser->id)
            ->where('lomba_id', $pendaftaran->lomba_id)
            ->exists();

        if ($isLeader) {
            return response()->json(['message' => 'Rekan Anda sudah terdaftar di lomba ini sebagai ketua tim lain.'], 400);
        }

        // Check if already registered in the same competition as joined member
        $otherRegs = Pendaftaran::where('lomba_id', $pendaftaran->lomba_id)
            ->whereNotNull('team_members')
            ->get();

        foreach ($otherRegs as $otherReg) {
            $mList = $otherReg->team_members ?: [];
            foreach ($mList as $m) {
                if (strtolower($m['email']) === strtolower($targetUser->email) && $m['status'] === 'joined') {
                    return response()->json(['message' => 'Rekan Anda sudah terdaftar dan bergabung di tim lain untuk lomba ini.'], 400);
                }
            }
        }

        // Add member
        $currentMembers[] = [
            'user_id' => $targetUser->id,
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'status' => 'pending'
        ];

        $pendaftaran->update(['team_members' => $currentMembers]);

        // Notify target user
        Notification::create([
            'user_id' => $targetUser->id,
            'judul' => 'Undangan Bergabung Tim',
            'pesan' => "Kamu diundang oleh {$user->name} untuk bergabung ke dalam tim {$pendaftaran->team_name} untuk kompetisi {$pendaftaran->lomba->title}.",
        ]);

        return response()->json([
            'message' => 'Undangan berhasil dikirim.',
            'data' => $pendaftaran
        ]);
    }

    public function acceptInvite(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        $user = $request->user();
        $members = $pendaftaran->team_members ?: [];

        $found = false;
        $joinedCount = 1; // Start with leader
        $hasPending = false;

        foreach ($members as &$m) {
            if (strtolower($m['email']) === strtolower($user->email)) {
                if ($m['status'] !== 'pending') {
                    return response()->json(['message' => 'Anda sudah bergabung di tim ini.'], 400);
                }
                $m['status'] = 'joined';
                $found = true;
            }
            if ($m['status'] === 'joined') {
                $joinedCount++;
            } else {
                $hasPending = true;
            }
        }

        if (!$found) {
            return response()->json(['message' => 'Undangan tidak ditemukan.'], 404);
        }

        $pendaftaran->team_members = $members;
        
        $maxSize = $this->getMaxMembers($pendaftaran->lomba->team_requirements);
        if ($joinedCount >= $maxSize && !$hasPending) {
            $pendaftaran->team_locked = true;
        }

        $pendaftaran->save();

        // Notify leader
        Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Undangan Diterima',
            'pesan' => "{$user->name} telah bergabung ke dalam tim Anda ({$pendaftaran->team_name})." . ($pendaftaran->team_locked ? " Tim Anda sekarang penuh dan dikunci." : ""),
        ]);

        return response()->json(['message' => 'Berhasil bergabung dengan tim.']);
    }

    public function declineInvite(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        $user = $request->user();
        $members = $pendaftaran->team_members ?: [];

        $found = false;
        $newMembers = [];

        foreach ($members as $m) {
            if (strtolower($m['email']) === strtolower($user->email)) {
                if ($m['status'] !== 'pending') {
                    return response()->json(['message' => 'Anda sudah menjadi bagian dari tim ini.'], 400);
                }
                $found = true;
            } else {
                $newMembers[] = $m;
            }
        }

        if (!$found) {
            return response()->json(['message' => 'Undangan tidak ditemukan.'], 404);
        }

        $pendaftaran->team_members = $newMembers;
        $pendaftaran->save();

        // Notify leader
        Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Undangan Ditolak',
            'pesan' => "{$user->name} menolak undangan bergabung ke tim {$pendaftaran->team_name}.",
        ]);

        return response()->json(['message' => 'Undangan berhasil ditolak.']);
    }

    public function removeMember(Request $request, Pendaftaran $pendaftaran, $userId): JsonResponse
    {
        $user = $request->user();

        if ($pendaftaran->user_id !== $user->id) {
            return response()->json(['message' => 'Hanya ketua tim yang dapat mengeluarkan anggota.'], 403);
        }

        if ($pendaftaran->team_locked) {
            return response()->json(['message' => 'Tim sudah dikunci. Silakan ajukan pembukaan kunci terlebih dahulu.'], 400);
        }

        $members = $pendaftaran->team_members ?: [];
        $newMembers = [];
        $removedMember = null;

        foreach ($members as $m) {
            if ((int)$m['user_id'] === (int)$userId) {
                $removedMember = $m;
            } else {
                $newMembers[] = $m;
            }
        }

        if (!$removedMember) {
            return response()->json(['message' => 'Anggota tidak ditemukan.'], 404);
        }

        $pendaftaran->team_members = $newMembers;
        $pendaftaran->save();

        // Notify target user
        Notification::create([
            'user_id' => $userId,
            'judul' => 'Dikeluarkan dari Tim',
            'pesan' => "Kamu telah dikeluarkan dari tim {$pendaftaran->team_name} untuk kompetisi {$pendaftaran->lomba->title} oleh ketua tim.",
        ]);

        return response()->json(['message' => 'Anggota berhasil dikeluarkan.']);
    }

    public function lockTeam(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if ($pendaftaran->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Hanya ketua tim yang dapat mengunci tim.'], 403);
        }

        $pendaftaran->update(['team_locked' => true]);

        return response()->json(['message' => 'Tim berhasil dikunci.']);
    }

    public function requestUnlock(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if ($pendaftaran->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Hanya ketua tim yang dapat mengajukan permohonan buka kunci.'], 403);
        }

        $pendaftaran->update(['unlock_requested' => true]);

        return response()->json(['message' => 'Permohonan buka kunci berhasil diajukan ke admin.']);
    }

    public function adminApproveUnlock(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        $pendaftaran->update([
            'team_locked' => false,
            'unlock_requested' => false
        ]);

        return response()->json(['message' => 'Simulasi Admin: Kunci tim berhasil dibuka.']);
    }
}
