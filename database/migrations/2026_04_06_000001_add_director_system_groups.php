<?php

use App\Enums\GroupTypeEnum;
use App\Models\Group;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $systemGroups = [
            ['system_name' => 'team_leads', 'name' => 'Team Leads', 'slug' => 'team-leads', 'description' => 'Team leads.'],
            ['system_name' => 'directors', 'name' => 'Directors', 'slug' => 'directors', 'description' => 'Department directors.'],
            ['system_name' => 'division_directors', 'name' => 'Division Directors', 'slug' => 'division-directors', 'description' => 'Division directors.'],
        ];

        foreach ($systemGroups as $g) {
            Group::firstOrCreate(
                ['system_name' => $g['system_name']],
                array_merge($g, ['type' => GroupTypeEnum::Automated]),
            );
        }
    }

    public function down(): void
    {
        Group::whereIn('system_name', ['team_leads', 'directors', 'division_directors'])->delete();
    }
};
