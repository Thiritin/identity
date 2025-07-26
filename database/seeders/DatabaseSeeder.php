<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class, // Relies on Role Seeder
            AppSeeder::class, // Relies on User Seeder
            GroupSeeder::class,
            OrganizationalStructureSeeder::class,
        ]);
    }
}
