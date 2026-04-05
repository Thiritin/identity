<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('staff_profile_consent_at')->nullable()->after('staff_profile_visibility');
            $table->unsignedSmallInteger('staff_profile_consent_version')->nullable()->after('staff_profile_consent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['staff_profile_consent_at', 'staff_profile_consent_version']);
        });
    }
};
