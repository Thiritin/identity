<?php

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsStaffProfileConsent;

uses(RefreshDatabase::class, GrantsStaffProfileConsent::class);

function makeStaffUserForConsentRead(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $staffGroup = Group::firstWhere('system_name', 'staff')
        ?? Group::factory()->create(['system_name' => 'staff']);
    $user->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    return $user->fresh();
}

test('un-consented staff user sees null for all gated columns in profile payload', function () {
    $user = makeStaffUserForConsentRead([
        'firstname' => 'Alice',
        'lastname'  => 'Example',
        'phone'     => '+49 123',
        'address_line1' => '1 Test St',
        'city' => 'Berlin',
        'country' => 'DE',
        'emergency_contact_name' => 'Bob',
        'spoken_languages' => ['en', 'de'],
        'credit_as' => 'Alice',
    ]);

    $response = $this->actingAs($user)->get(route('settings.profile'));
    $response->assertOk();
    $props = $response->viewData('page')['props'];

    expect($props['staffProfile']['firstname'])->toBeNull();
    expect($props['staffProfile']['lastname'])->toBeNull();
    expect($props['staffProfile']['pronouns'])->toBeNull();
    expect($props['staffProfile']['birthdate'])->toBeNull();
    expect($props['staffProfile']['phone'])->toBeNull();
    expect($props['staffProfile']['address_line1'])->toBeNull();
    expect($props['staffProfile']['address_line2'])->toBeNull();
    expect($props['staffProfile']['city'])->toBeNull();
    expect($props['staffProfile']['postal_code'])->toBeNull();
    expect($props['staffProfile']['country'])->toBeNull();
    expect($props['staffProfile']['emergency_contact_name'])->toBeNull();
    expect($props['staffProfile']['emergency_contact_phone'])->toBeNull();
    expect($props['staffProfile']['emergency_contact_telegram'])->toBeNull();
    expect($props['staffProfile']['spoken_languages'])->toBe([]);
    expect($props['staffProfile']['credit_as'])->toBeNull();
    expect($props['staffProfile']['visibility'])->toBe([]);
    expect($props['staffProfile']['consent']['granted'])->toBeFalse();
});

test('un-consented staff user sees empty group memberships and convention attendance', function () {
    $user = makeStaffUserForConsentRead();
    $response = $this->actingAs($user)->get(route('settings.profile'));
    $response->assertOk();
    $props = $response->viewData('page')['props'];

    expect($props['groupMemberships'])->toBeEmpty();
    expect($props['conventionAttendance'])->toBeEmpty();
});

test('consented staff user sees all gated columns in profile payload', function () {
    $user = $this->grantStaffProfileConsent(makeStaffUserForConsentRead([
        'firstname' => 'Alice',
        'address_line1' => '1 Test St',
        'credit_as' => 'Alice',
    ]));

    $response = $this->actingAs($user)->get(route('settings.profile'));
    $response->assertOk();
    $props = $response->viewData('page')['props'];

    expect($props['staffProfile']['firstname'])->toBe('Alice');
    expect($props['staffProfile']['address_line1'])->toBe('1 Test St');
    expect($props['staffProfile']['credit_as'])->toBe('Alice');
    expect($props['staffProfile']['consent']['granted'])->toBeTrue();
    expect($props['staffProfile']['consent']['version'])->toBe(1);
    expect($props['staffProfile']['consent']['is_current'])->toBeTrue();
    expect($props['staffProfile']['consent']['granted_at'])->not->toBeNull();
});
