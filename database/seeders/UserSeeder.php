<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
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
            "id" => 1
        ], [
            "name" => "Admin",
            "email" => "identity@eurofurence.localhost",
            "email_verified_at" => now(),
            "password" => \Hash::make(random_bytes(32))
        ]);
        // Warning this user will be made admin!
        $user->roles()->attach(Role::where('name','superadmin')->firstOrFail());
    }
}
