<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lombas', function (Blueprint $table) {
            $table->json('payment_accounts')->nullable()->after('fee_gelombang_2');
        });
    }

    public function down(): void
    {
        Schema::table('lombas', function (Blueprint $table) {
            $table->dropColumn('payment_accounts');
        });
    }
};
