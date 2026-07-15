<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE UNIQUE INDEX pendaftarans_team_name_unique
            ON pendaftarans (lomba_id, LOWER(team_name))
            WHERE team_name IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS pendaftarans_team_name_unique');
    }
};
