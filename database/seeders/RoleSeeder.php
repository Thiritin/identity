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
        Role::findOrCreate('banned');
        Role::findOrCreate('attendee');
        Role::findOrCreate('verified');
        Role::findOrCreate('staff');
        Role::findOrCreate('director');
        Role::findOrCreate('board_of_directors');
    }
}
