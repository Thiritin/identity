<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupDevModeStaff(): array
{
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);
    $user = User::factory()->create();
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    return [$user, $staffGroup];
}

test('directory index includes systemMemberships with viewer non-DDT groups', function () {
    [$user, $staffGroup] = setupDevModeStaff();

    // Also attach the user to a Department (should NOT appear in systemMemberships).
    $dept = Group::factory()->create([
        'type' => GroupTypeEnum::Department,
        'name' => 'Art',
    ]);
    $dept->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $this->actingAs($user)
        ->get(route('directory.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('systemMemberships', 1)
            ->where('systemMemberships.0.name', 'Staff')
            ->where('systemMemberships.0.type', 'automated')
            ->where('systemMemberships.0.hashid', $staffGroup->hashid)
            ->where('systemMemberships.0.slug', $staffGroup->slug)
        );
});

test('systemMemberships only includes non-DDT groups the viewer belongs to', function () {
    [$user, $staffGroup] = setupDevModeStaff();

    // User belongs only to the Automated 'staff' group (non-DDT), nothing else.
    $this->actingAs($user)
        ->get(route('directory.index'))
        ->assertInertia(fn ($page) => $page
            ->has('systemMemberships', 1)
            ->where('systemMemberships.0.name', 'Staff')
        );
});
