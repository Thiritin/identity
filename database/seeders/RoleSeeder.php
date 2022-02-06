<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Actual User Access Levels
        Role::findOrCreate('admin');
        Role::findOrCreate('superadmin');
        // Staff
        Role::findOrCreate('staffbooking');
    }
}
