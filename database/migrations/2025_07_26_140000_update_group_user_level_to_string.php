<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            // Remove old columns if they exist
            if (Schema::hasColumn('group_user', 'authorization_level')) {
                $table->dropColumn('authorization_level');
            }
            if (Schema::hasColumn('group_user', 'is_director')) {
                $table->dropColumn('is_director');
            }
            // Add new string column for level if not present
            if (!Schema::hasColumn('group_user', 'level')) {
                $table->string('level', 32)->default('member')->after('user_id');
            }
            // Add can_manage_users if not present
            if (!Schema::hasColumn('group_user', 'can_manage_users')) {
                $table->boolean('can_manage_users')->default(false)->after('level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropColumn('level');
            $table->dropColumn('can_manage_users');
            // Optionally restore old columns
            $table->tinyInteger('authorization_level', false, true)->default(1);
            $table->boolean('is_director')->default('0');
        });
    }
};
