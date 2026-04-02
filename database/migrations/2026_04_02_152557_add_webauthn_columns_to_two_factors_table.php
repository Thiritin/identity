<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('two_factors', function (Blueprint $table) {
            $table->string('credential_id')->nullable()->after('identifier');
            $table->text('public_key')->nullable()->after('credential_id');
            $table->unsignedInteger('sign_count')->default(0)->after('public_key');
            $table->json('transports')->nullable()->after('sign_count');
            $table->string('aaguid', 36)->nullable()->after('transports');

            $table->index(['user_id', 'credential_id']);
        });
    }

    public function down(): void
    {
        Schema::table('two_factors', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'credential_id']);
            $table->dropColumn(['credential_id', 'public_key', 'sign_count', 'transports', 'aaguid']);
        });
    }
};
