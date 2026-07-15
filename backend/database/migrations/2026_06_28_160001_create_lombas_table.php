<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lombas', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('title');
            $table->string('scale');
            $table->string('tagline');
            $table->string('fee');
            $table->string('target');
            $table->string('team_requirements');
            $table->string('languages');
            $table->string('babak');
            $table->string('description');
            $table->text('long_description');
            $table->text('rules')->nullable();
            $table->string('schedule');
            $table->string('registration_link');
            $table->string('guidebook_link');
            $table->string('card_bg')->default('#DCEEB1');
            $table->string('accent_color')->default('#FF3D8B');
            $table->string('text_color')->default('#04000D');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lombas');
    }
};
