<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalUsers = User::count();
        $totalPendaftarans = Pendaftaran::count();
        $byStatus = Pendaftaran::selectRaw("status, count(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');
        $byLomba = \App\Models\Lomba::withCount('pendaftarans')
            ->get()
            ->map(fn($l) => ['lomba' => $l->kode . ' - ' . $l->title, 'total' => $l->pendaftarans_count]);
        $pendingUnlock = Pendaftaran::where('unlock_requested', true)->count();
        $recentRegistrations = Pendaftaran::with('user:id,name,email', 'lomba:id,kode,title')
            ->latest()->take(5)->get();

        return response()->json([
            'total_users' => $totalUsers,
            'total_pendaftarans' => $totalPendaftarans,
            'by_status' => $byStatus,
            'by_lomba' => $byLomba,
            'pending_unlock_requests' => $pendingUnlock,
            'recent_registrations' => $recentRegistrations,
        ]);
    }

    public function pendaftarans(Request $request): JsonResponse
    {
        $query = Pendaftaran::with('user:id,name,email', 'lomba:id,kode,title', 'submission:id,pendaftaran_id,link_drive');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('lomba_id')) {
            $query->where('lomba_id', $request->lomba_id);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('team_name', 'ilike', "%$s%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'ilike', "%$s%")->orWhere('email', 'ilike', "%$s%"));
            });
        }

        $data = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json($data);
    }

    public function pendaftaranDetail(Pendaftaran $pendaftaran): JsonResponse
    {
        $pendaftaran->load('user', 'lomba', 'submission', 'teamInvitations.invitedUser', 'teamInvitations.invitedBy');
        return response()->json(['data' => $pendaftaran]);
    }

    public function verify(Pendaftaran $pendaftaran): JsonResponse
    {
        if ($pendaftaran->status !== 'pending') {
            return response()->json(['message' => 'Pendaftaran sudah diverifikasi sebelumnya'], 400);
        }

        $pendaftaran->update([
            'status' => 'verified',
            'team_locked' => true,
        ]);

        Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Pendaftaran Diverifikasi',
            'pesan' => "Pendaftaran untuk lomba {$pendaftaran->lomba->title} telah diverifikasi. Tim telah terkunci secara otomatis.",
        ]);

        return response()->json(['message' => 'Pendaftaran berhasil diverifikasi', 'data' => $pendaftaran->fresh()->load('lomba', 'user')]);
    }

    public function reject(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pendaftaran->update([
            'status' => 'rejected',
            'notes' => $request->notes,
        ]);

        Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Pendaftaran Ditolak',
            'pesan' => "Pendaftaran untuk lomba {$pendaftaran->lomba->title} ditolak. " . ($request->notes ? "Catatan: {$request->notes}" : ''),
        ]);

        return response()->json(['message' => 'Pendaftaran ditolak', 'data' => $pendaftaran->fresh()->load('lomba', 'user')]);
    }

    public function approveUnlock(Pendaftaran $pendaftaran): JsonResponse
    {
        if (!$pendaftaran->unlock_requested) {
            return response()->json(['message' => 'Tidak ada permohonan buka kunci untuk tim ini'], 400);
        }

        $pendaftaran->update([
            'team_locked' => false,
            'unlock_requested' => false,
        ]);

        Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Buka Kunci Tim Disetujui',
            'pesan' => 'Permohonan buka kunci tim telah disetujui. Kamu sekarang dapat mengubah anggota tim. Jangan lupa kunci kembali setelah selesai.',
        ]);

        return response()->json(['message' => 'Buka kunci tim disetujui', 'data' => $pendaftaran->fresh()]);
    }

    public function users(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'ilike', "%$s%")
                  ->orWhere('email', 'ilike', "%$s%");
            });
        }

        $data = $query->withCount('pendaftarans')->latest()->paginate($request->per_page ?? 20);
        return response()->json($data);
    }

    public function broadcastNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'pesan' => 'required|string|max:2000',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $users = $request->filled('user_ids')
            ? User::whereIn('id', $request->user_ids)->get()
            : User::all();

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'judul' => $request->judul,
                'pesan' => $request->pesan,
            ]);
        }

        return response()->json(['message' => 'Notifikasi terkirim ke ' . $users->count() . ' pengguna']);
    }
}
