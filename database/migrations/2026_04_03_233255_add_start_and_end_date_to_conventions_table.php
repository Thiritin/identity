<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('year');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('theme')->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'theme']);
        });
    }
};
