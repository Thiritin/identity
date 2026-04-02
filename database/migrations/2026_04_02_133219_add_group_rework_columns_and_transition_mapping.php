<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // On fresh databases (tests), the column already has the new enum values
        // and can_manage_members already exists. Only run data migration for
        // production databases that still have old enum values.
        if (! Schema::hasColumn('group_user', 'can_manage_members')) {
            Schema::table('group_user', function (Blueprint $table) {
                $table->boolean('can_manage_members')->default(false)->after('level');
            });
        }

        // Transition old level values to new model (no-op on fresh databases)
        DB::table('group_user')
            ->whereIn('level', ['owner', 'admin'])
            ->update(['can_manage_members' => true]);

        DB::table('group_user')
            ->whereIn('level', ['invited', 'banned', 'moderator', 'admin'])
            ->update(['level' => 'member']);

        DB::table('group_user')
            ->join('groups', 'groups.id', '=', 'group_user.group_id')
            ->where('group_user.level', 'owner')
            ->update([
                'group_user.level' => DB::raw("CASE
                    WHEN groups.type = 'division' THEN 'division_director'
                    WHEN groups.type = 'team' THEN 'team_lead'
                    WHEN groups.type = 'department' THEN 'director'
                    ELSE 'member'
                END"),
            ]);

        // Ensure ENUM column only allows new values (idempotent)
        DB::statement("ALTER TABLE group_user MODIFY COLUMN level ENUM('member','division_director','director','team_lead') NOT NULL DEFAULT 'member'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE group_user MODIFY COLUMN level ENUM('invited','banned','member','moderator','admin','owner') NOT NULL DEFAULT 'invited'");

        if (Schema::hasColumn('group_user', 'can_manage_members')) {
            Schema::table('group_user', function (Blueprint $table) {
                $table->dropColumn('can_manage_members');
            });
        }
    }
};
