<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Change the enum to include the new values we need
        DB::statement("ALTER TABLE group_user MODIFY COLUMN level ENUM('invited', 'banned', 'member', 'moderator', 'admin', 'owner', 'team_lead', 'director', 'division_director') DEFAULT 'member'");
    }

    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE group_user MODIFY COLUMN level ENUM('invited', 'banned', 'member', 'moderator', 'admin', 'owner') DEFAULT 'invited'");
    }
};
