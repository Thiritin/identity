# Event-Driven Group Integration Abstraction — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Decouple group/user observers from Nextcloud by introducing domain events and independent listeners.

**Architecture:** Observers fire domain events with zero business logic. Listeners handle Nextcloud integration (with environment guards), staff membership sync, and group owner assignment. Nextcloud jobs remain unchanged.

**Tech Stack:** Laravel 12, Pest 4, PHP 8.3, `Event::fake()`, `Bus::fake()`, `Http::fake()`

**Spec:** `docs/superpowers/specs/2026-04-02-nextcloud-event-driven-abstraction-design.md`

**Important context:**
- This project uses Laravel Sail — all commands use `vendor/bin/sail`
- Tests are Pest-style in `tests/Feature/` and `tests/Unit/`
- The `GroupUser` pivot model uses `App\Models\GroupUser` (extends `Pivot`)
- Groups use `Mtvs\EloquentHashids\HasHashid` for hashid routing
- The `Group` factory only sets `name` and `logo` — pass `type`, `system_name`, `nextcloud_folder_name`, `nextcloud_folder_id` etc. explicitly
- The `GroupUser` pivot has no timestamps and no auto-incrementing ID
- Nextcloud API returns XML responses — tests need XML string fakes
- Run `vendor/bin/sail bin pint --dirty --format agent` before committing

---

## File Structure

### New Files

| File | Responsibility |
|---|---|
| `app/Events/GroupUserAdded.php` | Event: user added to group |
| `app/Events/GroupUserUpdated.php` | Event: user's group level changed |
| `app/Events/GroupUserRemoved.php` | Event: user removed from group |
| `app/Events/GroupCreated.php` | Event: group created |
| `app/Events/GroupUpdated.php` | Event: group name/folder changed |
| `app/Events/GroupDeleted.php` | Event: group deleted |
| `app/Listeners/Concerns/ChecksNextcloudEnvironment.php` | Trait: `shouldHandle()` guard |
| `app/Listeners/CheckStaffGroupMembership.php` | Sync staff group on department changes |
| `app/Listeners/AssignGroupOwner.php` | Attach auth user as owner on group create |
| `app/Listeners/Nextcloud/AddUserToNextcloudGroup.php` | Dispatch `AddUserToGroupJob` |
| `app/Listeners/Nextcloud/UpdateUserNextcloudGroupLevel.php` | Dispatch `UpdateUserGroupLevelJob` |
| `app/Listeners/Nextcloud/RemoveUserFromNextcloudGroup.php` | Dispatch `RemoveUserFromGroupJob` |
| `app/Listeners/Nextcloud/CreateNextcloudGroup.php` | Dispatch `CreateGroupJob` |
| `app/Listeners/Nextcloud/UpdateNextcloudGroup.php` | Dispatch `UpdateGroupJob` |
| `app/Listeners/Nextcloud/DeleteNextcloudGroup.php` | Dispatch `DeleteGroupJob` |
| `tests/Feature/Observers/GroupUserObserverTest.php` | Observer event dispatch tests |
| `tests/Feature/Observers/GroupObserverTest.php` | Observer event dispatch tests |
| `tests/Feature/Listeners/CheckStaffGroupMembershipListenerTest.php` | Staff sync logic tests |
| `tests/Feature/Listeners/AssignGroupOwnerListenerTest.php` | Owner assignment tests |
| `tests/Feature/Listeners/Nextcloud/AddUserToNextcloudGroupListenerTest.php` | Listener tests |
| `tests/Feature/Listeners/Nextcloud/RemoveUserFromNextcloudGroupListenerTest.php` | Listener tests |
| `tests/Feature/Listeners/Nextcloud/UpdateUserNextcloudGroupLevelListenerTest.php` | Listener tests |
| `tests/Feature/Listeners/Nextcloud/CreateNextcloudGroupListenerTest.php` | Listener tests |
| `tests/Feature/Listeners/Nextcloud/UpdateNextcloudGroupListenerTest.php` | Listener tests |
| `tests/Feature/Listeners/Nextcloud/DeleteNextcloudGroupListenerTest.php` | Listener tests |
| `tests/Feature/Jobs/Nextcloud/AddUserToGroupJobTest.php` | Job HTTP tests |
| `tests/Feature/Jobs/Nextcloud/RemoveUserFromGroupJobTest.php` | Job HTTP tests |
| `tests/Feature/Jobs/Nextcloud/UpdateUserGroupLevelJobTest.php` | Job HTTP tests |
| `tests/Feature/Jobs/Nextcloud/CreateGroupJobTest.php` | Job HTTP tests |
| `tests/Feature/Jobs/Nextcloud/UpdateGroupJobTest.php` | Job HTTP tests |
| `tests/Feature/Jobs/Nextcloud/DeleteGroupJobTest.php` | Job HTTP tests |

### Modified Files

| File | Change |
|---|---|
| `app/Observers/GroupUserObserver.php` | Strip all logic, only dispatch events |
| `app/Observers/GroupObserver.php` | Strip all logic, only dispatch events |
| `app/Providers/EventServiceProvider.php` | Register new events → listeners |

### Deleted Files

| File | Reason |
|---|---|
| `app/Events/GroupUserAddedEvent.php` | Replaced by `GroupUserAdded` |
| `app/Events/GroupUserRemovedEvent.php` | Replaced by `GroupUserRemoved` |
| `app/Jobs/CheckStaffGroupMembershipJob.php` | Logic absorbed into listener |

---

## Task 1: Create Domain Events

**Files:**
- Create: `app/Events/GroupUserAdded.php`
- Create: `app/Events/GroupUserUpdated.php`
- Create: `app/Events/GroupUserRemoved.php`
- Create: `app/Events/GroupCreated.php`
- Create: `app/Events/GroupUpdated.php`
- Create: `app/Events/GroupDeleted.php`

- [ ] **Step 1: Create `GroupUserAdded` event**

