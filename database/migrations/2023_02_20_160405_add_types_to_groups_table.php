<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            // none => Default
            // department => Lists it in the department overview (Same as none, just allows users to give more information)
            // automatic => Usergroups will be set automatically, gui for user management disabled.
            //              (Automatic Groups will not be show group members to "members" and inviting will be disabled)
            $table->string('type')->default('none')->change();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->enum('type', ['none','department'])->default('none')->change();

        });
    }
};
