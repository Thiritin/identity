<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create staff group
    $this->staffGroup = Group::factory()->create([
        'name' => 'Staff',
        'type' => GroupTypeEnum::Default,
    ]);

    // Set staff group in config
    config(['groups.staff' => $this->staffGroup->id]);

    // Create staff user
    $this->staffUser = User::factory()->create(['email' => 'staff@test.com']);
    $this->staffUser->groups()->attach($this->staffGroup->id, ['level' => GroupUserLevel::Member]);

    // Create non-staff user
    $this->nonStaffUser = User::factory()->create(['email' => 'user@test.com']);

    // Create departments
    $this->department1 = Group::factory()->create([
        'name' => 'IT Department',
        'type' => GroupTypeEnum::Department,
    ]);

    $this->department2 = Group::factory()->create([
        'name' => 'HR Department',
        'type' => GroupTypeEnum::Department,
    ]);

    // Create teams under department1
    $this->team1 = Group::factory()->create([
        'name' => 'Dev Team',
        'type' => GroupTypeEnum::Team,
        'parent_id' => $this->department1->id,
    ]);

    $this->team2 = Group::factory()->create([
        'name' => 'QA Team',
        'type' => GroupTypeEnum::Team,
        'parent_id' => $this->department1->id,
    ]);

    // Add some users to departments/teams for realistic data
    $regularUser = User::factory()->create();
    $this->department1->users()->attach($regularUser->id, ['level' => GroupUserLevel::Member]);
    $this->team1->users()->attach($regularUser->id, ['level' => GroupUserLevel::Member]);
});

test('staff user can access departments with sanctum token', function () {
    // Create Sanctum token with groups.read scope
    $token = $this->staffUser->createToken('test-token', ['groups.read']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson('/api/v1/groups');

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->not->toBeEmpty();

    // Should include both departments since staff can see all departments
    $groupNames = collect($data)->pluck('name');
    expect($groupNames)->toContain('IT Department');
    expect($groupNames)->toContain('HR Department');
});

test('staff user can view specific department with sanctum token', function () {
    $token = $this->staffUser->createToken('test-token', ['groups.read']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson("/api/v1/groups/{$this->department1->hashid}");

    $response->assertOk();

    $data = $response->json('data');
    expect($data['name'])->toBe('IT Department');
    expect($data['type'])->toBe('department');
});

test('staff user can view team groups with sanctum token', function () {
    $token = $this->staffUser->createToken('test-token', ['groups.read']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson("/api/v1/groups/{$this->team1->hashid}");

    $response->assertOk();

    $data = $response->json('data');
    expect($data['name'])->toBe('Dev Team');
    expect($data['type'])->toBe('team');
});

test('non staff user cannot access departments they are not member of', function () {
    $token = $this->nonStaffUser->createToken('test-token', ['groups.read']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson("/api/v1/groups/{$this->department1->hashid}");

    $response->assertForbidden();
    $response->assertJson([
        'message' => 'You must be a staff member to access this department/team.',
    ]);
});

test('sanctum token without groups read scope is denied', function () {
    $token = $this->staffUser->createToken('test-token', ['profile']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson('/api/v1/groups');

    $response->assertForbidden();
    $response->assertJson([
        'message' => 'Insufficient permissions, groups.read is missing',
    ]);
});

test('staff user can access group members with sanctum token', function () {
    $token = $this->staffUser->createToken('test-token', ['groups.read']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson("/api/v1/groups/{$this->department1->hashid}/users");

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toBeArray();
});

test('staff user gets all departments in index with sanctum token', function () {
    // Add staff user to one department to test the filtering logic
    $this->staffUser->groups()->attach($this->department2->id, ['level' => GroupUserLevel::Admin]);

    $token = $this->staffUser->createToken('test-token', ['groups.read']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson('/api/v1/groups');

    $response->assertOk();

    $data = $response->json('data');
    $groupNames = collect($data)->pluck('name');

    // Should see both departments: one they're a member of, and others because they're staff
    expect($groupNames)->toContain('IT Department');
    expect($groupNames)->toContain('HR Department');
});

test('non staff user only sees groups they are member of', function () {
    // Create a regular (non-department) group to avoid automatic staff membership
    $regularGroup = Group::factory()->create([
        'name' => 'Regular Group',
        'type' => GroupTypeEnum::Default,
    ]);
    
    // Add non-staff user to only the regular group (not department)
    $this->nonStaffUser->groups()->attach($regularGroup->id, ['level' => GroupUserLevel::Member]);

    $token = $this->nonStaffUser->createToken('test-token', ['groups.read']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson('/api/v1/groups');

    $response->assertOk();

    $data = $response->json('data');
    $groupNames = collect($data)->pluck('name');

    // Should only see the group they're a member of
    expect($groupNames)->toContain('Regular Group');
    expect($groupNames)->not->toContain('IT Department');
    expect($groupNames)->not->toContain('HR Department');
});

test('staff user without token cannot access api', function () {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
    ])->getJson('/api/v1/groups');

    $response->assertUnauthorized();
});

test('expired token is rejected', function () {
    $token = $this->staffUser->createToken('test-token', ['groups.read'], now()->subMinute());

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson('/api/v1/groups');

    $response->assertUnauthorized();
});

test('staff config missing prevents access', function () {
    // Clear staff group config
    config(['groups.staff' => null]);

    $token = $this->staffUser->createToken('test-token', ['groups.read']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson("/api/v1/groups/{$this->department1->hashid}");

    $response->assertForbidden();
    $response->assertJson([
        'message' => 'Staff group is not configured. Please contact administrator.',
    ]);
});

test('user with view_full_staff_details scope can see email addresses', function () {
    // Add a user to the department to test
    $this->department1->users()->attach($this->staffUser->id, ['level' => GroupUserLevel::Member]);
    
    $token = $this->staffUser->createToken('test-token', ['groups.read', 'view_full_staff_details']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson("/api/v1/groups/{$this->department1->hashid}/users");

    $response->assertOk();
    $data = $response->json('data');
    
    // Should contain email addresses
    expect($data)->toBeArray();
    expect($data[0]['email'])->not->toBeNull();
    expect($data[0]['name'])->not->toBeNull();
    expect($data[0]['user_id'])->not->toBeNull();
});

test('user without view_full_staff_details scope cannot see email addresses', function () {
    // Add a user to the department to test
    $this->department1->users()->attach($this->staffUser->id, ['level' => GroupUserLevel::Member]);
    
    $token = $this->staffUser->createToken('test-token', ['groups.read']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
        'Accept' => 'application/json',
    ])->getJson("/api/v1/groups/{$this->department1->hashid}/users");

    $response->assertOk();
    $data = $response->json('data');
    
    // Should NOT contain email addresses but should have other fields
    expect($data)->toBeArray();
    expect($data[0]['email'])->toBeNull();
    expect($data[0]['name'])->not->toBeNull();
    expect($data[0]['user_id'])->not->toBeNull();
    expect($data[0]['level'])->toBe('member');
});
