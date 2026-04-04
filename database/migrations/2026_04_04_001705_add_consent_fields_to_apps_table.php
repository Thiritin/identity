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
        Schema::table('apps', function (Blueprint $table) {
            $table->boolean('skip_consent')->default(false)->after('data');
            $table->string('developer_name')->nullable()->after('skip_consent');
            $table->string('privacy_policy_url')->nullable()->after('developer_name');
            $table->string('terms_of_service_url')->nullable()->after('privacy_policy_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn(['skip_consent', 'developer_name', 'privacy_policy_url', 'terms_of_service_url']);
        });
    }
};
