<?php

namespace App\Console\Commands;

use App\Models\TeamInvitation;
use Illuminate\Console\Command;

class CleanupExpiredInvitations extends Command
{
    protected $signature = 'app:cleanup-expired-invitations';

    protected $description = 'Auto-reject expired team invitations';

    public function handle(): void
    {
        $count = TeamInvitation::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'rejected']);

        $this->info("Expired invitations cleaned up: {$count}");
    }
}
