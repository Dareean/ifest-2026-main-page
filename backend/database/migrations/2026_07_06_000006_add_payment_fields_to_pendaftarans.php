<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('auto_lock_at');
            $table->enum('payment_status', ['unpaid', 'pending', 'verified', 'rejected'])->default('unpaid')->after('payment_proof');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_status');
            $table->text('payment_notes')->nullable()->after('payment_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->dropColumn(['payment_proof', 'payment_status', 'payment_verified_at', 'payment_notes']);
        });
    }
};
