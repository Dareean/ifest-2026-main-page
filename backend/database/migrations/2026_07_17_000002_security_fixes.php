<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('status');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('is_read');
        });

        Schema::table('lombas', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('email_verifications', function (Blueprint $table) {
            $table->unique('email');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->foreign('admin_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['status']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['is_read']);
        });

        Schema::table('lombas', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('email_verifications', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->foreign('admin_id')->references('id')->on('users');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
