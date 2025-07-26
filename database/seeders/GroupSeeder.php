<?php

namespace Database\Seeders;

use App\Domains\Staff\Enums\GroupTypeEnum;
use App\Domains\Staff\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run()
    {
        Group::firstOrCreate([
            'system_name' => 'staff',
        ], [
            'system_name' => 'staff',
            'type' => GroupTypeEnum::Automated,
            'name' => 'Staff',
            'description' => 'Staff members.',
            'slug' => 'staff',
        ]);
    }
}