```php
<?php

namespace App\Events;

use App\Models\GroupUser;
use Illuminate\Foundation\Events\Dispatchable;

class GroupUserAdded
{
    use Dispatchable;

    public function __construct(
        public GroupUser $groupUser
    ) {}
}
```

- [ ] **Step 2: Create `GroupUserUpdated` event**

```php
<?php

namespace App\Events;

use App\Enums\GroupUserLevel;
use App\Models\GroupUser;
use Illuminate\Foundation\Events\Dispatchable;

class GroupUserUpdated
{
    use Dispatchable;

    public function __construct(
        public GroupUser $groupUser,
        public GroupUserLevel $oldLevel
    ) {}
}
```

- [ ] **Step 3: Create `GroupUserRemoved` event**

```php
<?php

namespace App\Events;

use App\Models\GroupUser;
use Illuminate\Foundation\Events\Dispatchable;

class GroupUserRemoved
{
    use Dispatchable;

    public function __construct(
        public GroupUser $groupUser
    ) {}
}
```

- [ ] **Step 4: Create `GroupCreated` event**

```php
<?php

namespace App\Events;

use App\Models\Group;
use Illuminate\Foundation\Events\Dispatchable;

class GroupCreated
{
    use Dispatchable;

    public function __construct(
        public Group $group
    ) {}
}
```

- [ ] **Step 5: Create `GroupUpdated` event**

```php
<?php

namespace App\Events;

use App\Models\Group;
use Illuminate\Foundation\Events\Dispatchable;

class GroupUpdated
{
    use Dispatchable;

    public function __construct(
        public Group $group,
        public array $originalData,
        public array $changedFields
    ) {}
}
```

- [ ] **Step 6: Create `GroupDeleted` event**

```php
<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class GroupDeleted
{
    use Dispatchable;

    public function __construct(
        public string $groupHashid,
        public int $groupId
    ) {}
}
```

- [ ] **Step 7: Delete old events**

```bash
rm app/Events/GroupUserAddedEvent.php app/Events/GroupUserRemovedEvent.php
```

- [ ] **Step 8: Run pint and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Events/
git commit -m "Add domain events for group integration abstraction"
```

---

## Task 2: Create ChecksNextcloudEnvironment Trait

**Files:**
- Create: `app/Listeners/Concerns/ChecksNextcloudEnvironment.php`

- [ ] **Step 1: Create the trait**

```php
<?php

namespace App\Listeners\Concerns;

use Illuminate\Support\Facades\App;

trait ChecksNextcloudEnvironment
{
    protected function shouldHandle(): bool
    {
        return ! App::isLocal() && ! app()->runningUnitTests();
    }
}
```

- [ ] **Step 2: Run pint and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Listeners/Concerns/ChecksNextcloudEnvironment.php
git commit -m "Add ChecksNextcloudEnvironment trait for listener guards"
```

---

## Task 3: Create Business Logic Listeners

**Files:**
- Create: `app/Listeners/CheckStaffGroupMembership.php`
- Create: `app/Listeners/AssignGroupOwner.php`

- [ ] **Step 1: Write test for CheckStaffGroupMembership**

Create `tests/Feature/Listeners/CheckStaffGroupMembershipListenerTest.php`:

```php
<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Events\GroupUserRemoved;
use App\Listeners\CheckStaffGroupMembership;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
    ]);
});

it('adds user to staff group when added to a department', function () {
    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);

    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $department->id)->first();

    $listener = new CheckStaffGroupMembership();
    $listener->handle(new GroupUserAdded($groupUser));

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('does not add user to staff group when added to a non-department group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['type' => GroupTypeEnum::Default]);

    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new CheckStaffGroupMembership();
    $listener->handle(new GroupUserAdded($groupUser));

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('removes user from staff group when removed from last department', function () {
    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);

    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $this->staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $department->id)->first();
    $department->users()->detach($user);

    $listener = new CheckStaffGroupMembership();
    $listener->handle(new GroupUserRemoved($groupUser));

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('keeps user in staff group when removed from one department but still in another', function () {
    $user = User::factory()->create();
    $dept1 = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $dept2 = Group::factory()->create(['type' => GroupTypeEnum::Department]);

    $dept1->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $dept2->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $this->staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $dept1->id)->first();
    $dept1->users()->detach($user);

    $listener = new CheckStaffGroupMembership();
    $listener->handle(new GroupUserRemoved($groupUser));

    expect($this->staffGroup->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('handles missing staff group gracefully', function () {
    $this->staffGroup->delete();

    $user = User::factory()->create();
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);

    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $department->id)->first();

    $listener = new CheckStaffGroupMembership();
    $listener->handle(new GroupUserAdded($groupUser));

    // Should not throw
    expect(true)->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Listeners/CheckStaffGroupMembershipListenerTest.php
```

Expected: FAIL — `CheckStaffGroupMembership` class not found.

- [ ] **Step 3: Create `CheckStaffGroupMembership` listener**

```php
<?php

namespace App\Listeners;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Events\GroupUserRemoved;
use App\Models\Group;

class CheckStaffGroupMembership
{
    public function handle(GroupUserAdded|GroupUserRemoved $event): void
    {
        if ($event->groupUser->group->type !== GroupTypeEnum::Department) {
            return;
        }

        $staffGroup = Group::where('system_name', 'staff')->first();

        if (! $staffGroup) {
            return;
        }

        $user = $event->groupUser->user;
        $isMemberInAnyDepartment = $user->groups()->where('type', 'department')->exists();

        if ($isMemberInAnyDepartment) {
            $staffGroup->users()->syncWithoutDetaching([$user->id => ['level' => GroupUserLevel::Member]]);
        } else {
            $staffGroup->users()->detach($user->id);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Listeners/CheckStaffGroupMembershipListenerTest.php
```

Expected: All 5 tests PASS.

- [ ] **Step 5: Write test for AssignGroupOwner**

Create `tests/Feature/Listeners/AssignGroupOwnerListenerTest.php`:

