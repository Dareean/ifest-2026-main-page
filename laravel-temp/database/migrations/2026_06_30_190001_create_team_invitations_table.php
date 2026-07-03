<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->foreignId('invited_by_user_id')->constrained('users');
            $table->foreignId('invited_user_id')->nullable()->constrained('users');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['pendaftaran_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};
