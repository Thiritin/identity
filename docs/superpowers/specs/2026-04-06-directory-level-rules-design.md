# Directory Member Level Rules

## Problem

The directory currently allows any `GroupUserLevel` to be assigned to any group regardless of group type. There are no group-type-aware restrictions on which levels are valid, who may assign them, or who may create child groups. The rules are partially enforced inline in form requests and duplicated across controllers.

## Decisions

- Rules apply only to `Division`, `Department`, `Team` group types. `Default`, `Automated`, `Root` keep current unrestricted behavior.
- No data migration needed — the system is being freshly migrated to these rules.
- Leaderless-group protection is out of scope (follow-up).
- A dedicated `DirectoryAuthorizer` service centralizes all directory-specific authorization. Policies remain for top-level `view`/`update` gates.

## Rules

### Group-type level restrictions

| Group type  | Allowed levels          |
|-------------|-------------------------|
| Division    | DivisionDirector        |
| Department  | Director, Member        |
| Team        | TeamLead, Member        |

Division groups have no plain Members. Department and Team groups always include Member as a valid level.

### Assignment hierarchy

Lead roles only assign *down into child groups*. Peer-level assignment (e.g., DD adding another DD) is admin/HR-only.

| Viewer role      | Can assign                                    |
|------------------|-----------------------------------------------|
| Admin / HR       | Any level allowed by the group's type          |
| DivisionDirector | Director, Member (in child Departments)        |
| Director         | TeamLead, Member (in child Teams)              |
| TeamLead         | Member (in own Team)                           |
| Member           | Nothing                                        |

Parent-group leads also have authority: a DD on a Division can manage members in Departments under that Division. A Director on a Department can manage members in Teams under that Department.

### Delegation via `can_manage_members`

The `can_manage_members` pivot flag only has meaning on Department and Team groups (Divisions only contain DivisionDirectors who already have full powers).

A plain Member with `can_manage_members = true` inherits the full powers of the group's top lead level:
- On a Department: acts as Director (can assign TeamLead/Member in child Teams, can create Teams).
- On a Team: acts as TeamLead (can assign Member in that Team).

### `is_hr` user flag

New boolean column `users.is_hr`, default `false`. HR users have the same directory powers as admins — unrestricted assignment and group creation, subject only to group-type level restrictions.

### Group creation

| Viewer role (effective)       | Can create                  |
|-------------------------------|-----------------------------|
| Admin / HR                    | Any child group type        |
| DivisionDirector              | Department under Division   |
| Director (or delegate)        | Team under Department       |

Newly created groups are empty — the creator does not automatically become a lead of the new group. Someone must explicitly add a lead afterwards.

### Member management

Who can add/remove plain Members from a group:
- Admin / HR
- The lead of that group (Director in a Department, TeamLead in a Team)
- The lead of the parent group (DD can manage members in child Departments)
- A delegate (`can_manage_members`) in that group or parent group

## Architecture

### `GroupTypeEnum` additions

Three new methods on the existing enum:

```php
public function allowedLevels(): array
{
    return match ($this) {
        self::Division   => [GroupUserLevel::DivisionDirector],
        self::Department => [GroupUserLevel::Director, GroupUserLevel::Member],
        self::Team       => [GroupUserLevel::TeamLead, GroupUserLevel::Member],
        default          => [], // unrestricted
    };
}

public function childGroupType(): ?self
{
    return match ($this) {
        self::Division   => self::Department,
        self::Department => self::Team,
        default          => null,
    };
}

public function topLeadLevel(): ?GroupUserLevel
{
    return match ($this) {
        self::Division   => GroupUserLevel::DivisionDirector,
        self::Department => GroupUserLevel::Director,
        self::Team       => GroupUserLevel::TeamLead,
        default          => null,
    };
}
```

### `DirectoryAuthorizer` service

New: `app/Support/Directory/DirectoryAuthorizer.php`. Stateless, pure functions.

```
hasGlobalPowers(User): bool
    → is_admin || is_hr

effectiveLevel(User, Group): ?GroupUserLevel
    → Resolves the viewer's pivot level in the group.
    → If pivot level is a lead role, return it.
    → If pivot level is Member with can_manage_members=true and group is
      Department or Team, return group->type->topLeadLevel().
    → Otherwise null.

assignableLevels(User, Group): GroupUserLevel[]
    → Admin/HR: return group->type->allowedLevels() (or all levels if empty).
    → Otherwise: union of effectiveLevel(viewer, group)->assignableLevels()
      and effectiveLevel(viewer, parent)->assignableLevels(),
      intersected with group->type->allowedLevels().

canManageMembers(User, Group): bool
    → Admin/HR: true.
    → effectiveLevel(viewer, group) !== null: true.
    → effectiveLevel(viewer, group->parent) !== null: true.
    → Otherwise false.

canCreateChildGroup(User, Group): bool
    → group->type->childGroupType() must be non-null.
    → Admin/HR: true.
    → effectiveLevel(viewer, group) !== null: true.
    → Otherwise false.

levelAllowedByType(GroupUserLevel, Group): bool
    → If group->type->allowedLevels() is empty: true (unrestricted).
    → Otherwise: level is in the allowed list.
```