```php
<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Events\GroupCreated;
use App\Listeners\AssignGroupOwner;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('assigns authenticated user as owner of new group', function () {
    $user = User::factory()->create();
    Auth::shouldReceive('user')->andReturn($user);

    $group = Group::factory()->create();

    $listener = new AssignGroupOwner();
    $listener->handle(new GroupCreated($group));

    expect($group->users()->where('user_id', $user->id)->wherePivot('level', GroupUserLevel::Owner)->exists())->toBeTrue();
});

it('does not assign owner when no authenticated user', function () {
    Auth::shouldReceive('user')->andReturn(null);

    $group = Group::factory()->create();

    $listener = new AssignGroupOwner();
    $listener->handle(new GroupCreated($group));

    expect($group->users()->count())->toBe(0);
});

it('does not assign owner for team group with nextcloud parent', function () {
    $user = User::factory()->create();
    Auth::shouldReceive('user')->andReturn($user);

    $parent = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $group = Group::factory()->create([
        'type' => GroupTypeEnum::Team,
        'parent_id' => $parent->id,
    ]);

    $listener = new AssignGroupOwner();
    $listener->handle(new GroupCreated($group));

    expect($group->users()->where('user_id', $user->id)->exists())->toBeFalse();
});
```

- [ ] **Step 6: Run test to verify it fails**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Listeners/AssignGroupOwnerListenerTest.php
```

Expected: FAIL — `AssignGroupOwner` class not found.

- [ ] **Step 7: Create `AssignGroupOwner` listener**

```php
<?php

namespace App\Listeners;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Events\GroupCreated;
use Illuminate\Support\Facades\Auth;

class AssignGroupOwner
{
    public function handle(GroupCreated $event): void
    {
        $group = $event->group;

        if ($group->type === GroupTypeEnum::Team && $group->parent?->nextcloud_folder_id) {
            return;
        }

        $user = Auth::user();

        if (! $user) {
            return;
        }

        $group->users()->attach($user, [
            'level' => GroupUserLevel::Owner,
        ]);
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Listeners/AssignGroupOwnerListenerTest.php
```

Expected: All 3 tests PASS.

- [ ] **Step 9: Run pint and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Listeners/CheckStaffGroupMembership.php app/Listeners/AssignGroupOwner.php tests/Feature/Listeners/
git commit -m "Add business logic listeners with tests"
```

---

## Task 4: Create Nextcloud Listeners

**Files:**
- Create: `app/Listeners/Nextcloud/AddUserToNextcloudGroup.php`
- Create: `app/Listeners/Nextcloud/UpdateUserNextcloudGroupLevel.php`
- Create: `app/Listeners/Nextcloud/RemoveUserFromNextcloudGroup.php`
- Create: `app/Listeners/Nextcloud/CreateNextcloudGroup.php`
- Create: `app/Listeners/Nextcloud/UpdateNextcloudGroup.php`
- Create: `app/Listeners/Nextcloud/DeleteNextcloudGroup.php`
- Create: `tests/Feature/Listeners/Nextcloud/AddUserToNextcloudGroupListenerTest.php`
- Create: `tests/Feature/Listeners/Nextcloud/UpdateUserNextcloudGroupLevelListenerTest.php`
- Create: `tests/Feature/Listeners/Nextcloud/RemoveUserFromNextcloudGroupListenerTest.php`
- Create: `tests/Feature/Listeners/Nextcloud/CreateNextcloudGroupListenerTest.php`
- Create: `tests/Feature/Listeners/Nextcloud/UpdateNextcloudGroupListenerTest.php`
- Create: `tests/Feature/Listeners/Nextcloud/DeleteNextcloudGroupListenerTest.php`

- [ ] **Step 1: Write test for AddUserToNextcloudGroup**

Create `tests/Feature/Listeners/Nextcloud/AddUserToNextcloudGroupListenerTest.php`:

```php
<?php

use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Jobs\Nextcloud\AddUserToGroupJob;
use App\Listeners\Nextcloud\AddUserToNextcloudGroup;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches AddUserToGroupJob when group has nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new AddUserToNextcloudGroup();
    $listener->handle(new GroupUserAdded($groupUser));

    Bus::assertDispatched(AddUserToGroupJob::class, function ($job) use ($group, $user) {
        return $job->group->id === $group->id
            && $job->user->id === $user->id
            && $job->level === GroupUserLevel::Member;
    });
});

it('dispatches AddUserToGroupJob when parent has nextcloud folder', function () {
    Bus::fake();

    $parent = Group::factory()->create(['nextcloud_folder_name' => 'Parent Folder']);
    $group = Group::factory()->create(['parent_id' => $parent->id]);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Admin]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new AddUserToNextcloudGroup();
    $listener->handle(new GroupUserAdded($groupUser));

    Bus::assertDispatched(AddUserToGroupJob::class);
});

it('does not dispatch job when group has no nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new AddUserToNextcloudGroup();
    $listener->handle(new GroupUserAdded($groupUser));

    Bus::assertNotDispatched(AddUserToGroupJob::class);
});

it('does not dispatch job on local environment', function () {
    Bus::fake();
    app()->detectEnvironment(fn () => 'local');

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new AddUserToNextcloudGroup();
    $listener->handle(new GroupUserAdded($groupUser));

    Bus::assertNotDispatched(AddUserToGroupJob::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Listeners/Nextcloud/AddUserToNextcloudGroupListenerTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Create `AddUserToNextcloudGroup` listener**

```php
<?php

namespace App\Listeners\Nextcloud;

use App\Events\GroupUserAdded;
use App\Jobs\Nextcloud\AddUserToGroupJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class AddUserToNextcloudGroup
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupUserAdded $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        $group = $event->groupUser->group;

        if (! $group->nextcloud_folder_name && ! $group->parent?->nextcloud_folder_name) {
            return;
        }

        AddUserToGroupJob::dispatch($group, $event->groupUser->user, $event->groupUser->level);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Listeners/Nextcloud/AddUserToNextcloudGroupListenerTest.php
```

Expected: All 4 tests PASS.

- [ ] **Step 5: Write test for RemoveUserFromNextcloudGroup**

Create `tests/Feature/Listeners/Nextcloud/RemoveUserFromNextcloudGroupListenerTest.php`:

```php
<?php

use App\Enums\GroupUserLevel;
use App\Events\GroupUserRemoved;
use App\Jobs\Nextcloud\RemoveUserFromGroupJob;
use App\Listeners\Nextcloud\RemoveUserFromNextcloudGroup;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches RemoveUserFromGroupJob when group has nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();
    $group->users()->detach($user);

    $listener = new RemoveUserFromNextcloudGroup();
    $listener->handle(new GroupUserRemoved($groupUser));

    Bus::assertDispatched(RemoveUserFromGroupJob::class, function ($job) use ($group, $user) {
        return $job->group->id === $group->id && $job->user->id === $user->id;
    });
});

