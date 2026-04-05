<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->string('location')->nullable()->after('theme');
            $table->string('website_url')->nullable()->after('location');
            $table->string('conbook_url')->nullable()->after('website_url');
            $table->unsignedInteger('attendees_count')->nullable()->after('conbook_url');
            $table->string('background_image_path')->nullable()->after('attendees_count');
            $table->json('dailies')->nullable()->after('background_image_path');
            $table->json('videos')->nullable()->after('dailies');
            $table->json('photos')->nullable()->after('videos');
        });
    }

    public function down(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->dropColumn([
                'location',
                'website_url',
                'conbook_url',
                'attendees_count',
                'background_image_path',
                'dailies',
                'videos',
                'photos',
            ]);
        });
    }
};
