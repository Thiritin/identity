<?php

namespace Tests\Feature\Staff;

use App\Enums\GroupTypeEnum;
use App\Domains\Staff\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\patchJson;

uses(RefreshDatabase::class);

test('Update member level to team lead', function (GroupUserLevel $groupUserLevel) {
    $group = Group::factory()->create();
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
        'description' => 'Staff members.',
        'slug' => 'staff',
    ]);
    $user = User::factory()->create();
    $userToBeUpdated = User::factory()->create();

    $staffGroup->users()->sync([
        $user->id => ['level' => GroupUserLevel::Member],
    ]);
    $group->users()->sync([
        $user->id => ['level' => $groupUserLevel],
        $userToBeUpdated->id => ['level' => GroupUserLevel::Member],
    ]);

    $this->actingAs($user, 'staff');

    $response = patchJson(
        route('staff.groups.members.update', ['group' => $group, 'member' => $userToBeUpdated]),
        ['level' => GroupUserLevel::TeamLead->value],
    );

    $response->assertRedirect(route('staff.groups.members.index', ['group' => $group]));

    expect($group->users()->find($userToBeUpdated)->pivot->level)
        ->toBe(GroupUserLevel::TeamLead);
})->with([
    'as director of group' => GroupUserLevel::Director,
    'as division director of group' => GroupUserLevel::DivisionDirector,
]);