it('does not dispatch job when group has no nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new RemoveUserFromNextcloudGroup();
    $listener->handle(new GroupUserRemoved($groupUser));

    Bus::assertNotDispatched(RemoveUserFromGroupJob::class);
});

it('does not dispatch job on local environment', function () {
    Bus::fake();
    app()->detectEnvironment(fn () => 'local');

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new RemoveUserFromNextcloudGroup();
    $listener->handle(new GroupUserRemoved($groupUser));

    Bus::assertNotDispatched(RemoveUserFromGroupJob::class);
});
```

- [ ] **Step 6: Create `RemoveUserFromNextcloudGroup` listener**

```php
<?php

namespace App\Listeners\Nextcloud;

use App\Events\GroupUserRemoved;
use App\Jobs\Nextcloud\RemoveUserFromGroupJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class RemoveUserFromNextcloudGroup
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupUserRemoved $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        $group = $event->groupUser->group;

        if (! $group->nextcloud_folder_name) {
            return;
        }

        RemoveUserFromGroupJob::dispatch($group, $event->groupUser->user, $group->type);
    }
}
```

- [ ] **Step 7: Run test to verify it passes**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Listeners/Nextcloud/RemoveUserFromNextcloudGroupListenerTest.php
```

Expected: All 3 tests PASS.

- [ ] **Step 8: Write test for UpdateUserNextcloudGroupLevel**

Create `tests/Feature/Listeners/Nextcloud/UpdateUserNextcloudGroupLevelListenerTest.php`:

```php
<?php

use App\Enums\GroupUserLevel;
use App\Events\GroupUserUpdated;
use App\Jobs\Nextcloud\UpdateUserGroupLevelJob;
use App\Listeners\Nextcloud\UpdateUserNextcloudGroupLevel;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches UpdateUserGroupLevelJob when group has nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new UpdateUserNextcloudGroupLevel();
    $listener->handle(new GroupUserUpdated($groupUser, GroupUserLevel::Member));

    Bus::assertDispatched(UpdateUserGroupLevelJob::class, function ($job) use ($group, $user) {
        return $job->group->id === $group->id
            && $job->user->id === $user->id
            && $job->oldLevel === GroupUserLevel::Member;
    });
});

it('does not dispatch job when group has no nextcloud folder', function () {
    Bus::fake();

    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Admin]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new UpdateUserNextcloudGroupLevel();
    $listener->handle(new GroupUserUpdated($groupUser, GroupUserLevel::Member));

    Bus::assertNotDispatched(UpdateUserGroupLevelJob::class);
});

it('does not dispatch job on local environment', function () {
    Bus::fake();
    app()->detectEnvironment(fn () => 'local');

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder']);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Admin]);
    $groupUser = GroupUser::where('user_id', $user->id)->where('group_id', $group->id)->first();

    $listener = new UpdateUserNextcloudGroupLevel();
    $listener->handle(new GroupUserUpdated($groupUser, GroupUserLevel::Member));

    Bus::assertNotDispatched(UpdateUserGroupLevelJob::class);
});
```

- [ ] **Step 9: Create `UpdateUserNextcloudGroupLevel` listener**

```php
<?php

namespace App\Listeners\Nextcloud;

use App\Events\GroupUserUpdated;
use App\Jobs\Nextcloud\UpdateUserGroupLevelJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class UpdateUserNextcloudGroupLevel
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupUserUpdated $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        if (! $event->groupUser->group->nextcloud_folder_name) {
            return;
        }

        UpdateUserGroupLevelJob::dispatch(
            $event->groupUser->group,
            $event->groupUser->user,
            $event->groupUser->level,
            $event->oldLevel
        );
    }
}
```

- [ ] **Step 10: Run test to verify it passes**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Listeners/Nextcloud/UpdateUserNextcloudGroupLevelListenerTest.php
```

Expected: All 3 tests PASS.

- [ ] **Step 11: Write test for CreateNextcloudGroup**

Create `tests/Feature/Listeners/Nextcloud/CreateNextcloudGroupListenerTest.php`:

```php
<?php

use App\Enums\GroupTypeEnum;
use App\Events\GroupCreated;
use App\Jobs\Nextcloud\CreateGroupJob;
use App\Listeners\Nextcloud\CreateNextcloudGroup;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches CreateGroupJob for team group with parent nextcloud folder', function () {
    Bus::fake();

    $parent = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $group = Group::factory()->create([
        'type' => GroupTypeEnum::Team,
        'parent_id' => $parent->id,
    ]);

    $listener = new CreateNextcloudGroup();
    $listener->handle(new GroupCreated($group));

    Bus::assertDispatched(CreateGroupJob::class, function ($job) use ($group) {
        return $job->group->id === $group->id
            && $job->isTeamGroup === true
            && $job->parentFolderId === 42;
    });
});

it('does not dispatch for non-team groups', function () {
    Bus::fake();

    $group = Group::factory()->create(['type' => GroupTypeEnum::Default]);

    $listener = new CreateNextcloudGroup();
    $listener->handle(new GroupCreated($group));

    Bus::assertNotDispatched(CreateGroupJob::class);
});

