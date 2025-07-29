<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Basic personal information
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('nickname')->nullable()->after('last_name');
            
            // Contact information
            $table->json('phone_numbers')->nullable()->after('email_verified_at'); // Array of phone numbers
            $table->string('telegram_username')->nullable()->after('phone_numbers');
            $table->string('telegram_user_id')->nullable()->after('telegram_username');
            
            // Address information
            $table->text('address_line_1')->nullable()->after('telegram_user_id');
            $table->text('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('state_province')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state_province');
            $table->string('country')->nullable()->after('postal_code');
            
            // Personal details
            $table->date('date_of_birth')->nullable()->after('country');
            $table->json('languages')->nullable()->after('date_of_birth'); // Array of languages
            
            // EF-specific information
            $table->string('credit_as')->nullable()->after('languages'); // How they want to be credited
            $table->year('joined_ef_year')->nullable()->after('credit_as'); // Year they joined EF
            $table->string('first_ef_year')->nullable()->after('joined_ef_year'); // First EF they staffed
            
            // Privacy settings
            $table->json('profile_visibility')->nullable()->after('first_ef_year'); // JSON object controlling what's visible
            
            // Timestamps for profile completion tracking
            $table->timestamp('profile_completed_at')->nullable()->after('profile_visibility');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'nickname',
                'phone_numbers',
                'telegram_username',
                'telegram_user_id',
                'address_line_1',
                'address_line_2',
                'city',
                'state_province',
                'postal_code',
                'country',
                'date_of_birth',
                'languages',
                'credit_as',
                'joined_ef_year',
                'first_ef_year',
                'profile_visibility',
                'profile_completed_at',
            ]);
        });
    }
};