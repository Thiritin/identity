<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'location', 'start_date', 'end_date']);
        });

        Schema::table('convention_attendee', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('convention_attendee', function (Blueprint $table) {
            $table->boolean('is_staff')->default(false)->after('convention_id');
        });
    }

    public function down(): void
    {
        Schema::table('convention_attendee', function (Blueprint $table) {
            $table->dropColumn('is_staff');
        });

        Schema::table('convention_attendee', function (Blueprint $table) {
            $table->string('status')->default('attendee');
        });

        Schema::table('conventions', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name');
            $table->string('location')->nullable()->after('year');
            $table->date('start_date')->nullable()->after('location');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }
};