it('does not dispatch for team group without parent nextcloud folder', function () {
    Bus::fake();

    $parent = Group::factory()->create();
    $group = Group::factory()->create([
        'type' => GroupTypeEnum::Team,
        'parent_id' => $parent->id,
    ]);

    $listener = new CreateNextcloudGroup();
    $listener->handle(new GroupCreated($group));

    Bus::assertNotDispatched(CreateGroupJob::class);
});

it('does not dispatch on local environment', function () {
    Bus::fake();
    app()->detectEnvironment(fn () => 'local');

    $parent = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $group = Group::factory()->create([
        'type' => GroupTypeEnum::Team,
        'parent_id' => $parent->id,
    ]);

    $listener = new CreateNextcloudGroup();
    $listener->handle(new GroupCreated($group));

    Bus::assertNotDispatched(CreateGroupJob::class);
});
```

- [ ] **Step 12: Create `CreateNextcloudGroup` listener**

```php
<?php

namespace App\Listeners\Nextcloud;

use App\Enums\GroupTypeEnum;
use App\Events\GroupCreated;
use App\Jobs\Nextcloud\CreateGroupJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class CreateNextcloudGroup
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupCreated $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        $group = $event->group;

        if ($group->type !== GroupTypeEnum::Team || ! $group->parent?->nextcloud_folder_id) {
            return;
        }

        CreateGroupJob::dispatch($group, true, $group->parent->nextcloud_folder_id);
    }
}
```

- [ ] **Step 13: Run test to verify it passes**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Listeners/Nextcloud/CreateNextcloudGroupListenerTest.php
```

Expected: All 4 tests PASS.

- [ ] **Step 14: Write test for UpdateNextcloudGroup**

Create `tests/Feature/Listeners/Nextcloud/UpdateNextcloudGroupListenerTest.php`:

```php
<?php

use App\Events\GroupUpdated;
use App\Jobs\Nextcloud\UpdateGroupJob;
use App\Listeners\Nextcloud\UpdateNextcloudGroup;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches UpdateGroupJob', function () {
    Bus::fake();

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test']);

    $listener = new UpdateNextcloudGroup();
    $listener->handle(new GroupUpdated($group, ['name' => 'Old Name'], ['name']));

    Bus::assertDispatched(UpdateGroupJob::class, function ($job) use ($group) {
        return $job->group->id === $group->id && in_array('name', $job->changedFields);
    });
});

it('does not dispatch on local environment', function () {
    Bus::fake();
    app()->detectEnvironment(fn () => 'local');

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test']);

    $listener = new UpdateNextcloudGroup();
    $listener->handle(new GroupUpdated($group, [], ['name']));

    Bus::assertNotDispatched(UpdateGroupJob::class);
});
```

- [ ] **Step 15: Create `UpdateNextcloudGroup` listener**

```php
<?php

namespace App\Listeners\Nextcloud;

use App\Events\GroupUpdated;
use App\Jobs\Nextcloud\UpdateGroupJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class UpdateNextcloudGroup
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupUpdated $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        UpdateGroupJob::dispatch($event->group, $event->originalData, $event->changedFields);
    }
}
```

- [ ] **Step 16: Write test for DeleteNextcloudGroup**

Create `tests/Feature/Listeners/Nextcloud/DeleteNextcloudGroupListenerTest.php`:

```php
<?php

use App\Events\GroupDeleted;
use App\Jobs\Nextcloud\DeleteGroupJob;
use App\Listeners\Nextcloud\DeleteNextcloudGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('dispatches DeleteGroupJob', function () {
    Bus::fake();

    $listener = new DeleteNextcloudGroup();
    $listener->handle(new GroupDeleted('abc123', 1));

    Bus::assertDispatched(DeleteGroupJob::class, function ($job) {
        return $job->groupHashid === 'abc123' && $job->groupId === 1;
    });
});

it('does not dispatch on local environment', function () {
    Bus::fake();
    app()->detectEnvironment(fn () => 'local');

    $listener = new DeleteNextcloudGroup();
    $listener->handle(new GroupDeleted('abc123', 1));

    Bus::assertNotDispatched(DeleteGroupJob::class);
});
```

- [ ] **Step 17: Create `DeleteNextcloudGroup` listener**

```php
<?php

namespace App\Listeners\Nextcloud;

use App\Events\GroupDeleted;
use App\Jobs\Nextcloud\DeleteGroupJob;
use App\Listeners\Concerns\ChecksNextcloudEnvironment;

class DeleteNextcloudGroup
{
    use ChecksNextcloudEnvironment;

    public function handle(GroupDeleted $event): void
    {
        if (! $this->shouldHandle()) {
            return;
        }

        DeleteGroupJob::dispatch($event->groupHashid, $event->groupId);
    }
}
```

- [ ] **Step 18: Run all Nextcloud listener tests**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Listeners/Nextcloud/
```

Expected: All tests PASS.

- [ ] **Step 19: Run pint and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Listeners/Nextcloud/ app/Listeners/Concerns/ tests/Feature/Listeners/Nextcloud/
git commit -m "Add Nextcloud listeners with tests"
```

---

## Task 5: Refactor Observers and Wire Up EventServiceProvider

**Files:**
- Modify: `app/Observers/GroupUserObserver.php`
- Modify: `app/Observers/GroupObserver.php`
- Modify: `app/Providers/EventServiceProvider.php`
- Create: `tests/Feature/Observers/GroupUserObserverTest.php`
- Create: `tests/Feature/Observers/GroupObserverTest.php`
- Delete: `app/Jobs/CheckStaffGroupMembershipJob.php`

- [ ] **Step 1: Write GroupUserObserver test**

Create `tests/Feature/Observers/GroupUserObserverTest.php`:

