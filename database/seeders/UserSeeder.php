<?php

namespace Database\Seeders;

use App\Domains\Staff\Enums\GroupTypeEnum;
use App\Domains\Staff\Enums\GroupUserLevel;
use App\Domains\Staff\Models\Group;
use App\Domains\User\Models\User;
use Illuminate\Database\Seeder;

/**
 * This seeder is responsible for always having ready a single user.
 * It will create a new admin user if there are no users in the system.
 */
class UserSeeder extends Seeder
{
    public function run()
    {
        /**
         * Only run if user count is 0
         */
        if (User::count() !== 0) {
            return false;
        }

        $user = User::firstOrCreate([
            'id' => 1,
        ], [
            'name' => 'Admin',
            'email' => 'identity@eurofurence.lan',
            'email_verified_at' => now(),
            'password' => \Hash::make(env('ADMIN_PASSWORD', random_bytes(32))),
        ]);

        // Create or find System Admin group for IDP admin access
        $adminGroup = Group::firstOrCreate([
            'name' => 'System Administrators'
        ], [
            'type' => GroupTypeEnum::Automated,
            'system_name' => 'system_admins',
            'description' => 'System Administrators - Full IDP admin access'
        ]);

        // Attach user as Member of admin group (membership grants admin privileges)
        $adminGroup->users()->syncWithoutDetaching([
            $user->id => [
                'level' => GroupUserLevel::Member->value,
                'can_manage_users' => true,
                'title' => 'System Administrator'
            ]
        ]);

        // Also create BOD group for organizational structure
        $bodGroup = Group::firstOrCreate([
            'name' => 'Board of Directors'
        ], [
            'type' => GroupTypeEnum::BOD,
            'description' => 'Board of Directors - Top level organizational group'
        ]);

        // Attach user as Director of BOD (organizational role, not system admin)
        $bodGroup->users()->syncWithoutDetaching([
            $user->id => [
                'level' => GroupUserLevel::Director->value,
                'can_manage_users' => true,
                'title' => 'Chairman'   
            ]
        ]);
    }
}
