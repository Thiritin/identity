<?php

namespace Database\Seeders;

use App\Domains\Staff\Enums\GroupTypeEnum;
use App\Domains\Staff\Models\Group;
use Illuminate\Database\Seeder;

class OrganizationalStructureSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        // Create BOD (Board of Directors) - should already exist, but ensure it's properly typed
        $bod = Group::firstOrCreate(
            ['name' => 'Board of Directors'],
            [
                'type' => GroupTypeEnum::BOD,
                'description' => 'The Board of Directors - highest level of governance',
            ]
        );

        $bod->update(['type' => GroupTypeEnum::BOD]);

        // Create Divisions
        $divisions = [
            [
                'name' => 'Finance & Legal',
                'description' => 'Finance and Legal division overseeing financial and legal matters',
            ],
            [
                'name' => 'Design & Operations',
                'description' => 'Design and Operations division handling creative and operational aspects',
            ],
            [
                'name' => 'Staff & Organization',
                'description' => 'Staff and Organization division managing human resources and organizational structure',
            ],
            [
                'name' => 'Marketing & Public',
                'description' => 'Marketing and Public division handling publicity and marketing efforts',
            ],
        ];

        foreach ($divisions as $divisionData) {
            Group::firstOrCreate(
                ['name' => $divisionData['name']],
                [
                    'type' => GroupTypeEnum::Division,
                    'description' => $divisionData['description'],
                    'parent_id' => $bod->id,
                ]
            );
        }

        $this->command->info('Organizational structure seeded successfully!');
    }
}
