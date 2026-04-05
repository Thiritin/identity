<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function createStaffGroupForNda(): Group
{
    return Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);
}

function makeNdaStaffUser(Group $staffGroup, ?Group $group = null, GroupUserLevel $level = GroupUserLevel::Member): User
{
    $user = User::factory()->create();
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);
    if ($group) {
        $group->users()->attach($user, ['level' => $level]);
    }

    return $user;
}

it('allows a director to check NDA status', function () {
    Http::fake([
        'edna.test/*' => Http::response(
            '<html><form id="searchform"></form>Eurofurence NDA EN sent (01.04.26 20:09 UTC) – COMPLETED<br></html>'
        ),
    ]);

    $staffGroup = createStaffGroupForNda();
    $department = Group::factory()->department()->create();
    $director = makeNdaStaffUser($staffGroup, $department, GroupUserLevel::Director);
    $target = makeNdaStaffUser($staffGroup, $department);

    $response = $this->actingAs($director)
        ->postJson(route('directory.members.nda.check', $target->hashid));

    $response->assertSuccessful()
        ->assertJson(['signed' => true]);

    expect($target->fresh()->nda_checked_at)->not->toBeNull();
});

it('stores nda_checked_at only when signed', function () {
    Http::fake([
        'edna.test/*' => Http::response(
            '<html><form id="searchform"></form>Eurofurence NDA DE sent (01.04.26 20:09 UTC) – <font color=\'red\'>NOT COMPLETED</font><br></html>'
        ),
    ]);

    $staffGroup = createStaffGroupForNda();
    $department = Group::factory()->department()->create();
    $director = makeNdaStaffUser($staffGroup, $department, GroupUserLevel::Director);
    $target = makeNdaStaffUser($staffGroup, $department);

    $response = $this->actingAs($director)
        ->postJson(route('directory.members.nda.check', $target->hashid));

    $response->assertSuccessful()
        ->assertJson(['signed' => false]);

    expect($target->fresh()->nda_checked_at)->toBeNull();
});

it('forbids non-directors from checking NDA', function () {
    $staffGroup = createStaffGroupForNda();
    $department = Group::factory()->department()->create();
    $member = makeNdaStaffUser($staffGroup, $department, GroupUserLevel::Member);
    $target = makeNdaStaffUser($staffGroup, $department);

    $response = $this->actingAs($member)
        ->postJson(route('directory.members.nda.check', $target->hashid));

    $response->assertForbidden();
});

it('allows a director to send an NDA', function () {
    Http::fake([
        'edna.test/*' => Http::response('<html>NDA sent</html>'),
    ]);

    $staffGroup = createStaffGroupForNda();
    $department = Group::factory()->department()->create();
    $director = makeNdaStaffUser($staffGroup, $department, GroupUserLevel::Director);
    $target = makeNdaStaffUser($staffGroup, $department);

    $response = $this->actingAs($director)
        ->postJson(route('directory.members.nda.send', $target->hashid));

    $response->assertSuccessful();

    Http::assertSent(function ($request) use ($target) {
        return $request['check'] === '0'
            && $request['email'] === $target->email
            && $request['nickname'] === $target->name;
    });
});

it('sends NDA in user preferred language', function () {
    Http::fake([
        'edna.test/*' => Http::response('<html>NDA sent</html>'),
    ]);

    $staffGroup = createStaffGroupForNda();
    $department = Group::factory()->department()->create();
    $director = makeNdaStaffUser($staffGroup, $department, GroupUserLevel::Director);
    $target = makeNdaStaffUser($staffGroup, $department);
    $target->update(['preferences' => ['locale' => 'de']]);

    $response = $this->actingAs($director)
        ->postJson(route('directory.members.nda.send', $target->hashid));

    $response->assertSuccessful();

    Http::assertSent(fn ($request) => $request['type'] === 'de');
});

it('forbids non-directors from sending NDA', function () {
    $staffGroup = createStaffGroupForNda();
    $department = Group::factory()->department()->create();
    $teamLead = makeNdaStaffUser($staffGroup, $department, GroupUserLevel::TeamLead);
    $target = makeNdaStaffUser($staffGroup, $department);

    $response = $this->actingAs($teamLead)
        ->postJson(route('directory.members.nda.send', $target->hashid));

    $response->assertForbidden();
});

it('requires authentication for NDA check', function () {
    $staffGroup = createStaffGroupForNda();
    $target = makeNdaStaffUser($staffGroup);

    $response = $this->postJson(route('directory.members.nda.check', $target->hashid));

    $response->assertUnauthorized();
});

it('requires authentication for NDA send', function () {
    $staffGroup = createStaffGroupForNda();
    $target = makeNdaStaffUser($staffGroup);

    $response = $this->postJson(route('directory.members.nda.send', $target->hashid));

    $response->assertUnauthorized();
});

it('allows division directors to check NDA', function () {
    Http::fake([
        'edna.test/*' => Http::response(
            '<html><form id="searchform"></form>Eurofurence NDA EN – COMPLETED<br></html>'
        ),
    ]);

    $staffGroup = createStaffGroupForNda();
    $department = Group::factory()->department()->create();
    $divDirector = makeNdaStaffUser($staffGroup, $department, GroupUserLevel::DivisionDirector);
    $target = makeNdaStaffUser($staffGroup, $department);

    $response = $this->actingAs($divDirector)
        ->postJson(route('directory.members.nda.check', $target->hashid));

    $response->assertSuccessful();
});
