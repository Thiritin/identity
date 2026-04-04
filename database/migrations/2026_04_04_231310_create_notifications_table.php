<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('apps')->restrictOnDelete();
            $table->foreignId('notification_type_id')->constrained('notification_types')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('cta_label')->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'read_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
