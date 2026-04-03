<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('staff users see departments in shared props', function () {
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);
    $department = Group::factory()->department()->create(['name' => 'Art']);

    $user = User::factory()->create();
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $department->users()->attach($user, ['level' => GroupUserLevel::Director, 'title' => 'Art Director']);

    $this->actingAs($user)
        ->get(route('settings.profile'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('user.departments.0.name', 'Art')
            ->where('user.departments.0.title', 'Art Director')
            ->where('user.departments.0.level', 'director')
            ->has('user.departments.0.hashid')
        );
});

test('non-staff users have empty departments', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.profile'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('user.departments', 0)
        );
});
