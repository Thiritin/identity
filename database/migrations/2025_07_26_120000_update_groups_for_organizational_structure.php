<?php

use App\Domains\Staff\Enums\GroupTypeEnum;
use App\Domains\Staff\Models\Group;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Update BOD group type if it exists
        $bod = Group::where('name', 'Board of Directors')->first();
        if ($bod) {
            $bod->update(['type' => GroupTypeEnum::BOD->value]);
        }

        // Create divisions under BOD
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
                    'type' => GroupTypeEnum::Division->value,
                    'description' => $divisionData['description'],
                    'parent_id' => $bod?->id,
                ]
            );
        }
    }

    public function down(): void
    {
        // Remove created divisions
        Group::whereIn('name', [
            'Finance & Legal',
            'Design & Operations', 
            'Staff & Organization',
            'Marketing & Public'
        ])->delete();
    }
};
