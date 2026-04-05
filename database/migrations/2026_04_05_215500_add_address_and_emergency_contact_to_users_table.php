<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address_line1', 255)->nullable()->after('phone');
            $table->string('address_line2', 255)->nullable()->after('address_line1');
            $table->string('city', 100)->nullable()->after('address_line2');
            $table->string('postal_code', 20)->nullable()->after('city');
            $table->string('country', 2)->nullable()->after('postal_code');
            $table->string('emergency_contact_name', 100)->nullable()->after('country');
            $table->string('emergency_contact_phone', 50)->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_telegram', 100)->nullable()->after('emergency_contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'address_line1',
                'address_line2',
                'city',
                'postal_code',
                'country',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_telegram',
            ]);
        });
    }
};
