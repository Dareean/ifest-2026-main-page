<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lombas')->where('kode', 'REG-03')->update(['is_active' => false]);
    }

    public function down(): void
    {
        DB::table('lombas')->where('kode', 'REG-03')->update(['is_active' => true]);
    }
};
