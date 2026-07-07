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
        $recentRegistrations = Pendaftaran::with('user:id,name,email', 'lomba:id,kode,title')
            ->latest()->take(5)->get();

        $data = [
            'total_users' => $totalUsers,
            'total_pendaftarans' => $totalPendaftarans,
            'by_status' => $byStatus,
            'by_lomba' => $byLomba,
            'pending_unlock_requests' => 0,
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

        // If not free, payment must be verified first
        if (!$pendaftaran->isFree() && $pendaftaran->payment_status !== 'verified') {
            return response()->json(['message' => 'Pembayaran belum diverifikasi. Verifikasi pembayaran terlebih dahulu sebelum memverifikasi tim.'], 400);
        }

        // Team must have filled all member slots
        $maxMembers = $pendaftaran->lomba->getMaxMembers();
        $acceptedCount = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
            ->where('status', 'accepted')
            ->count();
        if (1 + $acceptedCount < $maxMembers) {
            return response()->json(['message' => 'Tim belum mengisi semua kuota anggota. Isi slot terlebih dahulu sebelum verifikasi tim (' . (1 + $acceptedCount) . '/' . $maxMembers . ' terisi).'], 400);
        }

        $pendaftaran->update(['status' => 'verified']);

        $notif = Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Pendaftaran Diverifikasi',
            'pesan' => "Pendaftaran untuk lomba {$pendaftaran->lomba->title} telah diverifikasi. Selamat mengikuti lomba!",
        ]);
        $this->sendEmailBrevo($notif->fresh()->load('user'));

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

        $notif = Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Pendaftaran Ditolak',
            'pesan' => "Pendaftaran untuk lomba {$pendaftaran->lomba->title} ditolak. " . ($request->notes ? "Catatan: {$request->notes}" : ''),
        ]);

        $this->sendEmailBrevo($notif->fresh()->load('user'));

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

    public function verifyPayment(Pendaftaran $pendaftaran): JsonResponse
    {
        if ($pendaftaran->payment_status !== 'pending') {
            return response()->json(['message' => 'Tidak ada bukti pembayaran yang perlu diverifikasi'], 400);
        }

        $pendaftaran->update([
            'payment_status' => 'verified',
            'payment_verified_at' => now(),
        ]);

        $notif = Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Pembayaran Diverifikasi',
            'pesan' => "Pembayaran untuk lomba {$pendaftaran->lomba->title} telah diverifikasi. Silakan undang anggota tim kamu untuk mengisi slot yang tersedia.",
        ]);
        $this->sendEmailBrevo($notif->fresh()->load('user'));

        ActivityLog::create([
            'admin_id' => request()->user()->id,
            'action' => 'verify_payment',
            'target_type' => 'pendaftaran',
            'target_id' => $pendaftaran->id,
            'metadata' => ['lomba' => $pendaftaran->lomba->title, 'user' => $pendaftaran->user->name],
        ]);

        Cache::forget('admin_stats');

        return response()->json(['message' => 'Pembayaran berhasil diverifikasi', 'data' => $pendaftaran->fresh()->load('lomba', 'user')]);
    }

    public function batchVerify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:pendaftarans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $results = ['success' => 0, 'skipped' => 0, 'errors' => []];
        $pendaftarans = Pendaftaran::whereIn('id', $request->ids)->with('lomba', 'user')->get();

        foreach ($pendaftarans as $p) {
            try {
                if ($p->status !== 'pending') {
                    $results['skipped']++;
                    continue;
                }
                if (!$p->isFree() && $p->payment_status !== 'verified') {
                    $results['skipped']++;
                    continue;
                }

                $p->update(['status' => 'verified']);

                $notif = Notification::create([
                    'user_id' => $p->user_id,
                    'judul' => 'Pendaftaran Diverifikasi',
                    'pesan' => "Pendaftaran untuk lomba {$p->lomba->title} telah diverifikasi. Selamat mengikuti lomba!",
                ]);
                $this->sendEmailBrevo($notif->fresh()->load('user'));

                ActivityLog::create([
                    'admin_id' => $request->user()->id,
                    'action' => 'verify',
                    'target_type' => 'pendaftaran',
                    'target_id' => $p->id,
                    'metadata' => ['lomba' => $p->lomba->title, 'user' => $p->user->name],
                ]);

                $results['success']++;
            } catch (\Exception $e) {
                $results['errors'][] = "ID {$p->id}: {$e->getMessage()}";
            }
        }

        Cache::forget('admin_stats');

        return response()->json([
            'message' => "{$results['success']} pendaftaran berhasil diverifikasi, {$results['skipped']} dilewati",
            'results' => $results,
        ]);
    }

    public function batchReject(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:pendaftarans,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $results = ['success' => 0, 'skipped' => 0, 'errors' => []];
        $pendaftarans = Pendaftaran::whereIn('id', $request->ids)->with('lomba', 'user')->get();

        foreach ($pendaftarans as $p) {
            try {
                if ($p->status !== 'pending') {
                    $results['skipped']++;
                    continue;
                }

                $p->update([
                    'status' => 'rejected',
                    'notes' => $request->notes,
                ]);

                $notif = Notification::create([
                    'user_id' => $p->user_id,
                    'judul' => 'Pendaftaran Ditolak',
                    'pesan' => "Pendaftaran untuk lomba {$p->lomba->title} ditolak. " . ($request->notes ? "Catatan: {$request->notes}" : ''),
                ]);

                $this->sendEmailBrevo($notif->fresh()->load('user'));

                ActivityLog::create([
                    'admin_id' => $request->user()->id,
                    'action' => 'reject',
                    'target_type' => 'pendaftaran',
                    'target_id' => $p->id,
                    'metadata' => ['lomba' => $p->lomba->title, 'user' => $p->user->name, 'notes' => $request->notes],
                ]);

                $results['success']++;
            } catch (\Exception $e) {
                $results['errors'][] = "ID {$p->id}: {$e->getMessage()}";
            }
        }

        Cache::forget('admin_stats');

        return response()->json([
            'message' => "{$results['success']} pendaftaran ditolak, {$results['skipped']} dilewati",
            'results' => $results,
        ]);
    }

    public function rejectPayment(Request $request, Pendaftaran $pendaftaran): JsonResponse
    {
        if ($pendaftaran->payment_status !== 'pending') {
            return response()->json(['message' => 'Tidak ada bukti pembayaran yang perlu diverifikasi'], 400);
        }

        $validator = Validator::make($request->all(), [
            'payment_notes' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pendaftaran->update([
            'payment_status' => 'rejected',
            'payment_notes' => $request->payment_notes,
        ]);

        $notif = Notification::create([
            'user_id' => $pendaftaran->user_id,
            'judul' => 'Bukti Pembayaran Ditolak',
            'pesan' => "Bukti pembayaran untuk lomba {$pendaftaran->lomba->title} ditolak. Alasan: {$request->payment_notes}. Silakan upload ulang bukti pembayaran yang valid.",
        ]);

        $this->sendEmailBrevo($notif->fresh()->load('user'));

        ActivityLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'reject_payment',
            'target_type' => 'pendaftaran',
            'target_id' => $pendaftaran->id,
            'metadata' => ['lomba' => $pendaftaran->lomba->title, 'user' => $pendaftaran->user->name, 'notes' => $request->payment_notes],
        ]);

        Cache::forget('admin_stats');

        return response()->json(['message' => 'Bukti pembayaran ditolak', 'data' => $pendaftaran->fresh()->load('lomba', 'user')]);
    }

    private function sendEmailBrevo(Notification $notif): void
    {
        if (!filter_var($notif->user->email, FILTER_VALIDATE_EMAIL)) return;

        $apiKey = env('BREVO_API_KEY');
        if (!$apiKey) return;

        try {
            $html = view('emails.notification', ['notification' => $notif])->render();
            Http::timeout(10)->withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => ['email' => config('mail.from.address', 'noreply@ifest2026.com'), 'name' => 'I-FEST 2026'],
                'to' => [['email' => $notif->user->email]],
                'subject' => 'I-FEST 2026: ' . $notif->judul,
                'htmlContent' => $html,
            ]);
        } catch (\Exception $e) {
            Log::error('Send Brevo email failed: ' . $e->getMessage(), ['to' => $notif->user->email]);
        }
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
            'role' => 'required|string|in:user,admin',
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
        $data = User::where('role', 'admin')
            ->withCount('pendaftarans')
            ->latest()
            ->paginate(50);

        return response()->json($data);
    }
}
