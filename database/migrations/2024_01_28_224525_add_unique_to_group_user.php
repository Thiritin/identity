<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->unique(['group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropUnique(['group_id', 'user_id']);
        });
    }
};
