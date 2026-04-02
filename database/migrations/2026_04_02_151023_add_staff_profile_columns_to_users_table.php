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
        Schema::table('users', function (Blueprint $table) {
            $table->string('firstname', 100)->nullable()->after('name');
            $table->string('lastname', 100)->nullable()->after('firstname');
            $table->date('birthdate')->nullable()->after('lastname');
            $table->unsignedBigInteger('telegram_id')->nullable()->after('birthdate');
            $table->string('telegram_username', 100)->nullable()->after('telegram_id');
            $table->string('phone', 50)->nullable()->after('telegram_username');
            $table->json('spoken_languages')->nullable()->after('phone');
            $table->string('credit_as', 100)->nullable()->after('spoken_languages');
            $table->unsignedSmallInteger('first_eurofurence')->nullable()->after('credit_as');
            $table->unsignedSmallInteger('first_year_staff')->nullable()->after('first_eurofurence');
            $table->json('staff_profile_visibility')->nullable()->after('first_year_staff');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'firstname', 'lastname', 'birthdate',
                'telegram_id', 'telegram_username', 'phone',
                'spoken_languages', 'credit_as',
                'first_eurofurence', 'first_year_staff',
                'staff_profile_visibility',
            ]);
        });
    }
};
