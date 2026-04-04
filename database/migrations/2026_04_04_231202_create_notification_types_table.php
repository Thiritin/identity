<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->string('key', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['transactional', 'operational', 'informational', 'promotional']);
            $table->json('default_channels');
            $table->boolean('disabled')->default(false);
            $table->timestamps();

            $table->unique(['app_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_types');
    }
};
