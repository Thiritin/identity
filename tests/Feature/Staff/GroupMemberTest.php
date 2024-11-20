<?php

namespace Tests\Feature\Staff;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\patchJson;

uses(RefreshDatabase::class);

test('Update member level to admin', function (GroupUserLevel $groupUserLevel) {
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

    var_dump(GroupUserLevel::Admin->name);

    $response = patchJson(
        route('staff.groups.members.update', ['group' => $group, 'member' => $userToBeUpdated]),
        ['level' => GroupUserLevel::Admin->value],
    );

    $response->assertRedirect(route('staff.groups.members.index', ['group' => $group]));

    expect($group->users()->find($userToBeUpdated)->pivot->level)
        ->toBe(GroupUserLevel::Admin);
})->with([
    'as owner of group' => GroupUserLevel::Owner,
    'as admin of group' => GroupUserLevel::Admin,
]);
