<?php

namespace App\Http\Controllers;

use App\Models\Lomba;
use App\Models\Pendaftaran;
use App\Models\Notification;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PendaftaranController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $owned = Pendaftaran::where('user_id', $user->id)
            ->with('lomba', 'submission', 'user')
            ->get();

        $memberOfViaInvitation = Pendaftaran::whereHas('teamInvitations', function ($q) use ($user) {
            $q->where('invited_user_id', $user->id)->where('status', 'accepted');
        })->with('lomba', 'submission', 'user')->get();

        $memberOfViaJson = Pendaftaran::where('status', 'verified')
            ->whereNotNull('team_members')
            ->with('lomba', 'submission', 'user')
            ->get()
            ->filter(function ($p) use ($user) {
                $mList = $p->team_members ?: [];
                foreach ($mList as $m) {
                    if (isset($m['email']) && strtolower($m['email']) === strtolower($user->email) && isset($m['status']) && $m['status'] === 'joined') {
                        return true;
                    }
                }
                return false;
            })
            ->values();

        $all = $owned->merge($memberOfViaInvitation)->merge($memberOfViaJson)->unique('id')->sortByDesc('created_at')->values();

        return response()->json(['data' => $all]);
    }

    public function store(Request $request, Lomba $lomba): JsonResponse
    {
        $exists = Pendaftaran::where('user_id', $request->user()->id)
            ->where('lomba_id', $lomba->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Kamu sudah terdaftar di lomba ini'], 409);
        }

        // Determine which gelombang we're in
        $now = now()->startOfDay();
        $gelombang = null;

        if ($lomba->gelombang_1_start && $lomba->gelombang_1_end) {
            $g1Start = $lomba->gelombang_1_start->startOfDay();
            $g1End = $lomba->gelombang_1_end->startOfDay();

            if ($now->greaterThanOrEqualTo($g1Start) && $now->lessThanOrEqualTo($g1End)) {
                $gelombang = '1';
            } elseif ($lomba->gelombang_2_end) {
                $g2Start = $g1End->copy()->addDay();
                $g2End = $lomba->gelombang_2_end->startOfDay();

                if ($now->greaterThanOrEqualTo($g2Start) && $now->lessThanOrEqualTo($g2End)) {
                    $gelombang = '2';
                }
            }
        }

        if (!$gelombang) {
            return response()->json(['message' => 'Pendaftaran untuk lomba ini belum dibuka atau sudah ditutup.'], 403);
        }

        $isTeam = $lomba->getMaxMembers() > 1;

        $rules = [
            'team_name' => $isTeam ? 'required|string|max:255' : 'nullable|string|max:255',
            'team_members' => 'nullable|array',
            'team_members.*.name' => 'required_with:team_members|string|max:255',
            'team_members.*.email' => 'required_with:team_members|email|max:255',
        ];

        $messages = $isTeam ? [
            'team_name.required' => 'Nama tim wajib diisi untuk lomba beregu',
        ] : [];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $teamName = $request->has('team_name') ? trim($request->team_name) : null;

        if ($teamName && $isTeam) {
            $duplicate = Pendaftaran::where('lomba_id', $lomba->id)
                ->whereRaw('LOWER(team_name) = ?', [strtolower($teamName)])
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'errors' => ['team_name' => ["Nama tim '$teamName' sudah digunakan. Silakan pilih nama lain."]]
                ], 422);
            }
        }

        $pendaftaran = Pendaftaran::create([
            'user_id' => $request->user()->id,
            'lomba_id' => $lomba->id,
            'team_name' => $teamName,
            'team_members' => $request->team_members,
            'gelombang' => $gelombang,
            'payment_status' => strtolower(trim($lomba->fee)) === 'gratis' ? 'verified' : 'unpaid',
        ]);

        Notification::create([
            'user_id' => $request->user()->id,
            'judul' => 'Pendaftaran Berhasil',
            'pesan' => "Kamu berhasil mendaftar di lomba {$lomba->title} (Gelombang {$gelombang}). Tim kami akan memverifikasi pendaftaranmu.",
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil',
            'data' => $pendaftaran->load('lomba'),
        ], 201);
    }

    public function show(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        $user = $request->user();
        $isLeader = $pendaftaran->user_id === $user->id;
        $isMember = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
            ->where('invited_user_id', $user->id)
            ->where('status', 'accepted')
            ->exists();

        if (!$isMember) {
            $mList = $pendaftaran->team_members ?: [];
            foreach ($mList as $m) {
                if (isset($m['email']) && strtolower($m['email']) === strtolower($user->email) && isset($m['status']) && $m['status'] === 'joined') {
                    $isMember = true;
                    break;
                }
            }
        }

        if (!$isLeader && !$isMember) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $pendaftaran->load('lomba', 'submission', 'user')]);
    }

    public function uploadPayment(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if ($pendaftaran->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($pendaftaran->payment_status === 'verified') {
            return response()->json(['message' => 'Pembayaran sudah diverifikasi'], 400);
        }

        if ($pendaftaran->isFree()) {
            return response()->json(['message' => 'Lomba ini gratis, tidak perlu upload bukti bayar'], 400);
        }

        $validator = Validator::make($request->all(), [
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Delete old proof if exists
        if ($pendaftaran->payment_proof) {
            Storage::disk('public')->delete($pendaftaran->payment_proof);
        }

        $path = $request->file('payment_proof')->store('payment-proofs', 'public');

        $pendaftaran->update([
            'payment_proof' => $path,
            'payment_status' => 'pending',
            'payment_notes' => null,
        ]);

        Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Bukti Pembayaran Terkirim',
            'pesan' => "Bukti pembayaran untuk lomba {$pendaftaran->lomba->title} telah diterima. Tim kami akan memverifikasinya.",
        ]);

        // Notify admins
        try {
            $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'judul' => 'Bukti Pembayaran Baru',
                    'pesan' => "Tim {$pendaftaran->team_name} mengupload bukti pembayaran untuk {$pendaftaran->lomba->title}.",
                ]);
            }
        } catch (\Exception $e) {}

        return response()->json([
            'message' => 'Bukti pembayaran berhasil diupload',
            'data' => $pendaftaran->fresh()->load('lomba'),
        ]);
    }
}
