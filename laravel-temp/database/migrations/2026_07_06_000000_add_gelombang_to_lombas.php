<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lombas', function (Blueprint $table) {
            $table->date('gelombang_1_start')->nullable()->after('schedule');
            $table->date('gelombang_1_end')->nullable()->after('gelombang_1_start');
            $table->date('gelombang_2_end')->nullable()->after('gelombang_1_end');
            $table->string('fee_gelombang_1')->nullable()->after('fee');
            $table->string('fee_gelombang_2')->nullable()->after('fee_gelombang_1');
        });
    }

    public function down(): void
    {
        Schema::table('lombas', function (Blueprint $table) {
            $table->dropColumn(['gelombang_1_start', 'gelombang_1_end', 'gelombang_2_end', 'fee_gelombang_1', 'fee_gelombang_2']);
        });
    }
};
