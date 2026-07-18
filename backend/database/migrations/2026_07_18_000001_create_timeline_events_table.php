<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('timeline_events')) {
            return;
        }

        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();
            $table->string('phase', 10);
            $table->string('title');
            $table->string('date_range');
            $table->json('description_items')->nullable();
            $table->string('accent_color', 20)->nullable();
            $table->string('status')->default('upcoming'); // upcoming, ongoing, completed
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_events');
    }
};
