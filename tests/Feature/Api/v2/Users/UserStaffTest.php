<?php

use App\Models\Skill;
use App\Models\User;
use App\Services\Auth\ApiGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\ValidatesOpenApiV2;

uses(RefreshDatabase::class, ValidatesOpenApiV2::class);

function actingAsStaffProfileApiUser(User $user, string $clientId, array $scopes = []): void
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

// --- GET tests ---

it('returns profile fields with Staff.Profile.Read', function () {
    $user = User::factory()->create([
        'firstname' => 'John',
        'lastname' => 'Doe',
        'birthdate' => '1990-05-15',
        'pronouns' => 'he/him',
        'credit_as' => 'J. Doe',
    ]);
    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.Read']);

    $response = $this->getJson('/api/v2/users/me/staff')
        ->assertOk()
        ->assertJsonStructure(['name', 'firstname', 'lastname', 'birthdate', 'avatar', 'pronouns', 'credit_as', 'nda_checked_at'])
        ->assertJson([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'birthdate' => '1990-05-15',
            'pronouns' => 'he/him',
            'credit_as' => 'J. Doe',
        ]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff');
});

it('returns skills fields with Staff.Skills.Read', function () {
    $user = User::factory()->create([
        'spoken_languages' => ['English', 'German'],
    ]);
    $skill = Skill::create(['name' => 'PHP']);
    $user->skills()->attach($skill);

    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.Read', 'Staff.Skills.Read']);

    $response = $this->getJson('/api/v2/users/me/staff')
        ->assertOk()
        ->assertJsonStructure(['spoken_languages', 'skills'])
        ->assertJson([
            'spoken_languages' => ['English', 'German'],
            'skills' => ['PHP'],
        ]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff');
});

it('returns contact fields with Staff.Contact.Read', function () {
    $user = User::factory()->create([
        'phone' => '+491234567890',
        'telegram_username' => 'johndoe',
    ]);
    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.Read', 'Staff.Contact.Read']);

    $response = $this->getJson('/api/v2/users/me/staff')
        ->assertOk()
        ->assertJson([
            'phone' => '+491234567890',
            'telegram_username' => 'johndoe',
        ]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff');
});

it('returns address fields with Staff.Address.Read', function () {
    $user = User::factory()->create([
        'address_line1' => '123 Main St',
        'address_line2' => 'Apt 4',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country' => 'DE',
    ]);
    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.Read', 'Staff.Address.Read']);

    $response = $this->getJson('/api/v2/users/me/staff')
        ->assertOk()
        ->assertJson([
            'address_line1' => '123 Main St',
            'address_line2' => 'Apt 4',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country' => 'DE',
        ]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff');
});

it('returns emergency_contact with Staff.Emergency.Read', function () {
    $user = User::factory()->create([
        'emergency_contact_name' => 'Jane Doe',
        'emergency_contact_phone' => '+491111111111',
        'emergency_contact_telegram' => 'janedoe',
    ]);
    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.Read', 'Staff.Emergency.Read']);

    $response = $this->getJson('/api/v2/users/me/staff')
        ->assertOk()
        ->assertJson([
            'emergency_contact' => [
                'name' => 'Jane Doe',
                'phone' => '+491111111111',
                'telegram' => 'janedoe',
            ],
        ]);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff');
});

it('only returns fields for granted scopes', function () {
    $user = User::factory()->create([
        'firstname' => 'John',
        'lastname' => 'Doe',
        'phone' => '+491234567890',
        'address_line1' => '123 Main St',
    ]);
    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.Read']);

    $response = $this->getJson('/api/v2/users/me/staff')
        ->assertOk();

    $json = $response->json();
    expect($json)->toHaveKey('firstname');
    expect($json)->not->toHaveKey('phone');
    expect($json)->not->toHaveKey('telegram_username');
    expect($json)->not->toHaveKey('address_line1');
    expect($json)->not->toHaveKey('spoken_languages');
    expect($json)->not->toHaveKey('skills');
    expect($json)->not->toHaveKey('emergency_contact');

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff');
});

it('returns 403 without any Staff scope', function () {
    $user = User::factory()->create();
    actingAsStaffProfileApiUser($user, 'app-one', ['User.Read']);

    $response = $this->getJson('/api/v2/users/me/staff')
        ->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff');
});

it('validates GET response against OpenAPI spec', function () {
    $user = User::factory()->create([
        'firstname' => 'John',
        'lastname' => 'Doe',
        'spoken_languages' => ['English'],
        'phone' => '+491234567890',
        'telegram_username' => 'johndoe',
        'address_line1' => '123 Main St',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country' => 'DE',
        'emergency_contact_name' => 'Jane',
        'emergency_contact_phone' => '+490000000000',
        'emergency_contact_telegram' => 'jane',
    ]);
    actingAsStaffProfileApiUser($user, 'app-one', [
        'Staff.Profile.Read',
        'Staff.Skills.Read',
        'Staff.Contact.Read',
        'Staff.Address.Read',
        'Staff.Emergency.Read',
    ]);

    $response = $this->getJson('/api/v2/users/me/staff')
        ->assertOk();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff');
});

// --- PUT tests ---

it('updates profile fields with Staff.Profile.ReadWrite', function () {
    $user = User::factory()->create(['firstname' => 'Old']);
    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.ReadWrite']);

    $response = $this->putJson('/api/v2/users/me/staff', [
        'firstname' => 'New',
        'lastname' => 'Name',
        'pronouns' => 'they/them',
    ])->assertOk()
        ->assertJson([
            'firstname' => 'New',
            'lastname' => 'Name',
            'pronouns' => 'they/them',
        ]);

    expect($user->fresh()->firstname)->toBe('New');

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff', 'put');
});

it('updates skills with Staff.Skills.ReadWrite', function () {
    $user = User::factory()->create(['spoken_languages' => ['English']]);
    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.ReadWrite', 'Staff.Skills.ReadWrite']);

    $response = $this->putJson('/api/v2/users/me/staff', [
        'spoken_languages' => ['English', 'German'],
        'skills' => ['PHP', 'Laravel'],
    ])->assertOk()
        ->assertJson([
            'spoken_languages' => ['English', 'German'],
            'skills' => ['PHP', 'Laravel'],
        ]);

    expect($user->fresh()->spoken_languages)->toBe(['English', 'German']);
    expect($user->fresh()->skills->pluck('name')->all())->toBe(['PHP', 'Laravel']);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff', 'put');
});

it('updates contact with Staff.Contact.ReadWrite', function () {
    $user = User::factory()->create();
    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.ReadWrite', 'Staff.Contact.ReadWrite']);

    $response = $this->putJson('/api/v2/users/me/staff', [
        'phone' => '+491234567890',
        'telegram_username' => 'newhandle',
    ])->assertOk()
        ->assertJson([
            'phone' => '+491234567890',
            'telegram_username' => 'newhandle',
        ]);

    expect($user->fresh()->telegram_username)->toBe('newhandle');

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff', 'put');
});

it('updates address with Staff.Address.ReadWrite', function () {
    $user = User::factory()->create();
    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.ReadWrite', 'Staff.Address.ReadWrite']);

    $response = $this->putJson('/api/v2/users/me/staff', [
        'address_line1' => '456 Oak Ave',
        'city' => 'Munich',
        'postal_code' => '80331',
        'country' => 'DE',
    ])->assertOk()
        ->assertJson([
            'address_line1' => '456 Oak Ave',
            'city' => 'Munich',
            'postal_code' => '80331',
            'country' => 'DE',
        ]);

    expect($user->fresh()->city)->toBe('Munich');

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff', 'put');
});

it('updates emergency_contact with Staff.Emergency.ReadWrite', function () {
    $user = User::factory()->create();
    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.ReadWrite', 'Staff.Emergency.ReadWrite']);

    $response = $this->putJson('/api/v2/users/me/staff', [
        'emergency_contact' => [
            'name' => 'Jane Doe',
            'phone' => '+491111111111',
            'telegram' => 'janedoe',
        ],
    ])->assertOk()
        ->assertJson([
            'emergency_contact' => [
                'name' => 'Jane Doe',
                'phone' => '+491111111111',
                'telegram' => 'janedoe',
            ],
        ]);

    expect($user->fresh()->emergency_contact_name)->toBe('Jane Doe');

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff', 'put');
});

it('returns 403 when writing skills fields without Staff.Skills.ReadWrite scope', function () {
    $user = User::factory()->create();
    actingAsStaffProfileApiUser($user, 'app-one', ['Staff.Profile.ReadWrite']);

    $response = $this->putJson('/api/v2/users/me/staff', [
        'spoken_languages' => ['English'],
    ])->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff', 'put');
});

it('validates PUT response against OpenAPI spec', function () {
    $user = User::factory()->create();
    actingAsStaffProfileApiUser($user, 'app-one', [
        'Staff.Profile.ReadWrite',
        'Staff.Skills.ReadWrite',
        'Staff.Contact.ReadWrite',
        'Staff.Address.ReadWrite',
        'Staff.Emergency.ReadWrite',
    ]);

    $response = $this->putJson('/api/v2/users/me/staff', [
        'firstname' => 'Test',
        'lastname' => 'User',
        'spoken_languages' => ['English'],
        'skills' => ['PHP'],
        'phone' => '+491234567890',
        'telegram_username' => 'testuser',
        'address_line1' => '123 Main St',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country' => 'DE',
        'emergency_contact' => [
            'name' => 'Emergency Person',
            'phone' => '+490000000000',
            'telegram' => 'emergperson',
        ],
    ])->assertOk();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/staff', 'put');
});
