<?php

use App\Enums\GroupUserLevel;
use App\Models\Convention;
use App\Models\Group;
use App\Models\OauthSession;
use App\Models\TwoFactor;
use App\Models\User;
use App\Services\Hydra\Client as HydraClient;
use App\Services\RegistrationService;
use App\Support\StaffProfile\ConsentNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsStaffProfileConsent;

uses(RefreshDatabase::class, GrantsStaffProfileConsent::class);

it('anonymizes account when no active registration', function () {
    $this->mock(RegistrationService::class, function ($mock) {
        $mock->shouldReceive('hasActiveRegistration')->andReturn(false);
    });

    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('revokeAllConsentSessions')->once();
        $mock->shouldReceive('invalidateAllSessions')->once();
    });

    $user = User::factory()->create([
        'name' => 'OriginalName',
        'email' => 'original@example.com',
        'firstname' => 'Jane',
        'lastname' => 'Doe',
        'pronouns' => 'she/her',
        'phone' => '+49123',
        'telegram_username' => 'jane',
    ]);
    OauthSession::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('my-data.delete-account'))
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertDatabaseMissing('oauth_sessions', ['user_id' => $user->id]);

    $user->refresh();
    expect($user->name)->toBe('deleted-user-' . $user->id);
    expect($user->email)->toBe('deleted-' . $user->id . '@deleted.invalid');
    expect($user->firstname)->toBeNull();
    expect($user->lastname)->toBeNull();
    expect($user->pronouns)->toBeNull();
    expect($user->phone)->toBeNull();
    expect($user->telegram_username)->toBeNull();
    expect($user->anonymized_at)->not->toBeNull();
    expect($user->suspended_at)->not->toBeNull();
    expect($user->isAnonymized())->toBeTrue();
    expect($user->isSuspended())->toBeTrue();
});

it('blocks deletion when active registration exists', function () {
    $this->mock(RegistrationService::class, function ($mock) {
        $mock->shouldReceive('hasActiveRegistration')->andReturn(true);
    });

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('my-data.delete-account'))
        ->assertRedirect()
        ->assertSessionHasErrors('delete');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

it('blocks deletion when registration service fails', function () {
    $this->mock(RegistrationService::class, function ($mock) {
        $mock->shouldReceive('hasActiveRegistration')
            ->andThrow(new RuntimeException('Service unavailable'));
    });

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('my-data.delete-account'))
        ->assertRedirect()
        ->assertSessionHasErrors('delete');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

it('anonymises all gated columns, consent state, group credit_as, and convention attendance', function () {
    $this->mock(RegistrationService::class, function ($mock) {
        $mock->shouldReceive('hasActiveRegistration')->andReturn(false);
    });

    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('revokeAllConsentSessions')->once();
        $mock->shouldReceive('invalidateAllSessions')->once();
    });

    $user = User::factory()->create([
        'firstname' => 'Alice',
        'lastname' => 'Example',
        'pronouns' => 'she/her',
        'birthdate' => '1990-01-01',
        'phone' => '+49 1',
        'address_line1' => '1 A',
        'address_line2' => '2 B',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country' => 'DE',
        'emergency_contact_name' => 'Bob',
        'emergency_contact_phone' => '+49 2',
        'emergency_contact_telegram' => '@bob',
        'spoken_languages' => ['en', 'de'],
        'credit_as' => 'Alice',
    ]);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    $staffGroup = Group::firstWhere('system_name', 'staff')
        ?? Group::factory()->create(['system_name' => 'staff']);
    $user->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);

    $group = Group::factory()->create();
    $user->groups()->attach($group, [
        'level' => GroupUserLevel::Member,
        'credit_as' => 'Alice Credit',
    ]);

    $convention = Convention::factory()->create();
    $user->conventions()->attach($convention->id, [
        'is_attended' => true,
        'is_staff' => true,
    ]);

    $this->grantStaffProfileConsent($user);

    OauthSession::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('my-data.delete-account'))
        ->assertRedirect('/');

    $user->refresh();

    // All 15 GATED_USER_COLUMNS must be null
    foreach (ConsentNotice::GATED_USER_COLUMNS as $col) {
        expect($user->$col)->toBeNull("column {$col} should be null after anonymisation");
    }

    // Consent state must be null
    expect($user->staff_profile_consent_at)->toBeNull();
    expect($user->staff_profile_consent_version)->toBeNull();

    // group_user.credit_as must be gone (groups detached entirely)
    $pivotCount = \DB::table('group_user')->where('user_id', $user->id)->count();
    expect($pivotCount)->toBe(0);

    // convention_attendee rows must be removed
    $conventionCount = \DB::table('convention_attendee')->where('user_id', $user->id)->count();
    expect($conventionCount)->toBe(0);
});

it('requires authentication', function () {
    $this->delete(route('my-data.delete-account'))
        ->assertRedirect();
});
