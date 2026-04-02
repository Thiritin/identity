# Event-Driven Group Integration Abstraction

## Problem

The group/user observers (`GroupObserver`, `GroupUserObserver`) are tightly coupled to Nextcloud. They contain environment checks, Nextcloud-specific conditionals, and direct job dispatches. This makes them hard to test and impossible to extend with new integrations (e.g., Dokuwiki) without modifying the observers.

## Solution

Refactor to an event-driven architecture where observers only dispatch domain events. Integrations (Nextcloud, staff membership, owner assignment) become listeners that independently react to those events.

## Events

All events live in `app/Events/`.

| Event | Fired by | Payload |
|---|---|---|
| `GroupUserAdded` | `GroupUserObserver::created` | `GroupUser $groupUser` |
| `GroupUserUpdated` | `GroupUserObserver::updated` | `GroupUser $groupUser`, `GroupUserLevel $oldLevel` |
| `GroupUserRemoved` | `GroupUserObserver::deleted` | `GroupUser $groupUser` |
| `GroupCreated` | `GroupObserver::created` | `Group $group` |
| `GroupUpdated` | `GroupObserver::updated` | `Group $group`, `array $originalData`, `array $changedFields` |
| `GroupDeleted` | `GroupObserver::deleted` | `string $groupHashid`, `int $groupId` |

Existing `GroupUserAddedEvent` and `GroupUserRemovedEvent` are replaced by the new events.

## Observers

Observers become thin event dispatchers with zero business logic.

### GroupUserObserver

```php
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
```

### GroupObserver

```php
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
```

## Listeners

All listeners live in `app/Listeners/`. Registered in `EventServiceProvider`.

### Nextcloud Listeners

All Nextcloud listeners include a `shouldHandle()` guard that returns `false` when `App::isLocal()` or `app()->runningUnitTests()`. They share this via a `Concerns\ChecksNextcloudEnvironment` trait.

| Listener | Event | Action |
|---|---|---|
| `AddUserToNextcloudGroup` | `GroupUserAdded` | Dispatches `AddUserToGroupJob` if group has nextcloud folder |
| `UpdateUserNextcloudGroupLevel` | `GroupUserUpdated` | Dispatches `UpdateUserGroupLevelJob` if group has nextcloud folder |
| `RemoveUserFromNextcloudGroup` | `GroupUserRemoved` | Dispatches `RemoveUserFromGroupJob` if group has nextcloud folder |
| `CreateNextcloudGroup` | `GroupCreated` | Dispatches `CreateGroupJob` for Team groups with parent nextcloud folder |
| `UpdateNextcloudGroup` | `GroupUpdated` | Dispatches `UpdateGroupJob` |
| `DeleteNextcloudGroup` | `GroupDeleted` | Dispatches `DeleteGroupJob` |

### Business Logic Listeners

| Listener | Event(s) | Action |
|---|---|---|
| `CheckStaffGroupMembership` | `GroupUserAdded`, `GroupUserRemoved` | Runs synchronously. If group is Department type: checks if user is in any department, syncs staff group membership accordingly. |
| `AssignGroupOwner` | `GroupCreated` | Attaches authenticated user as Owner. Skips if no auth user or if group is a Team group being dispatched to Nextcloud. |

### ChecksNextcloudEnvironment Trait

