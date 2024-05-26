<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedInteger('nextcloud_folder_id')->after('nextcloud_folder_name')->nullable();
            // nextcloud group id
            $table->string('nextcloud_group_id')->after('nextcloud_folder_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('nextcloud_folder_id');
            $table->dropColumn('nextcloud_group_id');
        });
    }
};
