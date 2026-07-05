<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EmailVerification;
use App\Models\Notification;
use App\Models\Pendaftaran;
use App\Models\Submission;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        $data = [
            'total_users' => $totalUsers,
            'total_pendaftarans' => $totalPendaftarans,
            'by_status' => $byStatus,
            'by_lomba' => $byLomba,
            'pending_unlock_requests' => $pendingUnlock,
            'recent_registrations' => $recentRegistrations,
        ];

        return response()->json($data);
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
        ]);

        Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Pendaftaran Diverifikasi',
            'pesan' => "Pendaftaran untuk lomba {$pendaftaran->lomba->title} telah diverifikasi oleh admin. Kamu sekarang dapat menambahkan anggota tim.",
        ]);

        ActivityLog::create([
            'admin_id' => request()->user()->id,
            'action' => 'verify',
            'target_type' => 'pendaftaran',
            'target_id' => $pendaftaran->id,
            'metadata' => ['lomba' => $pendaftaran->lomba->title, 'user' => $pendaftaran->user->name],
        ]);

        Cache::forget('admin_stats');

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

        ActivityLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'reject',
            'target_type' => 'pendaftaran',
            'target_id' => $pendaftaran->id,
            'metadata' => ['lomba' => $pendaftaran->lomba->title, 'user' => $pendaftaran->user->name, 'notes' => $request->notes],
        ]);

        Cache::forget('admin_stats');

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
            'auto_lock_at' => now()->addMinutes(5),
        ]);

        Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Buka Kunci Tim Disetujui',
            'pesan' => 'Permohonan buka kunci tim telah disetujui. Kamu memiliki waktu 5 menit untuk mengubah anggota tim sebelum sistem mengunci otomatis.',
        ]);

        ActivityLog::create([
            'admin_id' => request()->user()->id,
            'action' => 'approve_unlock',
            'target_type' => 'pendaftaran',
            'target_id' => $pendaftaran->id,
            'metadata' => ['lomba' => $pendaftaran->lomba->title, 'user' => $pendaftaran->user->name],
        ]);

        Cache::forget('admin_stats');

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

        $emails = [];
        foreach ($users as $user) {
            $notif = Notification::create([
                'user_id' => $user->id,
                'judul' => $request->judul,
                'pesan' => $request->pesan,
            ]);
            if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = ['email' => $user->email, 'notif_id' => $notif->id];
            }
        }

        $judul = $request->judul;
        $apiKey = env('BREVO_API_KEY');
        foreach ($emails as $item) {
            $notif = Notification::find($item['notif_id']);
            if (!$notif) continue;
            try {
                $html = view('emails.notification', ['notification' => $notif])->render();
                if ($apiKey) {
                    $res = Http::timeout(10)->withHeaders([
                        'api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])->post('https://api.brevo.com/v3/smtp/email', [
                        'sender' => ['email' => config('mail.from.address', 'noreply@ifest2026.com'), 'name' => 'I-FEST 2026'],
                        'to' => [['email' => $item['email']]],
                        'subject' => $judul,
                        'htmlContent' => $html,
                    ]);
                    if ($res->failed()) {
                        Log::error('Brevo API error', ['to' => $item['email'], 'status' => $res->status(), 'body' => $res->body()]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Send email failed: ' . $e->getMessage(), $item);
            }
        }

        ActivityLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'broadcast_notification',
            'target_type' => $request->filled('user_ids') ? 'users' : 'all',
            'metadata' => ['judul' => $request->judul, 'recipient_count' => $users->count()],
        ]);

        return response()->json(['message' => 'Notifikasi terkirim ke ' . $users->count() . ' pengguna']);
    }

    public function notifications(): JsonResponse
    {
        $data = Notification::with('user:id,name,email')
            ->latest()
            ->paginate(20);
        return response()->json($data);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Anda tidak bisa mengubah role Anda sendiri'], 400);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:user,admin,super_admin',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update([
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => "Role user {$user->name} berhasil diubah menjadi {$request->role}",
            'data' => $user,
        ]);
    }

    public function activityLogs(): JsonResponse
    {
        $data = ActivityLog::with('admin:id,name')
            ->latest()
            ->paginate(20);

        return response()->json($data);
    }

    public function exportPendaftarans(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = Pendaftaran::with('user:id,name,email', 'lomba:id,kode,title', 'submission:id,pendaftaran_id,link_drive');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('lomba_id')) {
            $query->where('lomba_id', $request->lomba_id);
        }

        $pendaftarans = $query->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="daftar_peserta_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($pendaftarans) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['No', 'Nama Tim', 'Lomba', 'Ketua', 'Email Ketua', 'Status', 'Link Karya', 'Tanggal Daftar']);

            foreach ($pendaftarans as $i => $p) {
                fputcsv($file, [
                    $i + 1,
                    $p->team_name,
                    $p->lomba->title,
                    $p->user->name,
                    $p->user->email,
                    $p->status,
                    $p->submission?->link_drive,
                    $p->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function destroyUser(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Anda tidak bisa menghapus akun Anda sendiri'], 400);
        }

        $userName = $user->name;

        // Delete related data
        Pendaftaran::where('user_id', $user->id)->delete();
        TeamInvitation::where('invited_by_user_id', $user->id)->orWhere('invited_user_id', $user->id)->delete();
        Notification::where('user_id', $user->id)->delete();
        EmailVerification::where('email', $user->email)->delete();
        Submission::whereHas('pendaftaran', fn($q) => $q->where('user_id', $user->id))->delete();
        ActivityLog::where('admin_id', $user->id)->delete();

        ActivityLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'delete_user',
            'target_type' => 'user',
            'target_id' => $user->id,
            'metadata' => ['name' => $userName, 'email' => $user->email],
        ]);

        $user->delete();

        return response()->json(['message' => "Akun {$userName} berhasil dihapus"]);
    }

    public function admins(): JsonResponse
    {
        $data = User::whereIn('role', ['admin', 'super_admin'])
            ->withCount('pendaftarans')
            ->latest()
            ->paginate(50);

        return response()->json($data);
    }
}
