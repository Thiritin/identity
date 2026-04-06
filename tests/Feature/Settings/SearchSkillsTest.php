<?php

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\Skill;
use App\Models\TwoFactor;
use App\Models\User;
use App\Support\StaffProfile\ConsentNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createStaffUserForSkillSearch(): User
{
    $user = User::factory()->create([
        'staff_profile_consent_at' => now(),
        'staff_profile_consent_version' => ConsentNotice::CURRENT_VERSION,
    ]);
    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $user->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    return $user;
}

it('returns skills matching a query', function () {
    $user = createStaffUserForSkillSearch();

    Skill::create(['name' => 'PHP']);
    Skill::create(['name' => 'JavaScript']);
    Skill::create(['name' => 'TypeScript']);

    $response = $this->actingAs($user)
        ->getJson(route('settings.staff-profile.skills.search', ['q' => 'Script']));

    $response->assertOk();
    $data = $response->json();
    expect($data)->toBeArray();
    expect(collect($data)->pluck('name')->all())->toContain('JavaScript');
    expect(collect($data)->pluck('name')->all())->toContain('TypeScript');
    expect(collect($data)->pluck('name')->all())->not->toContain('PHP');
});

it('returns empty array when no skills match the query', function () {
    $user = createStaffUserForSkillSearch();

    Skill::create(['name' => 'PHP']);

    $response = $this->actingAs($user)
        ->getJson(route('settings.staff-profile.skills.search', ['q' => 'zzznomatch']));

    $response->assertOk();
    expect($response->json())->toBe([]);
});

it('returns all skills when no query is provided', function () {
    $user = createStaffUserForSkillSearch();

    Skill::create(['name' => 'PHP']);
    Skill::create(['name' => 'JavaScript']);

    $response = $this->actingAs($user)
        ->getJson(route('settings.staff-profile.skills.search'));

    $response->assertOk();
    $data = $response->json();
    expect($data)->toBeArray();
    expect(count($data))->toBe(2);
});

it('requires authentication', function () {
    $response = $this->getJson(route('settings.staff-profile.skills.search'));

    $response->assertUnauthorized();
});
