<?php

use App\Enums\GroupUserLevel;
use App\Models\App;
use App\Models\Convention;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use App\Services\Hydra\Client as HydraClient;
use App\Services\Hydra\HydraRequestException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the my data page for authenticated users', function () {
    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('getConsentSessions')->andReturn([]);
    });

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('my-data'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/MyData')
            ->has('profile')
            ->has('isStaff')
            ->has('connectedApps')
            ->has('activityLog')
        );
});

it('redirects unauthenticated users', function () {
    $this->get(route('my-data'))
        ->assertRedirect();
});

it('shows minimal data for non-staff users', function () {
    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('getConsentSessions')->andReturn([]);
    });

    $user = User::factory()->create(['name' => 'TestNick']);

    $this->actingAs($user)
        ->get(route('my-data'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isStaff', false)
            ->where('profile.name', 'TestNick')
            ->missing('groups')
            ->missing('conventions')
            ->missing('visibility')
        );
});

it('shows full data for staff users', function () {
    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('getConsentSessions')->andReturn([]);
    });

    $user = User::factory()->create([
        'firstname' => 'John',
        'lastname' => 'Doe',
    ]);

    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $user->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    $convention = Convention::create(['name' => 'EF28', 'year' => 2025, 'theme' => null]);
    $user->conventions()->attach($convention, ['is_staff' => true]);

    $this->actingAs($user)
        ->get(route('my-data'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('isStaff', true)
            ->has('profile.firstname')
            ->has('groups')
            ->has('conventions')
            ->has('visibility')
        );
});

it('shows connected apps from hydra consent sessions', function () {
    $app = App::withoutEvents(function () {
        return App::factory()->create([
            'client_id' => 'test-app',
            'name' => 'Test Application',
            'description' => 'A test app',
            'data' => ['policy_uri' => 'https://example.com/privacy'],
        ]);
    });

    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('getConsentSessions')->andReturn([
            [
                'consent_request' => [
                    'client' => ['client_id' => 'test-app'],
                ],
                'grant_scope' => ['openid', 'email'],
                'handled_at' => '2026-01-15T10:00:00Z',
            ],
        ]);
    });

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('my-data'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('connectedApps', 1)
            ->where('connectedApps.0.name', 'Test Application')
            ->where('connectedApps.0.policyUri', 'https://example.com/privacy')
            ->has('connectedApps.0.scopes', 2)
        );
});

it('handles hydra failure gracefully', function () {
    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('getConsentSessions')
            ->andThrow(new HydraRequestException('Failed', 500));
    });

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('my-data'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('connectedApps', null)
        );
});

it('filters out consent sessions without a matching local app', function () {
    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('getConsentSessions')->andReturn([
            [
                'consent_request' => [
                    'client' => ['client_id' => 'unknown-app'],
                ],
                'grant_scope' => ['openid'],
                'handled_at' => '2026-01-15T10:00:00Z',
            ],
        ]);
    });

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('my-data'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('connectedApps', 0)
        );
});

it('paginates activity log entries', function () {
    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('getConsentSessions')->andReturn([]);
    });

    $user = User::factory()->create();

    foreach (range(1, 25) as $i) {
        activity()
            ->on($user)
            ->by($user)
            ->log("test-action-{$i}");
    }

    $this->actingAs($user)
        ->get(route('my-data'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('activityLog.data', 20)
        );
});