### Form requests

**`StoreMemberRequest`** — authorization via `canManageMembers()`. Validates `user_hashid`, `level` (constrained to `assignableLevels()`), `title`, `can_manage_members`.

**`UpdateMemberRequest`** — authorization via `canManageMembers()`. Same level validation. Inline `validateLevelAssignment()` and `getViewerHighestLevel()` methods deleted.

**`StoreTeamRequest`** — authorization changed from `can('update', $group)` to `canCreateChildGroup()`.

**`StoreDepartmentRequest`** (new) — authorization via `canCreateChildGroup()`. Validates `name`, `description`.

### Controllers

**`DirectoryMemberController::store`** — attaches with the validated `level`, `title`, and `can_manage_members` instead of hard-coded `GroupUserLevel::Member`.

**`DirectoryMemberController::destroy`** — uses `canManageMembers()` instead of `authorize('update', $group)`.

**`DirectoryDepartmentController`** (new) — mirrors `DirectoryTeamController`. Creates a `GroupTypeEnum::Department` child group.

**`DirectoryController::show`** — deletes private `getAssignableLevels()`, calls `DirectoryAuthorizer::assignableLevels()`. Passes new Inertia props: `canCreateChildGroup`, `childGroupType`.

**`StaffProfileController`** — deletes private `getAssignableLevels()`, calls the authorizer.

**Routes** — new `POST` route for department creation under a group, mirroring the existing team route.

### Frontend

**`MemberAddModal.vue`** — new props: `assignableLevels`, `groupType`. Adds level select, title input, and `can_manage_members` checkbox (only shown for department/team). Form submits all fields.

**`MemberEditModal.vue`** — new prop: `groupType`. `can_manage_members` checkbox only shown for department/team. Level select already present and driven by `assignableLevels`.

**`TeamCreateModal.vue`** — renamed to `SubGroupCreateModal.vue`. Used for both department and team creation. Same fields (name, description). Route determined by `childGroupType` prop.

**`DirectoryShow.vue`** — shows "Create Department" or "Create Team" button based on `canCreateChildGroup` and `childGroupType` props.

## Files changed

| Action  | Path                                                            |
|---------|-----------------------------------------------------------------|
| Migrate | `database/migrations/..._add_is_hr_to_users_table.php`         |
| Edit    | `app/Models/User.php`                                           |
| Edit    | `app/Enums/GroupTypeEnum.php`                                   |
| Create  | `app/Support/Directory/DirectoryAuthorizer.php`                 |
| Edit    | `app/Http/Requests/Directory/StoreMemberRequest.php`            |
| Edit    | `app/Http/Requests/Directory/UpdateMemberRequest.php`           |
| Edit    | `app/Http/Requests/Directory/StoreTeamRequest.php`              |
| Create  | `app/Http/Requests/Directory/StoreDepartmentRequest.php`        |
| Edit    | `app/Http/Controllers/Directory/DirectoryMemberController.php`  |
| Create  | `app/Http/Controllers/Directory/DirectoryDepartmentController.php` |
| Edit    | `app/Http/Controllers/Directory/DirectoryController.php`        |
| Edit    | `app/Http/Controllers/Directory/StaffProfileController.php`     |
| Edit    | `routes/web.php`                                                |
| Rename  | `SubGroupCreateModal.vue` (was `TeamCreateModal.vue`)           |
| Edit    | `MemberAddModal.vue`                                            |
| Edit    | `MemberEditModal.vue`                                           |
| Edit    | `DirectoryShow.vue`                                             |
| Create  | `tests/Unit/Support/DirectoryAuthorizerTest.php`                |
| Create  | `tests/Unit/Enums/GroupTypeEnumTest.php`                        |
| Create  | `tests/Feature/Directory/DirectoryMemberTest.php`               |
| Create  | `tests/Feature/Directory/DirectoryGroupCreationTest.php`        |

## Testing strategy

**Unit tests** — `DirectoryAuthorizerTest` covers each public method with table-driven cases: admin/HR overrides, lead-in-group, lead-in-parent, delegate, unrelated user. `GroupTypeEnumTest` covers the three new enum methods.

**Feature tests** — `DirectoryMemberTest` covers store/update with valid and invalid levels per group type, delegation, HR access. `DirectoryGroupCreationTest` covers department creation by DD, team creation by Director/delegate, denial for insufficient roles.
