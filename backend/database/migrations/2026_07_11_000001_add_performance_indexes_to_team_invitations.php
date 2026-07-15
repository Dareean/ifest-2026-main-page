<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->index(['pendaftaran_id', 'status'], 'ti_pendaftaran_status_idx');
            $table->index(['invited_user_id', 'status'], 'ti_invited_user_status_idx');
            $table->index(['pendaftaran_id', 'created_at'], 'ti_pendaftaran_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->dropIndex('ti_pendaftaran_status_idx');
            $table->dropIndex('ti_invited_user_status_idx');
            $table->dropIndex('ti_pendaftaran_created_idx');
        });
    }
};
