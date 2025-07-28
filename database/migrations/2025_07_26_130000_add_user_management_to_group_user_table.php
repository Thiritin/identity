<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            // This migration is handled by the later migration file
            // 2025_07_26_140000_update_group_user_level_to_string.php
            // Just ensure we have the necessary structure prepared
            if (!Schema::hasColumn('group_user', 'can_manage_users')) {
                $table->boolean('can_manage_users')->default(false)->after('level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            if (Schema::hasColumn('group_user', 'can_manage_users')) {
                $table->dropColumn('can_manage_users');
            }
        });
    }
};