```php
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

## Jobs

The 6 Nextcloud jobs in `app/Jobs/Nextcloud/` remain unchanged. They are already clean and well-structured.

`CheckStaffGroupMembershipJob` is removed. Its logic is absorbed into the `CheckStaffGroupMembership` listener which runs synchronously (just an `exists()` check and `syncWithoutDetaching`/`detach`).

## EventServiceProvider Registration

```php
protected $listen = [
    // ... existing auth events ...

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
];
```

## Test Plan

### Observer Tests (`tests/Feature/Observers/`)

**GroupUserObserverTest:**
- Adding user to group dispatches `GroupUserAdded`
- Updating user level dispatches `GroupUserUpdated` with old level
- Updating non-level fields does not dispatch `GroupUserUpdated`
- Removing user from group dispatches `GroupUserRemoved`

**GroupObserverTest:**
- Creating group dispatches `GroupCreated`
- Updating group name dispatches `GroupUpdated`
- Updating group nextcloud_folder_name dispatches `GroupUpdated`
- Updating unrelated fields does not dispatch `GroupUpdated`
- Deleting group dispatches `GroupDeleted`

### Listener Tests (`tests/Feature/Listeners/`)

**CheckStaffGroupMembershipListenerTest:**
- User added to department group gets added to staff group
- User added to non-department group does not affect staff
- User removed from last department gets removed from staff
- User removed from one department but still in another stays in staff

**AssignGroupOwnerListenerTest:**
- Authenticated user becomes owner of new group
- No auth user: no owner assigned
- Team group with Nextcloud parent: owner not assigned (Nextcloud handles it)

**AddUserToNextcloudGroupListenerTest:**
- Dispatches `AddUserToGroupJob` when group has nextcloud folder
- Dispatches when parent has nextcloud folder
- Does not dispatch when no nextcloud folder
- Does not dispatch on local environment
- Does not dispatch during unit tests

**RemoveUserFromNextcloudGroupListenerTest:**
- Dispatches `RemoveUserFromGroupJob` when group has nextcloud folder
- Does not dispatch when no nextcloud folder
- Environment guard tests

**UpdateUserNextcloudGroupLevelListenerTest:**
- Dispatches `UpdateUserGroupLevelJob` when group has nextcloud folder
- Does not dispatch when no nextcloud folder
- Environment guard tests

**CreateNextcloudGroupListenerTest:**
- Dispatches `CreateGroupJob` for Team group with parent nextcloud folder
- Does not dispatch for non-Team groups
- Environment guard tests

**UpdateNextcloudGroupListenerTest:**
- Dispatches `UpdateGroupJob`
- Environment guard tests

**DeleteNextcloudGroupListenerTest:**
- Dispatches `DeleteGroupJob`
- Environment guard tests

### Job Tests (`tests/Feature/Jobs/Nextcloud/`)

All use `Http::fake()` to verify correct Nextcloud API calls.

**AddUserToGroupJobTest:**
- Calls correct user add endpoint
- Sets ACL for Admin/Owner on non-Team groups
- Does not set ACL for Member/Moderator
- Does not set ACL for Team groups
- Creates user first if they don't exist in Nextcloud

**RemoveUserFromGroupJobTest:**
- Calls correct user remove endpoint
- Removes ACL for non-Team groups
- Does not remove ACL for Team groups

**UpdateUserGroupLevelJobTest:**
- Calls setManageAcl with correct allow/deny based on new level

**CreateGroupJobTest:**
- Creates group and sets display name
- For Team groups: adds to parent folder with combined name

**UpdateGroupJobTest:**
- Renames folder when nextcloud_folder_name changes
- Creates folder + group + adds users when folder is new
- Updates display name when name changes

**DeleteGroupJobTest:**
- Calls delete group endpoint

## Files Changed

### New files
- `app/Events/GroupUserAdded.php`
- `app/Events/GroupUserUpdated.php`
- `app/Events/GroupUserRemoved.php`
- `app/Events/GroupCreated.php`
- `app/Events/GroupUpdated.php`
- `app/Events/GroupDeleted.php`
- `app/Listeners/CheckStaffGroupMembership.php`
- `app/Listeners/AssignGroupOwner.php`
- `app/Listeners/Nextcloud/AddUserToNextcloudGroup.php`
- `app/Listeners/Nextcloud/UpdateUserNextcloudGroupLevel.php`
- `app/Listeners/Nextcloud/RemoveUserFromNextcloudGroup.php`
- `app/Listeners/Nextcloud/CreateNextcloudGroup.php`
- `app/Listeners/Nextcloud/UpdateNextcloudGroup.php`
- `app/Listeners/Nextcloud/DeleteNextcloudGroup.php`
- `app/Listeners/Concerns/ChecksNextcloudEnvironment.php`
- `tests/Feature/Observers/GroupUserObserverTest.php`
- `tests/Feature/Observers/GroupObserverTest.php`
- `tests/Feature/Listeners/CheckStaffGroupMembershipListenerTest.php`
- `tests/Feature/Listeners/AssignGroupOwnerListenerTest.php`
- `tests/Feature/Listeners/Nextcloud/AddUserToNextcloudGroupListenerTest.php`
- `tests/Feature/Listeners/Nextcloud/RemoveUserFromNextcloudGroupListenerTest.php`
- `tests/Feature/Listeners/Nextcloud/UpdateUserNextcloudGroupLevelListenerTest.php`
- `tests/Feature/Listeners/Nextcloud/CreateNextcloudGroupListenerTest.php`
- `tests/Feature/Listeners/Nextcloud/UpdateNextcloudGroupListenerTest.php`
- `tests/Feature/Listeners/Nextcloud/DeleteNextcloudGroupListenerTest.php`
- `tests/Feature/Jobs/Nextcloud/AddUserToGroupJobTest.php`
- `tests/Feature/Jobs/Nextcloud/RemoveUserFromGroupJobTest.php`
- `tests/Feature/Jobs/Nextcloud/UpdateUserGroupLevelJobTest.php`
- `tests/Feature/Jobs/Nextcloud/CreateGroupJobTest.php`
- `tests/Feature/Jobs/Nextcloud/UpdateGroupJobTest.php`
- `tests/Feature/Jobs/Nextcloud/DeleteGroupJobTest.php`

### Modified files
- `app/Observers/GroupUserObserver.php` — Simplified to only dispatch events
- `app/Observers/GroupObserver.php` — Simplified to only dispatch events
- `app/Providers/EventServiceProvider.php` — Register new events and listeners

### Deleted files
- `app/Events/GroupUserAddedEvent.php` — Replaced by `GroupUserAdded`
- `app/Events/GroupUserRemovedEvent.php` — Replaced by `GroupUserRemoved`
- `app/Jobs/CheckStaffGroupMembershipJob.php` — Logic moved to listener
