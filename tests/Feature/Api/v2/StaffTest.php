<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use App\Services\Auth\ApiGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\ValidatesOpenApiV2;

uses(RefreshDatabase::class, ValidatesOpenApiV2::class);

function actingAsStaffApiUser(User $user, string $clientId, array $scopes = []): void
{
    $guard = Mockery::mock(ApiGuard::class);
    $guard->shouldReceive('user')->andReturn($user);
    $guard->shouldReceive('check')->andReturn(true);
    $guard->shouldReceive('guest')->andReturn(false);
    $guard->shouldReceive('id')->andReturn($user->id);
    $guard->shouldReceive('hasUser')->andReturn(true);
    $guard->shouldReceive('getClientId')->andReturn($clientId);
    $guard->shouldReceive('getScopes')->andReturn($scopes);
    $guard->shouldReceive('setRequest')->andReturnSelf();

    Auth::extend('hydra', fn () => $guard);
    Auth::forgetGuards();
}

beforeEach(function () {
    $this->staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);
});

it('GET /staff/me returns self with pronouns, address, emergency contact', function () {
    $user = User::factory()->create([
        'firstname' => 'Self',
        'lastname' => 'Viewer',
        'pronouns' => 'they/them',
        'address_line1' => 'Line 1',
        'address_line2' => null,
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country' => 'DE',
        'emergency_contact_name' => 'Kin',
        'emergency_contact_phone' => '+4900',
        'emergency_contact_telegram' => 'kin_tg',
    ]);
    $this->staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $user = $user->fresh();
    actingAsStaffApiUser($user, 'app-one', ['staff.my.read']);

    $response = $this->getJson('/api/v2/staff/me');

    $response->assertOk();
    $body = $response->json();

    expect($body['firstname'])->toBe('Self');
    expect($body['lastname'])->toBe('Viewer');
    expect($body['pronouns'])->toBe('they/them');
    expect($body['address_line1'])->toBe('Line 1');
    expect($body['address_line2'])->toBeNull();
    expect($body['city'])->toBe('Berlin');
    expect($body['postal_code'])->toBe('10115');
    expect($body['country'])->toBe('DE');
    expect($body['emergency_contact_name'])->toBe('Kin');
    expect($body['emergency_contact_phone'])->toBe('+4900');
    expect($body['emergency_contact_telegram'])->toBe('kin_tg');

    $this->assertMatchesOpenApiV2($response, '/staff/me');
});

it('GET /staff/{id} hides address from same-department staff by default and shows emergency contact', function () {
    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $dept = Group::factory()->department()->create(['name' => 'IT', 'parent_id' => $root->id]);

    $subject = User::factory()->create([
        'firstname' => 'Subject',
        'lastname' => 'Member',
        'address_line1' => 'Private St',
        'city' => 'Hamburg',
        'country' => 'DE',
        'emergency_contact_name' => 'Emergency Person',
        'emergency_contact_phone' => '+49111',
    ]);
    $this->staffGroup->users()->attach($subject, ['level' => GroupUserLevel::Member]);
    $dept->users()->attach($subject, ['level' => GroupUserLevel::Member]);

    $viewer = User::factory()->create();
    $this->staffGroup->users()->attach($viewer, ['level' => GroupUserLevel::Member]);
    $dept->users()->attach($viewer, ['level' => GroupUserLevel::Member]);
    $viewer = $viewer->fresh();
    $subject = $subject->fresh();

    actingAsStaffApiUser($viewer, 'app-one', ['staff.all.read']);

    $response = $this->getJson('/api/v2/staff/' . $subject->hashid);

    $response->assertOk();
    $body = $response->json();

    expect($body['emergency_contact_name'])->toBe('Emergency Person');
    expect($body['emergency_contact_phone'])->toBe('+49111');

    expect($body)->not->toHaveKey('address_line1');
    expect($body)->not->toHaveKey('address_line2');
    expect($body)->not->toHaveKey('city');
    expect($body)->not->toHaveKey('postal_code');
    expect($body)->not->toHaveKey('country');

    $this->assertMatchesOpenApiV2($response, '/staff/{user}');
});

it('GET /staff/{id} exposes address to directors', function () {
    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $dept = Group::factory()->department()->create(['name' => 'IT', 'parent_id' => $root->id]);

    $subject = User::factory()->create([
        'firstname' => 'Subject',
        'lastname' => 'Member',
        'address_line1' => 'Private St',
        'city' => 'Hamburg',
        'country' => 'DE',
    ]);
    $this->staffGroup->users()->attach($subject, ['level' => GroupUserLevel::Member]);
    $dept->users()->attach($subject, ['level' => GroupUserLevel::Member]);

    $director = User::factory()->create();
    $this->staffGroup->users()->attach($director, ['level' => GroupUserLevel::Member]);
    $dept->users()->attach($director, ['level' => GroupUserLevel::Director]);
    $director = $director->fresh();
    $subject = $subject->fresh();

    actingAsStaffApiUser($director, 'app-one', ['staff.all.read']);

    $response = $this->getJson('/api/v2/staff/' . $subject->hashid);

    $response->assertOk();
    $body = $response->json();
    expect($body['address_line1'])->toBe('Private St');
    expect($body['city'])->toBe('Hamburg');
    expect($body['country'])->toBe('DE');

    $this->assertMatchesOpenApiV2($response, '/staff/{user}');
});
