<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->string('webhook_url', 2000)->nullable()->after('allow_notifications');
            $table->text('webhook_secret')->nullable()->after('webhook_url');
            $table->json('webhook_subscribed_fields')->nullable()->after('webhook_secret');
            $table->string('webhook_event_name', 64)->nullable()->after('webhook_subscribed_fields');
        });
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn(['webhook_url', 'webhook_secret', 'webhook_subscribed_fields', 'webhook_event_name']);
        });
    }
};