```php
<?php

use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Events\GroupUserRemoved;
use App\Events\GroupUserUpdated;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('dispatches GroupUserAdded when user is added to group', function () {
    Event::fake([GroupUserAdded::class]);

    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);

    Event::assertDispatched(GroupUserAdded::class, function ($event) use ($user, $group) {
        return $event->groupUser->user_id === $user->id
            && $event->groupUser->group_id === $group->id;
    });
});

it('dispatches GroupUserUpdated when user level changes', function () {
    Event::fake([GroupUserUpdated::class]);

    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $group->users()->updateExistingPivot($user, ['level' => GroupUserLevel::Admin]);

    Event::assertDispatched(GroupUserUpdated::class, function ($event) use ($user) {
        return $event->groupUser->user_id === $user->id
            && $event->oldLevel === GroupUserLevel::Member;
    });
});

it('does not dispatch GroupUserUpdated when non-level field changes', function () {
    Event::fake([GroupUserUpdated::class]);

    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $group->users()->updateExistingPivot($user, ['title' => 'Lead']);

    Event::assertNotDispatched(GroupUserUpdated::class);
});

it('dispatches GroupUserRemoved when user is removed from group', function () {
    Event::fake([GroupUserRemoved::class]);

    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);

    Event::fake([GroupUserRemoved::class]); // Re-fake to clear the attach events

    $group->users()->detach($user);

    Event::assertDispatched(GroupUserRemoved::class, function ($event) use ($user, $group) {
        return $event->groupUser->user_id === $user->id
            && $event->groupUser->group_id === $group->id;
    });
});
```

- [ ] **Step 2: Write GroupObserver test**

Create `tests/Feature/Observers/GroupObserverTest.php`:

```php
<?php

use App\Events\GroupCreated;
use App\Events\GroupDeleted;
use App\Events\GroupUpdated;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('dispatches GroupCreated when group is created', function () {
    Event::fake([GroupCreated::class]);

    $group = Group::factory()->create();

    Event::assertDispatched(GroupCreated::class, function ($event) use ($group) {
        return $event->group->id === $group->id;
    });
});

it('dispatches GroupUpdated when group name changes', function () {
    $group = Group::factory()->create();

    Event::fake([GroupUpdated::class]);

    $group->update(['name' => 'New Name']);

    Event::assertDispatched(GroupUpdated::class, function ($event) use ($group) {
        return $event->group->id === $group->id
            && in_array('name', $event->changedFields);
    });
});

it('dispatches GroupUpdated when nextcloud_folder_name changes', function () {
    $group = Group::factory()->create();

    Event::fake([GroupUpdated::class]);

    $group->update(['nextcloud_folder_name' => 'New Folder']);

    Event::assertDispatched(GroupUpdated::class, function ($event) {
        return in_array('nextcloud_folder_name', $event->changedFields);
    });
});

it('does not dispatch GroupUpdated when unrelated field changes', function () {
    $group = Group::factory()->create();

    Event::fake([GroupUpdated::class]);

    $group->update(['description' => 'Updated description']);

    Event::assertNotDispatched(GroupUpdated::class);
});

it('dispatches GroupDeleted when group is deleted', function () {
    $group = Group::factory()->create();
    $hashid = $group->hashid;
    $id = $group->id;

    Event::fake([GroupDeleted::class]);

    $group->delete();

    Event::assertDispatched(GroupDeleted::class, function ($event) use ($hashid, $id) {
        return $event->groupHashid === $hashid && $event->groupId === $id;
    });
});
```

- [ ] **Step 3: Run observer tests to verify they fail**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Observers/
```

Expected: FAIL — observers still have old logic, events not dispatched.

- [ ] **Step 4: Refactor `GroupUserObserver`**

Replace entire contents of `app/Observers/GroupUserObserver.php`:

```php
<?php

namespace App\Observers;

use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Events\GroupUserRemoved;
use App\Events\GroupUserUpdated;
use App\Models\GroupUser;

class GroupUserObserver
{
    public function created(GroupUser $groupUser): void
    {
        GroupUserAdded::dispatch($groupUser);
    }

    public function updated(GroupUser $groupUser): void
    {
        if ($groupUser->isDirty('level')) {
            $oldLevel = GroupUserLevel::from($groupUser->getOriginal('level'));
            GroupUserUpdated::dispatch($groupUser, $oldLevel);
        }
    }

    public function deleted(GroupUser $groupUser): void
    {
        GroupUserRemoved::dispatch($groupUser);
    }
}
```

- [ ] **Step 5: Refactor `GroupObserver`**

Replace entire contents of `app/Observers/GroupObserver.php`:

```php
<?php

namespace App\Observers;

use App\Events\GroupCreated;
use App\Events\GroupDeleted;
use App\Events\GroupUpdated;
use App\Models\Group;

class GroupObserver
{
    public function created(Group $group): void
    {
        GroupCreated::dispatch($group);
    }

    public function updated(Group $group): void
    {
        $changedFields = array_keys($group->getDirty());
        $relevantChanges = array_intersect($changedFields, ['nextcloud_folder_name', 'name']);

        if (! empty($relevantChanges)) {
            GroupUpdated::dispatch($group, $group->getOriginal(), $relevantChanges);
        }
    }

    public function deleted(Group $group): void
    {
        GroupDeleted::dispatch($group->hashid, $group->id);
    }
}
```

- [ ] **Step 6: Update `EventServiceProvider`**

Add the new event-listener mappings to the `$listen` array in `app/Providers/EventServiceProvider.php`. Add these imports and entries:

```php
// Add to imports:
use App\Events\GroupCreated;
use App\Events\GroupDeleted;
use App\Events\GroupUpdated;
use App\Events\GroupUserAdded;
use App\Events\GroupUserRemoved;
use App\Events\GroupUserUpdated;
use App\Listeners\AssignGroupOwner;
use App\Listeners\CheckStaffGroupMembership;
use App\Listeners\Nextcloud\AddUserToNextcloudGroup;
use App\Listeners\Nextcloud\CreateNextcloudGroup;
use App\Listeners\Nextcloud\DeleteNextcloudGroup;
use App\Listeners\Nextcloud\RemoveUserFromNextcloudGroup;
use App\Listeners\Nextcloud\UpdateNextcloudGroup;
use App\Listeners\Nextcloud\UpdateUserNextcloudGroupLevel;

