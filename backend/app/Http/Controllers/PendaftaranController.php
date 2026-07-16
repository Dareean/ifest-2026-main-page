<?php

namespace App\Http\Controllers;

use App\Models\Lomba;
use App\Models\Pendaftaran;
use App\Models\Notification;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $all = $owned->merge($memberOfViaInvitation)->unique('id')->sortByDesc('created_at')->values();

        return response()->json(['data' => $all]);
    }

    public function store(Request $request, Lomba $lomba): JsonResponse
    {
        return DB::transaction(function () use ($request, $lomba) {
            $userId = $request->user()->id;

            $exists = Pendaftaran::where('user_id', $userId)
                ->where('lomba_id', $lomba->id)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Kamu sudah terdaftar di lomba ini'], 409);
            }

            // Check per kategori: 1 akun hanya boleh mendaftar 1 lomba per kategori
            $kategori = explode('-', $lomba->kode)[0];
            $existingKategori = Pendaftaran::where('user_id', $userId)
                ->whereHas('lomba', function ($q) use ($kategori) {
                    $q->where('kode', 'like', $kategori . '-%');
                })
                ->lockForUpdate()
                ->exists();
            if ($existingKategori) {
                return response()->json(['message' => 'Kamu sudah terdaftar di lomba lain di kategori ini. 1 akun hanya bisa mendaftar 1 lomba per kategori.'], 400);
            }
            // Also check if user is an accepted member of another team in the same category
            $existingMember = TeamInvitation::where('invited_user_id', $userId)
                ->where('status', 'accepted')
                ->whereHas('pendaftaran.lomba', function ($q) use ($kategori) {
                    $q->where('kode', 'like', $kategori . '-%');
                })
                ->lockForUpdate()
                ->exists();
            if ($existingMember) {
                return response()->json(['message' => 'Kamu sudah menjadi anggota tim lain di kategori ini. 1 akun hanya bisa mendaftar 1 lomba per kategori.'], 400);
            }

            // SIMULASI: bypass gelombang — semua lomba terbuka
            $gelombang = '1';

            $isTeam = $lomba->getMaxMembers() > 1;

            $rules = [
                'team_name' => $isTeam ? 'required|string|max:255' : 'nullable|string|max:255',
            ];

            $messages = $isTeam ? [
                'team_name.required' => 'Nama tim wajib diisi untuk lomba beregu',
            ] : [];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $teamName = $request->has('team_name') ? trim(preg_replace('/[^a-zA-Z0-9\s\-_.]+/u', '', $request->team_name)) : null;

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

            try {
                $pendaftaran = Pendaftaran::create([
                    'user_id' => $userId,
                    'lomba_id' => $lomba->id,
                    'team_name' => $teamName,
                    'gelombang' => $gelombang,
                    'team_locked' => false,
                    'payment_status' => strtolower(trim($lomba->fee)) === 'gratis' ? 'verified' : 'unpaid',
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'UNIQUE constraint failed') || $e->getCode() == 23000) {
                    return response()->json([
                        'errors' => ['team_name' => ["Nama tim '$teamName' sudah digunakan. Silakan pilih nama lain."]]
                    ], 422);
                }
                Log::error('Registration failed: ' . $e->getMessage());
                return response()->json(['message' => 'Terjadi kesalahan. Silakan coba lagi.'], 500);
            }

            Notification::create([
                'user_id' => $userId,
                'judul' => 'Pendaftaran Berhasil',
                'pesan' => "Kamu berhasil mendaftar di lomba {$lomba->title} (Gelombang {$gelombang}). Tim kami akan memverifikasi pendaftaranmu.",
            ]);

            return response()->json([
                'message' => 'Pendaftaran berhasil',
                'data' => $pendaftaran->load('lomba'),
            ], 201);
        });
    }

    public function show(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        $user = $request->user();
        $isLeader = $pendaftaran->user_id === $user->id;
        $isMember = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
            ->where('invited_user_id', $user->id)
            ->where('status', 'accepted')
            ->exists();

        if (!$isLeader && !$isMember) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $acceptedMembers = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
            ->where('status', 'accepted')
            ->with('invitedUser:id,name,email')
            ->get()
            ->map(fn ($inv) => [
                'name' => $inv->invitedUser->name,
                'email' => $inv->invitedUser->email,
            ]);

        // Prepend the team leader to the members list
        $leader = [
            'name' => $pendaftaran->user->name,
            'email' => $pendaftaran->user->email,
        ];
        $acceptedMembers->prepend($leader);

        return response()->json([
            'data' => $pendaftaran->load('lomba', 'submission', 'user')
                ->setAttribute('accepted_members', $acceptedMembers),
        ]);
    }

    public function uploadPayment(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if ($pendaftaran->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($pendaftaran->isFree()) {
            return response()->json(['message' => 'Lomba ini gratis, tidak perlu upload bukti bayar'], 400);
        }

        $validator = Validator::make($request->all(), [
            'payment_proof' => 'required|url|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Atomic guard: only update if not already verified — prevents race with admin verify
        $updated = Pendaftaran::where('id', $pendaftaran->id)
            ->where('payment_status', '!=', 'verified')
            ->update([
                'payment_proof' => $request->payment_proof,
                'payment_status' => 'pending',
                'payment_notes' => null,
            ]);

        if ($updated === 0) {
            return response()->json(['message' => 'Pembayaran sudah diverifikasi'], 400);
        }

        $pendaftaran->refresh();

        Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Bukti Pembayaran Terkirim',
            'pesan' => "Bukti pembayaran untuk lomba {$pendaftaran->lomba->title} telah diterima. Tim kami akan memverifikasinya.",
        ]);

        // Notify admins
        try {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'judul' => 'Bukti Pembayaran Baru',
                    'pesan' => "Tim {$pendaftaran->team_name} mengirimkan bukti pembayaran untuk {$pendaftaran->lomba->title}.",
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Gagal notifikasi admin saat upload payment: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Bukti pembayaran berhasil dikirim',
            'data' => $pendaftaran->fresh()->load('lomba'),
        ]);
    }

    public function requestUnlock(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if ($pendaftaran->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$pendaftaran->team_locked) {
            return response()->json(['message' => 'Tim tidak terkunci'], 400);
        }

        if ($pendaftaran->unlock_requested) {
            return response()->json(['message' => 'Permintaan buka kunci sudah dikirim'], 400);
        }

        $pendaftaran->update(['unlock_requested' => true]);

        // Notify admins
        try {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'judul' => 'Permintaan Buka Kunci Tim',
                    'pesan' => "Tim {$pendaftaran->team_name} ({$pendaftaran->lomba->title}) meminta buka kunci tim.",
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Gagal notifikasi admin saat request unlock: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Permintaan buka kunci terkirim',
            'data' => $pendaftaran->fresh()->load('lomba'),
        ]);
    }

    public function uploadSocialProof(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if ($pendaftaran->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:follow,twibbon',
            'proof_url' => 'required|url|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $column = $request->type === 'follow' ? 'ig_follow_proof' : 'ig_twibbon_proof';

        $pendaftaran->update([$column => $request->proof_url]);

        $bothFilled = $pendaftaran->fresh()->ig_follow_proof && $pendaftaran->fresh()->ig_twibbon_proof;
        if ($bothFilled) {
            $pendaftaran->update(['social_validated' => true]);
        }

        return response()->json([
            'message' => 'Bukti ' . ($request->type === 'follow' ? 'follow' : 'twibbon') . ' berhasil disimpan',
            'data' => $pendaftaran->fresh()->load('lomba'),
        ]);
    }
}
