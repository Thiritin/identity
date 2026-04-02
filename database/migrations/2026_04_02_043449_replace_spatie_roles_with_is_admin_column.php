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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
        });

        // Migrate existing superadmin role assignments to is_admin column
        if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            DB::statement("
                UPDATE users
                SET is_admin = true
                WHERE id IN (
                    SELECT model_id FROM model_has_roles
                    WHERE model_type = 'App\\\\Models\\\\User'
                    AND role_id IN (SELECT id FROM roles WHERE name = 'superadmin')
                )
            ");
        }

        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