// Add to $listen array:
GroupUserAdded::class => [
    CheckStaffGroupMembership::class,
    AddUserToNextcloudGroup::class,
],
GroupUserUpdated::class => [
    UpdateUserNextcloudGroupLevel::class,
],
GroupUserRemoved::class => [
    CheckStaffGroupMembership::class,
    RemoveUserFromNextcloudGroup::class,
],
GroupCreated::class => [
    AssignGroupOwner::class,
    CreateNextcloudGroup::class,
],
GroupUpdated::class => [
    UpdateNextcloudGroup::class,
],
GroupDeleted::class => [
    DeleteNextcloudGroup::class,
],
```

- [ ] **Step 7: Delete `CheckStaffGroupMembershipJob`**

```bash
rm app/Jobs/CheckStaffGroupMembershipJob.php
```

- [ ] **Step 8: Run observer tests**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Observers/
```

Expected: All tests PASS.

- [ ] **Step 9: Run all listener tests**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Listeners/
```

Expected: All tests PASS.

- [ ] **Step 10: Run pint and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add app/Observers/ app/Providers/EventServiceProvider.php tests/Feature/Observers/
git rm app/Jobs/CheckStaffGroupMembershipJob.php app/Events/GroupUserAddedEvent.php app/Events/GroupUserRemovedEvent.php
git commit -m "Refactor observers to dispatch events, wire up listeners"
```

---

## Task 6: Nextcloud Job Tests with Http::fake()

**Files:**
- Create: `tests/Feature/Jobs/Nextcloud/AddUserToGroupJobTest.php`
- Create: `tests/Feature/Jobs/Nextcloud/RemoveUserFromGroupJobTest.php`
- Create: `tests/Feature/Jobs/Nextcloud/UpdateUserGroupLevelJobTest.php`
- Create: `tests/Feature/Jobs/Nextcloud/CreateGroupJobTest.php`
- Create: `tests/Feature/Jobs/Nextcloud/UpdateGroupJobTest.php`
- Create: `tests/Feature/Jobs/Nextcloud/DeleteGroupJobTest.php`

Note: The Nextcloud API returns XML. Use `Http::fake()` with XML response strings. The `Http::nextcloud()` macro uses `config('services.nextcloud.baseUrl')` — set this in `.env.testing` or via `config()->set()` in tests.

- [ ] **Step 1: Create `AddUserToGroupJobTest`**

```php
<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Jobs\Nextcloud\AddUserToGroupJob;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.nextcloud.baseUrl', 'https://cloud.example.com');
    config()->set('services.nextcloud.username', 'admin');
    config()->set('services.nextcloud.password', 'secret');
});

it('adds user to nextcloud group', function () {
    Http::fake([
        '*/ocs/v2.php/cloud/users/*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
        '*/ocs/v2.php/cloud/users/*/groups' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test']);
    $user = User::factory()->create();

    $job = new AddUserToGroupJob($group, $user, GroupUserLevel::Member);
    $job->handle();

    Http::assertSent(function ($request) use ($user) {
        return str_contains($request->url(), "cloud/users/{$user->hashid}/groups");
    });
});

it('sets ACL for admin on non-team group', function () {
    Http::fake([
        '*/ocs/v2.php/cloud/users/*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
        '*/apps/groupfolders/*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test',
        'nextcloud_folder_id' => 1,
        'type' => GroupTypeEnum::Department,
    ]);
    $user = User::factory()->create();

    $job = new AddUserToGroupJob($group, $user, GroupUserLevel::Admin);
    $job->handle();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'manageACL');
    });
});

it('does not set ACL for member', function () {
    Http::fake([
        '*/ocs/v2.php/cloud/users/*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test',
        'nextcloud_folder_id' => 1,
        'type' => GroupTypeEnum::Department,
    ]);
    $user = User::factory()->create();

    $job = new AddUserToGroupJob($group, $user, GroupUserLevel::Member);
    $job->handle();

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), 'manageACL');
    });
});

it('does not set ACL for team group even if admin', function () {
    Http::fake([
        '*/ocs/v2.php/cloud/users/*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test',
        'nextcloud_folder_id' => 1,
        'type' => GroupTypeEnum::Team,
    ]);
    $user = User::factory()->create();

    $job = new AddUserToGroupJob($group, $user, GroupUserLevel::Admin);
    $job->handle();

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), 'manageACL');
    });
});

it('creates user if they do not exist in nextcloud', function () {
    Http::fake([
        '*/ocs/v2.php/cloud/users' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
        '*/ocs/v2.php/cloud/users/*/groups' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    // First call to check user returns 404, triggering createUser
    Http::fake(Http::sequence()
        ->push('<ocs><meta><statuscode>404</statuscode></meta></ocs>', 404) // checkUserExists
        ->push('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200) // createUser
        ->push('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200) // addUserToGroup
    );

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test']);
    $user = User::factory()->create();

    $job = new AddUserToGroupJob($group, $user, GroupUserLevel::Member);
    $job->handle();

    Http::assertSentCount(3);
});
```

- [ ] **Step 2: Create `RemoveUserFromGroupJobTest`**

```php
<?php

use App\Enums\GroupTypeEnum;
use App\Jobs\Nextcloud\RemoveUserFromGroupJob;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.nextcloud.baseUrl', 'https://cloud.example.com');
    config()->set('services.nextcloud.username', 'admin');
    config()->set('services.nextcloud.password', 'secret');
});

it('removes user from nextcloud group', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test',
        'nextcloud_folder_id' => 1,
    ]);
    $user = User::factory()->create();

    $job = new RemoveUserFromGroupJob($group, $user, GroupTypeEnum::Department);
    $job->handle();

    Http::assertSent(function ($request) use ($user) {
        return str_contains($request->url(), "cloud/users/{$user->hashid}/groups");
    });
});

it('removes ACL for non-team group', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test',
        'nextcloud_folder_id' => 1,
    ]);
    $user = User::factory()->create();

    $job = new RemoveUserFromGroupJob($group, $user, GroupTypeEnum::Department);
    $job->handle();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'manageACL');
    });
});

it('does not remove ACL for team group', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test',
        'nextcloud_folder_id' => 1,
    ]);
    $user = User::factory()->create();

    $job = new RemoveUserFromGroupJob($group, $user, GroupTypeEnum::Team);
    $job->handle();

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), 'manageACL');
    });
});
```

