<?php

namespace App\Console\Commands;

use App\Models\Lomba;
use App\Models\Notification;
use App\Models\Pendaftaran;
use App\Models\TeamInvitation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SendTeamReminders extends Command
{
    protected $signature = 'app:send-team-reminders';
    protected $description = 'Send reminders to team leaders who have not filled all member slots approaching deadline';

    public function handle(): int
    {
        $reminderDays = [14, 7, 3, 2, 1];
        $today = Carbon::today();
        $sent = 0;

        $pendaftarans = Pendaftaran::with('lomba', 'user')
            ->where('status', 'pending')
            ->get();

        foreach ($pendaftarans as $pendaftaran) {
            $lomba = $pendaftaran->lomba;
            if (!$lomba) continue;

            $deadline = $lomba->gelombang_2_end ?? $lomba->gelombang_1_end;
            if (!$deadline) continue;

            $deadline = Carbon::parse($deadline);
            $daysUntil = (int) $today->diffInDays($deadline, false);

            if ($daysUntil < 0) continue;

            if (!in_array($daysUntil, $reminderDays)) continue;

            if ($pendaftaran->last_reminder_sent_at && Carbon::parse($pendaftaran->last_reminder_sent_at)->isToday()) {
                continue;
            }

            // Check if team is already full
            $acceptedCount = TeamInvitation::where('pendaftaran_id', $pendaftaran->id)
                ->where('status', 'accepted')
                ->count();
            $maxMembers = $lomba->getMaxMembers();
            $isFull = (1 + $acceptedCount) >= $maxMembers;

            if ($isFull) continue;

            $remainingSlots = $maxMembers - (1 + $acceptedCount);
            $hariLabel = $daysUntil === 1 ? 'H-1 (besok)' : "H-{$daysUntil}";

            Notification::create([
                'user_id' => $pendaftaran->user_id,
                'judul' => "Peringatan: Slot Tim — {$hariLabel}",
                'pesan' => "Tim kamu untuk lomba {$lomba->title} masih memiliki {$remainingSlots} slot anggota yang belum terisi. Segera undang anggota tim sebelum batas pendaftaran berakhir.",
            ]);

            $this->sendEmailReminder($pendaftaran, $lomba, $remainingSlots, $hariLabel);

            $pendaftaran->update(['last_reminder_sent_at' => now()]);
            $sent++;
        }

        $this->info("Sent {$sent} team reminders.");
        return Command::SUCCESS;
    }

    private function sendEmailReminder(Pendaftaran $pendaftaran, Lomba $lomba, int $remainingSlots, string $hariLabel): void
    {
        $apiKey = config('services.brevo.api_key');
        if (!$apiKey) return;

        $user = $pendaftaran->user;
        if (!$user?->email) return;

        $teamName = $pendaftaran->team_name ?: 'Tim ' . $user->name;

        $html = view('emails.notification', [
            'judul' => "Peringatan: Slot Tim — {$hariLabel}",
            'pesan' => "Halo {$user->name},<br><br>"
                . "Tim <strong>{$teamName}</strong> untuk lomba <strong>{$lomba->title}</strong> masih memiliki <strong>{$remainingSlots}</strong> slot anggota yang belum terisi.<br><br>"
                . "Segera undang anggota tim kamu melalui dashboard sebelum batas pendaftaran berakhir.<br><br>"
                . "Terima kasih,<br>Panitia I-FEST 2026",
        ])->render();

        try {
            Http::withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => ['email' => 'noreply@ifest.com', 'name' => 'I-FEST 2026'],
                'to' => [['email' => $user->email, 'name' => $user->name]],
                'subject' => "Peringatan: Slot Tim — {$hariLabel}",
                'htmlContent' => $html,
            ]);
        } catch (\Exception $e) {
            $this->error("Failed to send email to {$user->email}: {$e->getMessage()}");
        }
    }
}
