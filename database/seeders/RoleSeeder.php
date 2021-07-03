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
        Role::create(['name' => 'banned']);
        Role::create(['name' => 'attendee']);
        Role::create(['name' => 'verified']);
        Role::create(['name' => 'staff']);
        Role::create(['name' => 'director']);
        Role::create(['name' => 'board_of_directors']);
        Role::create(['name' => 'admin']);
    }
}
