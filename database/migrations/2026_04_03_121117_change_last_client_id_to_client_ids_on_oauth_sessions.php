<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('oauth_sessions', function (Blueprint $table) {
            $table->json('client_ids')->nullable()->after('user_agent');
        });

        DB::table('oauth_sessions')
            ->whereNotNull('last_client_id')
            ->eachById(function ($row) {
                DB::table('oauth_sessions')
                    ->where('id', $row->id)
                    ->update(['client_ids' => json_encode([$row->last_client_id])]);
            });

        Schema::table('oauth_sessions', function (Blueprint $table) {
            $table->dropColumn('last_client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_sessions', function (Blueprint $table) {
            $table->string('last_client_id')->nullable()->after('user_agent');
        });

        DB::table('oauth_sessions')
            ->whereNotNull('client_ids')
            ->eachById(function ($row) {
                $ids = json_decode($row->client_ids, true);
                DB::table('oauth_sessions')
                    ->where('id', $row->id)
                    ->update(['last_client_id' => $ids[0] ?? null]);
            });

        Schema::table('oauth_sessions', function (Blueprint $table) {
            $table->dropColumn('client_ids');
        });
    }
};
