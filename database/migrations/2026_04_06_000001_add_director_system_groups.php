<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

        $this->backfill('team_leads', GroupTypeEnum::Team, GroupUserLevel::TeamLead);
        $this->backfill('directors', GroupTypeEnum::Department, GroupUserLevel::Director);
        $this->backfill('division_directors', GroupTypeEnum::Division, GroupUserLevel::DivisionDirector);
    }

    private function backfill(string $systemName, GroupTypeEnum $sourceType, GroupUserLevel $level): void
    {
        $target = Group::where('system_name', $systemName)->first();
        if (! $target) {
            return;
        }

        $userIds = DB::table('group_user')
            ->join('groups', 'groups.id', '=', 'group_user.group_id')
            ->where('groups.type', $sourceType->value)
            ->where('group_user.level', $level->value)
            ->pluck('group_user.user_id')
            ->unique()
            ->all();

        foreach ($userIds as $userId) {
            $exists = DB::table('group_user')
                ->where('group_id', $target->id)
                ->where('user_id', $userId)
                ->exists();
            if (! $exists) {
                DB::table('group_user')->insert([
                    'group_id' => $target->id,
                    'user_id' => $userId,
                    'level' => GroupUserLevel::Member->value,
                ]);
            }
        }
    }

    public function down(): void
    {
        Group::whereIn('system_name', ['team_leads', 'directors', 'division_directors'])->delete();
    }
};