- [ ] **Step 3: Create `UpdateUserGroupLevelJobTest`**

```php
<?php

use App\Enums\GroupUserLevel;
use App\Jobs\Nextcloud\UpdateUserGroupLevelJob;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.nextcloud.baseUrl', 'https://cloud.example.com');
    config()->set('services.nextcloud.username', 'admin');
    config()->set('services.nextcloud.password', 'secret');
});

it('enables ACL management when promoted to admin', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test',
        'nextcloud_folder_id' => 1,
    ]);
    $user = User::factory()->create();

    $job = new UpdateUserGroupLevelJob($group, $user, GroupUserLevel::Admin, GroupUserLevel::Member);
    $job->handle();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'manageACL')
            && $request['manageAcl'] === '1';
    });
});

it('disables ACL management when demoted to member', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test',
        'nextcloud_folder_id' => 1,
    ]);
    $user = User::factory()->create();

    $job = new UpdateUserGroupLevelJob($group, $user, GroupUserLevel::Member, GroupUserLevel::Admin);
    $job->handle();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'manageACL')
            && $request['manageAcl'] === '0';
    });
});
```

- [ ] **Step 4: Create `CreateGroupJobTest`**

```php
<?php

use App\Enums\GroupTypeEnum;
use App\Jobs\Nextcloud\CreateGroupJob;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.nextcloud.baseUrl', 'https://cloud.example.com');
    config()->set('services.nextcloud.username', 'admin');
    config()->set('services.nextcloud.password', 'secret');
});

it('creates group and sets display name', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $group = Group::factory()->create();

    $job = new CreateGroupJob($group);
    $job->handle();

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), 'cloud/groups')
            && ($request['groupid'] ?? null) === $group->hashid;
    });
});

it('adds team group to parent folder', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $parent = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $group = Group::factory()->create([
        'type' => GroupTypeEnum::Team,
        'parent_id' => $parent->id,
    ]);

    $job = new CreateGroupJob($group, true, 42);
    $job->handle();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'groupfolders/folders/42/groups');
    });
});
```

- [ ] **Step 5: Create `UpdateGroupJobTest`**

```php
<?php

use App\Enums\GroupUserLevel;
use App\Jobs\Nextcloud\UpdateGroupJob;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.nextcloud.baseUrl', 'https://cloud.example.com');
    config()->set('services.nextcloud.username', 'admin');
    config()->set('services.nextcloud.password', 'secret');
});

it('renames folder when nextcloud_folder_name changes', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'New Name',
        'nextcloud_folder_id' => 5,
    ]);

    $job = new UpdateGroupJob($group, ['nextcloud_folder_name' => 'Old Name'], ['nextcloud_folder_name']);
    $job->handle();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'groupfolders/folders/5/mountpoint');
    });
});

it('creates folder and adds users when folder is new', function () {
    Http::fake([
        '*/ocs/v1.php/cloud/groups' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
        '*/apps/groupfolders/folders' => Http::response('<ocs><meta><statuscode>200</statuscode><data><id>10</id></data></meta></ocs>', 200),
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    // Need proper XML for createFolder
    Http::fake(fn ($request) => Http::response(
        str_contains($request->url(), 'groupfolders/folders') && $request->method() === 'POST' && ! str_contains($request->url(), '/')
            ? '<ocs><data><id>10</id></data></ocs>'
            : '<ocs><meta><statuscode>200</statuscode></meta></ocs>',
        200
    ));

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'New Folder',
        'nextcloud_folder_id' => null,
    ]);
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Admin]);

    $job = new UpdateGroupJob($group, [], ['nextcloud_folder_name']);
    $job->handle();

    // Folder ID should be saved
    expect($group->fresh()->nextcloud_folder_id)->not->toBeNull();
});
```

- [ ] **Step 6: Create `DeleteGroupJobTest`**

```php
<?php

use App\Jobs\Nextcloud\DeleteGroupJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.nextcloud.baseUrl', 'https://cloud.example.com');
    config()->set('services.nextcloud.username', 'admin');
    config()->set('services.nextcloud.password', 'secret');
});

it('deletes nextcloud group', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta></ocs>', 200),
    ]);

    $job = new DeleteGroupJob('abc123', 1);
    $job->handle();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'cloud/groups/abc123')
            && $request->method() === 'DELETE';
    });
});
```

- [ ] **Step 7: Run all job tests**

```bash
vendor/bin/sail artisan test --compact tests/Feature/Jobs/Nextcloud/
```

Expected: All tests PASS.

- [ ] **Step 8: Run pint and commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add tests/Feature/Jobs/Nextcloud/
git commit -m "Add Nextcloud job tests with Http::fake"
```

---

## Task 7: Full Test Suite Verification

- [ ] **Step 1: Run entire test suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: All existing tests continue to pass alongside new tests.

- [ ] **Step 2: Run pint on all changed files**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 3: Final commit if pint made changes**

```bash
git add -A && git commit -m "Fix formatting"
```

(Only if pint changed anything.)

- [ ] **Step 4: Verify no references to deleted files remain**

Search for any remaining references to `CheckStaffGroupMembershipJob`, `GroupUserAddedEvent`, or `GroupUserRemovedEvent`:

```bash
grep -r "CheckStaffGroupMembershipJob\|GroupUserAddedEvent\|GroupUserRemovedEvent" app/ tests/ --include="*.php"
```

Expected: No results.
