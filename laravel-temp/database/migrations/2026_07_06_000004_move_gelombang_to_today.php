<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lombas')->update([
            'gelombang_1_start' => '2026-07-05',
        ]);
    }

    public function down(): void
    {
        DB::table('lombas')->update([
            'gelombang_1_start' => '2026-07-11',
        ]);
    }
};
