<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->text('ig_follow_proof')->nullable()->after('status');
            $table->text('ig_twibbon_proof')->nullable()->after('ig_follow_proof');
            $table->boolean('social_validated')->default(false)->after('ig_twibbon_proof');
        });
    }

    public function down(): void
    {
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->dropColumn(['ig_follow_proof', 'ig_twibbon_proof', 'social_validated']);
        });
    }
};
