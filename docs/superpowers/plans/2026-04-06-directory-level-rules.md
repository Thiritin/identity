# Directory Level Rules Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enforce group-type-aware member level restrictions in the directory, centralize authorization in a DirectoryAuthorizer service, add HR flag, and enable department creation by DivisionDirectors.

**Architecture:** A stateless `DirectoryAuthorizer` service replaces scattered inline authorization logic. `GroupTypeEnum` gains methods to define which levels and child types each group type permits. Form requests and controllers become thin wrappers around the authorizer. Frontend modals receive filtered level lists from Inertia props.

**Tech Stack:** Laravel 10, PHP 8.2 enums, Vue 3 Composition API, Inertia.js, Pest test framework.

**Spec:** `docs/superpowers/specs/2026-04-06-directory-level-rules-design.md`

---

## File Map

| Action  | Path                                                            | Responsibility                                                   |
|---------|-----------------------------------------------------------------|------------------------------------------------------------------|
| Migrate | `database/migrations/2026_04_06_000001_add_is_hr_to_users.php`  | Add `is_hr` boolean column                                      |
| Edit    | `app/Models/User.php`                                           | Cast `is_hr` as boolean                                          |
| Edit    | `app/Enums/GroupTypeEnum.php`                                   | `allowedLevels()`, `childGroupType()`, `topLeadLevel()`          |
| Create  | `app/Support/Directory/DirectoryAuthorizer.php`                 | Central directory authorization logic                            |
| Edit    | `app/Http/Requests/Directory/StoreMemberRequest.php`            | Use authorizer for auth + validate level                         |
| Edit    | `app/Http/Requests/Directory/UpdateMemberRequest.php`           | Use authorizer, delete inline helpers                            |
| Edit    | `app/Http/Requests/Directory/StoreTeamRequest.php`              | Use `canCreateChildGroup()`                                      |
| Create  | `app/Http/Requests/Directory/StoreDepartmentRequest.php`        | Authorize + validate department creation                         |
| Edit    | `app/Http/Controllers/Directory/DirectoryMemberController.php`  | Attach with validated level/title/can_manage_members             |
| Create  | `app/Http/Controllers/Directory/DirectoryDepartmentController.php` | Mirror DirectoryTeamController for departments                |
| Edit    | `app/Http/Controllers/Directory/DirectoryController.php`        | Delete `getAssignableLevels()`, use authorizer, add new props    |
| Edit    | `app/Http/Controllers/Directory/StaffProfileController.php`     | Delete `getAssignableLevels()`, use authorizer                   |
| Edit    | `routes/apps/portal.php`                                        | Add department store route                                       |
| Rename  | `resources/js/Pages/Directory/Components/SubGroupCreateModal.vue` | Was `TeamCreateModal.vue`; now used for both dept + team       |
| Edit    | `resources/js/Pages/Directory/Components/MemberAddModal.vue`    | Add level select, title, can_manage_members                      |
| Edit    | `resources/js/Pages/Directory/Components/MemberEditModal.vue`   | Conditionally show can_manage_members by group type              |
| Edit    | `resources/js/Pages/Directory/DirectoryShow.vue`                | Wire new props, dynamic create-child button                      |
| Create  | `tests/Unit/Enums/GroupTypeEnumTest.php`                        | Test new enum methods                                            |
| Create  | `tests/Unit/Support/DirectoryAuthorizerTest.php`                | Test all authorizer methods                                      |
| Create  | `tests/Feature/Directory/DirectoryLevelRulesTest.php`           | Feature tests for level rules + group creation                   |

---

## Task 1: Migration — add `is_hr` to users

**Files:**
- Create: `database/migrations/2026_04_06_000001_add_is_hr_to_users.php`
- Modify: `app/Models/User.php:110-125` (casts array)

- [ ] **Step 1: Create migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_hr')->default(false)->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_hr');
        });
    }
};
```

- [ ] **Step 2: Add cast to User model**

In `app/Models/User.php`, add `'is_hr' => 'boolean'` to the `$casts` array, right after the `'is_admin' => 'boolean'` line.

- [ ] **Step 3: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_06_000001_add_is_hr_to_users.php app/Models/User.php
git commit -m "feat: add is_hr column to users table"
```

---

## Task 2: GroupTypeEnum — add `allowedLevels()`, `childGroupType()`, `topLeadLevel()`

**Files:**
- Modify: `app/Enums/GroupTypeEnum.php`
- Create: `tests/Unit/Enums/GroupTypeEnumTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Unit/Enums/GroupTypeEnumTest.php`:

```php
<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;

test('division allows only DivisionDirector', function () {
    expect(GroupTypeEnum::Division->allowedLevels())
        ->toBe([GroupUserLevel::DivisionDirector]);
});

test('department allows Director and Member', function () {
    expect(GroupTypeEnum::Department->allowedLevels())
        ->toBe([GroupUserLevel::Director, GroupUserLevel::Member]);
});

test('team allows TeamLead and Member', function () {
    expect(GroupTypeEnum::Team->allowedLevels())
        ->toBe([GroupUserLevel::TeamLead, GroupUserLevel::Member]);
});

test('default types return empty allowedLevels (unrestricted)', function () {
    expect(GroupTypeEnum::Default->allowedLevels())->toBe([]);
    expect(GroupTypeEnum::Automated->allowedLevels())->toBe([]);
    expect(GroupTypeEnum::Root->allowedLevels())->toBe([]);
});

test('division child type is department', function () {
    expect(GroupTypeEnum::Division->childGroupType())->toBe(GroupTypeEnum::Department);
});

test('department child type is team', function () {
    expect(GroupTypeEnum::Department->childGroupType())->toBe(GroupTypeEnum::Team);
});

test('team has no child type', function () {
    expect(GroupTypeEnum::Team->childGroupType())->toBeNull();
});

test('default types have no child type', function () {
    expect(GroupTypeEnum::Default->childGroupType())->toBeNull();
    expect(GroupTypeEnum::Automated->childGroupType())->toBeNull();
    expect(GroupTypeEnum::Root->childGroupType())->toBeNull();
});

test('topLeadLevel returns correct level per type', function () {
    expect(GroupTypeEnum::Division->topLeadLevel())->toBe(GroupUserLevel::DivisionDirector);
    expect(GroupTypeEnum::Department->topLeadLevel())->toBe(GroupUserLevel::Director);
    expect(GroupTypeEnum::Team->topLeadLevel())->toBe(GroupUserLevel::TeamLead);
});

test('default types have no topLeadLevel', function () {
    expect(GroupTypeEnum::Default->topLeadLevel())->toBeNull();
    expect(GroupTypeEnum::Automated->topLeadLevel())->toBeNull();
    expect(GroupTypeEnum::Root->topLeadLevel())->toBeNull();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Enums/GroupTypeEnumTest.php`
Expected: All tests FAIL (methods don't exist).

- [ ] **Step 3: Implement the three methods**

Add to `app/Enums/GroupTypeEnum.php`:

```php
use App\Enums\GroupUserLevel;

// ... existing cases ...

public function allowedLevels(): array
{
    return match ($this) {
        self::Division   => [GroupUserLevel::DivisionDirector],
        self::Department => [GroupUserLevel::Director, GroupUserLevel::Member],
        self::Team       => [GroupUserLevel::TeamLead, GroupUserLevel::Member],
        default          => [],
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

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Unit/Enums/GroupTypeEnumTest.php`
Expected: All tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Enums/GroupTypeEnum.php tests/Unit/Enums/GroupTypeEnumTest.php
git commit -m "feat: add allowedLevels, childGroupType, topLeadLevel to GroupTypeEnum"
```

---

## Task 3: DirectoryAuthorizer — core service

**Files:**
- Create: `app/Support/Directory/DirectoryAuthorizer.php`
- Create: `tests/Unit/Support/DirectoryAuthorizerTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Unit/Support/DirectoryAuthorizerTest.php`:

```php
<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use App\Support\Directory\DirectoryAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authorizer(): DirectoryAuthorizer
{
    return app(DirectoryAuthorizer::class);
}

function createHierarchy(): array
{
    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $division = Group::factory()->division()->create(['parent_id' => $root->id]);
    $department = Group::factory()->department()->create(['parent_id' => $division->id]);
    $team = Group::factory()->team()->create(['parent_id' => $department->id]);

    return [$root, $division, $department, $team];
}

// --- hasGlobalPowers ---

test('admin has global powers', function () {
    $user = User::factory()->create(['is_admin' => true]);
    expect(authorizer()->hasGlobalPowers($user))->toBeTrue();
});

test('hr has global powers', function () {
    $user = User::factory()->create(['is_hr' => true]);
    expect(authorizer()->hasGlobalPowers($user))->toBeTrue();
});

test('regular user does not have global powers', function () {
    $user = User::factory()->create(['is_admin' => false, 'is_hr' => false]);
    expect(authorizer()->hasGlobalPowers($user))->toBeFalse();
});

// --- effectiveLevel ---

test('effectiveLevel returns lead role directly', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();
    $department->users()->attach($user, ['level' => GroupUserLevel::Director]);

    expect(authorizer()->effectiveLevel($user, $department))->toBe(GroupUserLevel::Director);
});

test('effectiveLevel returns topLeadLevel for delegate member', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();
    $department->users()->attach($user, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);

    expect(authorizer()->effectiveLevel($user, $department))->toBe(GroupUserLevel::Director);
});

test('effectiveLevel returns null for plain member without delegation', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();
    $department->users()->attach($user, ['level' => GroupUserLevel::Member, 'can_manage_members' => false]);

    expect(authorizer()->effectiveLevel($user, $department))->toBeNull();
});

test('effectiveLevel returns null for non-member', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();

    expect(authorizer()->effectiveLevel($user, $department))->toBeNull();
});

test('effectiveLevel ignores can_manage_members on division', function () {
    [, $division] = createHierarchy();
    $user = User::factory()->create();
    $division->users()->attach($user, ['level' => GroupUserLevel::DivisionDirector, 'can_manage_members' => true]);

    // DivisionDirector is already a lead role — returned as-is
    expect(authorizer()->effectiveLevel($user, $division))->toBe(GroupUserLevel::DivisionDirector);
});

// --- assignableLevels ---

test('admin gets all levels allowed by group type', function () {
    [, , $department] = createHierarchy();
    $admin = User::factory()->create(['is_admin' => true]);

    $levels = authorizer()->assignableLevels($admin, $department);
    expect($levels)->toEqualCanonicalizing([GroupUserLevel::Director, GroupUserLevel::Member]);
});

test('hr gets all levels allowed by group type', function () {
    [, $division] = createHierarchy();
    $hr = User::factory()->create(['is_hr' => true]);

    $levels = authorizer()->assignableLevels($hr, $division);
    expect($levels)->toBe([GroupUserLevel::DivisionDirector]);
});

test('DD in division can assign Director and Member in child department', function () {
    [, $division, $department] = createHierarchy();
    $dd = User::factory()->create();
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);

    $levels = authorizer()->assignableLevels($dd, $department);
    expect($levels)->toEqualCanonicalizing([GroupUserLevel::Director, GroupUserLevel::Member]);
});

test('Director in department can assign TeamLead and Member in child team', function () {
    [, , $department, $team] = createHierarchy();
    $director = User::factory()->create();
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);

    $levels = authorizer()->assignableLevels($director, $team);
    expect($levels)->toEqualCanonicalizing([GroupUserLevel::TeamLead, GroupUserLevel::Member]);
});

test('TeamLead in team can assign Member in own team', function () {
    [, , , $team] = createHierarchy();
    $lead = User::factory()->create();
    $team->users()->attach($lead, ['level' => GroupUserLevel::TeamLead]);

    $levels = authorizer()->assignableLevels($lead, $team);
    expect($levels)->toBe([GroupUserLevel::Member]);
});

test('delegate member in department gets Director assignable levels', function () {
    [, , $department, $team] = createHierarchy();
    $delegate = User::factory()->create();
    $department->users()->attach($delegate, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);

    $levels = authorizer()->assignableLevels($delegate, $team);
    expect($levels)->toEqualCanonicalizing([GroupUserLevel::TeamLead, GroupUserLevel::Member]);
});

test('unrelated user gets empty assignable levels', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();

    expect(authorizer()->assignableLevels($user, $department))->toBe([]);
});

test('DD cannot assign peer DD in own division', function () {
    [, $division] = createHierarchy();
    $dd = User::factory()->create();
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);

    // DD's assignableLevels = [Director, TeamLead, Member] ∩ Division's allowedLevels = [DivisionDirector] → empty
    expect(authorizer()->assignableLevels($dd, $division))->toBe([]);
});

// --- canManageMembers ---

test('admin can manage members anywhere', function () {
    [, , $department] = createHierarchy();
    $admin = User::factory()->create(['is_admin' => true]);
    expect(authorizer()->canManageMembers($admin, $department))->toBeTrue();
});

test('group lead can manage members', function () {
    [, , $department] = createHierarchy();
    $director = User::factory()->create();
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);
    expect(authorizer()->canManageMembers($director, $department))->toBeTrue();
});

test('parent lead can manage members in child', function () {
    [, $division, $department] = createHierarchy();
    $dd = User::factory()->create();
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);
    expect(authorizer()->canManageMembers($dd, $department))->toBeTrue();
});

test('delegate can manage members', function () {
    [, , $department] = createHierarchy();
    $delegate = User::factory()->create();
    $department->users()->attach($delegate, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);
    expect(authorizer()->canManageMembers($delegate, $department))->toBeTrue();
});

test('plain member cannot manage members', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();
    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    expect(authorizer()->canManageMembers($user, $department))->toBeFalse();
});

// --- canCreateChildGroup ---

test('DD can create department under division', function () {
    [, $division] = createHierarchy();
    $dd = User::factory()->create();
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);
    expect(authorizer()->canCreateChildGroup($dd, $division))->toBeTrue();
});

test('Director can create team under department', function () {
    [, , $department] = createHierarchy();
    $director = User::factory()->create();
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);
    expect(authorizer()->canCreateChildGroup($director, $department))->toBeTrue();
});

test('cannot create child under team (no child type)', function () {
    [, , , $team] = createHierarchy();
    $admin = User::factory()->create(['is_admin' => true]);
    expect(authorizer()->canCreateChildGroup($admin, $team))->toBeFalse();
});

test('delegate can create child group', function () {
    [, , $department] = createHierarchy();
    $delegate = User::factory()->create();
    $department->users()->attach($delegate, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);
    expect(authorizer()->canCreateChildGroup($delegate, $department))->toBeTrue();
});

test('plain member cannot create child group', function () {
    [, , $department] = createHierarchy();
    $user = User::factory()->create();
    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    expect(authorizer()->canCreateChildGroup($user, $department))->toBeFalse();
});

// --- levelAllowedByType ---

test('DivisionDirector allowed on division', function () {
    [, $division] = createHierarchy();
    expect(authorizer()->levelAllowedByType(GroupUserLevel::DivisionDirector, $division))->toBeTrue();
});

test('Member not allowed on division', function () {
    [, $division] = createHierarchy();
    expect(authorizer()->levelAllowedByType(GroupUserLevel::Member, $division))->toBeFalse();
});

test('any level allowed on unrestricted type', function () {
    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    expect(authorizer()->levelAllowedByType(GroupUserLevel::DivisionDirector, $root))->toBeTrue();
    expect(authorizer()->levelAllowedByType(GroupUserLevel::Member, $root))->toBeTrue();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Support/DirectoryAuthorizerTest.php`
Expected: FAIL — class does not exist.

- [ ] **Step 3: Implement DirectoryAuthorizer**

Create `app/Support/Directory/DirectoryAuthorizer.php`:

```php
<?php

namespace App\Support\Directory;

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;

final class DirectoryAuthorizer
{
    public function hasGlobalPowers(User $viewer): bool
    {
        return $viewer->is_admin || $viewer->is_hr;
    }

    public function effectiveLevel(User $viewer, Group $group): ?GroupUserLevel
    {
        $membership = $viewer->groups()->where('groups.id', $group->id)->first();

        if (! $membership) {
            return null;
        }

        $level = $membership->pivot->level instanceof GroupUserLevel
            ? $membership->pivot->level
            : GroupUserLevel::from($membership->pivot->level);

        if ($level->isLeadRole()) {
            return $level;
        }

        if ($membership->pivot->can_manage_members
            && $group->type !== \App\Enums\GroupTypeEnum::Division
            && $group->type->topLeadLevel()) {
            return $group->type->topLeadLevel();
        }

        return null;
    }

    public function assignableLevels(User $viewer, Group $group): array
    {
        $typeAllowed = $group->type->allowedLevels();

        if ($this->hasGlobalPowers($viewer)) {
            return $typeAllowed ?: GroupUserLevel::cases();
        }

        $levels = collect();

        $selfLevel = $this->effectiveLevel($viewer, $group);
        if ($selfLevel) {
            $levels = $levels->merge($selfLevel->assignableLevels());
        }

        if ($group->parent_id) {
            $group->loadMissing('parent');
            $parentLevel = $this->effectiveLevel($viewer, $group->parent);
            if ($parentLevel) {
                $levels = $levels->merge($parentLevel->assignableLevels());
            }
        }

        if ($typeAllowed) {
            $levels = $levels->intersect($typeAllowed);
        }

        return $levels->unique()->values()->all();
    }

    public function canManageMembers(User $viewer, Group $group): bool
    {
        if ($this->hasGlobalPowers($viewer)) {
            return true;
        }

        if ($this->effectiveLevel($viewer, $group) !== null) {
            return true;
        }

        if ($group->parent_id) {
            $group->loadMissing('parent');

            return $this->effectiveLevel($viewer, $group->parent) !== null;
        }

        return false;
    }

    public function canCreateChildGroup(User $viewer, Group $parent): bool
    {
        if ($parent->type->childGroupType() === null) {
            return false;
        }

        if ($this->hasGlobalPowers($viewer)) {
            return true;
        }

        return $this->effectiveLevel($viewer, $parent) !== null;
    }

    public function levelAllowedByType(GroupUserLevel $level, Group $group): bool
    {
        $allowed = $group->type->allowedLevels();

        return $allowed === [] || in_array($level, $allowed, true);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Unit/Support/DirectoryAuthorizerTest.php`
Expected: All tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Support/Directory/DirectoryAuthorizer.php tests/Unit/Support/DirectoryAuthorizerTest.php
git commit -m "feat: add DirectoryAuthorizer service"
```

---

## Task 4: Update form requests to use DirectoryAuthorizer

**Files:**
- Modify: `app/Http/Requests/Directory/StoreMemberRequest.php`
- Modify: `app/Http/Requests/Directory/UpdateMemberRequest.php`
- Modify: `app/Http/Requests/Directory/StoreTeamRequest.php`
- Create: `app/Http/Requests/Directory/StoreDepartmentRequest.php`

- [ ] **Step 1: Rewrite StoreMemberRequest**

Replace full contents of `app/Http/Requests/Directory/StoreMemberRequest.php`:

```php
<?php

namespace App\Http\Requests\Directory;

use App\Enums\GroupUserLevel;
use App\Support\Directory\DirectoryAuthorizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(DirectoryAuthorizer::class)
            ->canManageMembers($this->user(), $this->route('group'));
    }

    public function rules(): array
    {
        $group = $this->route('group');
        $assignable = app(DirectoryAuthorizer::class)
            ->assignableLevels($this->user(), $group);

        return [
            'user_hashid' => 'required|string|exists:users,hashid',
            'level' => ['required', Rule::in(array_map(fn ($l) => $l->value, $assignable))],
            'title' => 'nullable|string|max:255',
            'can_manage_members' => 'boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Strip can_manage_members for division groups
        if ($this->route('group')?->type->allowedLevels() === [\App\Enums\GroupUserLevel::DivisionDirector]) {
            $this->merge(['can_manage_members' => false]);
        }
    }
}
```

- [ ] **Step 2: Rewrite UpdateMemberRequest**

Replace full contents of `app/Http/Requests/Directory/UpdateMemberRequest.php`:

```php
<?php

namespace App\Http\Requests\Directory;

use App\Enums\GroupTypeEnum;
use App\Support\Directory\DirectoryAuthorizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(DirectoryAuthorizer::class)
            ->canManageMembers($this->user(), $this->route('group'));
    }

    public function rules(): array
    {
        $group = $this->route('group');
        $assignable = app(DirectoryAuthorizer::class)
            ->assignableLevels($this->user(), $group);

        return [
            'level' => ['required', Rule::in(array_map(fn ($l) => $l->value, $assignable))],
            'title' => 'nullable|string|max:255',
            'can_manage_members' => 'boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('group')?->type === GroupTypeEnum::Division) {
            $this->merge(['can_manage_members' => false]);
        }
    }
}
```

- [ ] **Step 3: Update StoreTeamRequest authorization**

Replace `app/Http/Requests/Directory/StoreTeamRequest.php`:

```php
<?php

namespace App\Http\Requests\Directory;

use App\Support\Directory\DirectoryAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(DirectoryAuthorizer::class)
            ->canCreateChildGroup($this->user(), $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
        ];
    }
}
```

- [ ] **Step 4: Create StoreDepartmentRequest**

Create `app/Http/Requests/Directory/StoreDepartmentRequest.php`:

```php
<?php

namespace App\Http\Requests\Directory;

use App\Support\Directory\DirectoryAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(DirectoryAuthorizer::class)
            ->canCreateChildGroup($this->user(), $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
        ];
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Requests/Directory/
git commit -m "feat: wire form requests to DirectoryAuthorizer"
```

---

## Task 5: Update controllers

**Files:**
- Modify: `app/Http/Controllers/Directory/DirectoryMemberController.php`
- Create: `app/Http/Controllers/Directory/DirectoryDepartmentController.php`
- Modify: `app/Http/Controllers/Directory/DirectoryController.php`
- Modify: `app/Http/Controllers/Directory/StaffProfileController.php`
- Modify: `routes/apps/portal.php`

- [ ] **Step 1: Update DirectoryMemberController::store to use validated level**

Replace the `store` method body in `app/Http/Controllers/Directory/DirectoryMemberController.php`:

```php
public function store(StoreMemberRequest $request, Group $group): RedirectResponse
{
    $user = User::where('hashid', $request->validated('user_hashid'))->firstOrFail();

    if ($group->users()->where('user_id', $user->id)->exists()) {
        return back()->withErrors(['user_hashid' => 'User is already a member of this group.']);
    }

    $group->users()->attach($user, [
        'level' => GroupUserLevel::from($request->validated('level')),
        'title' => $request->validated('title'),
        'can_manage_members' => $request->boolean('can_manage_members'),
    ]);

    return back();
}
```

- [ ] **Step 2: Update DirectoryMemberController::destroy to use authorizer**

Replace the `destroy` method:

```php
public function destroy(Group $group, User $user): RedirectResponse
{
    $authorizer = app(DirectoryAuthorizer::class);
    $viewer = request()->user();

    if (! $authorizer->canManageMembers($viewer, $group)) {
        abort(403);
    }

    // Viewers can only remove members whose level they could assign
    if (! $authorizer->hasGlobalPowers($viewer)) {
        $targetPivot = $group->users()->where('user_id', $user->id)->first()?->pivot;
        if ($targetPivot) {
            $targetLevel = $targetPivot->level instanceof GroupUserLevel
                ? $targetPivot->level
                : GroupUserLevel::from($targetPivot->level);
            $assignable = $authorizer->assignableLevels($viewer, $group);
            if (! in_array($targetLevel, $assignable, true)) {
                abort(403);
            }
        }
    }

    $group->users()->detach($user);

    return back();
}
```

Add `use App\Support\Directory\DirectoryAuthorizer;` and `use App\Enums\GroupUserLevel;` to the imports.

- [ ] **Step 3: Create DirectoryDepartmentController**

Create `app/Http/Controllers/Directory/DirectoryDepartmentController.php`:

```php
<?php

namespace App\Http\Controllers\Directory;

use App\Enums\GroupTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Directory\StoreDepartmentRequest;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;

class DirectoryDepartmentController extends Controller
{
    public function store(StoreDepartmentRequest $request, Group $group): RedirectResponse
    {
        Group::create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'type' => GroupTypeEnum::Department,
            'parent_id' => $group->id,
        ]);

        return back();
    }
}
```

- [ ] **Step 4: Add department route**

In `routes/apps/portal.php`, add the department route right after the team route (line with `directory.teams.store`):

```php
Route::post('/g/{group:hashid}/departments', [DirectoryDepartmentController::class, 'store'])->name('directory.departments.store');
```

Add the import at the top of the file:

```php
use App\Http\Controllers\Directory\DirectoryDepartmentController;
```

- [ ] **Step 5: Update DirectoryController — replace getAssignableLevels, add new props**

In `app/Http/Controllers/Directory/DirectoryController.php`:

Add import: `use App\Support\Directory\DirectoryAuthorizer;`

In the `show` method, replace the `assignableLevels` prop and add new props. Change lines around `'canEdit'` and `'assignableLevels'`:

```php
'canEdit' => request()->user()->can('update', $group),
'assignableLevels' => app(DirectoryAuthorizer::class)->assignableLevels(request()->user(), $group),
'canCreateChildGroup' => app(DirectoryAuthorizer::class)->canCreateChildGroup(request()->user(), $group),
'childGroupType' => $group->type->childGroupType()?->value,
```

Delete the entire private `getAssignableLevels` method (lines 148-179 approximately).

- [ ] **Step 6: Update StaffProfileController — replace getAssignableLevels**

In `app/Http/Controllers/Directory/StaffProfileController.php`:

Add import: `use App\Support\Directory\DirectoryAuthorizer;`

Replace the `'assignableLevels'` prop line in `show` method:

```php
'assignableLevels' => app(DirectoryAuthorizer::class)->assignableLevels($viewer, $group),
```

Delete the entire private `getAssignableLevels` method (lines 126-155 approximately).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Directory/ routes/apps/portal.php
git commit -m "feat: wire controllers to DirectoryAuthorizer, add department creation"
```

---

## Task 6: Frontend — update modals and DirectoryShow

**Files:**
- Rename: `resources/js/Pages/Directory/Components/TeamCreateModal.vue` → `SubGroupCreateModal.vue`
- Modify: `resources/js/Pages/Directory/Components/MemberAddModal.vue`
- Modify: `resources/js/Pages/Directory/Components/MemberEditModal.vue`
- Modify: `resources/js/Pages/Directory/DirectoryShow.vue`

- [ ] **Step 1: Rename TeamCreateModal → SubGroupCreateModal and make it generic**

Rename the file:

```bash
git mv resources/js/Pages/Directory/Components/TeamCreateModal.vue resources/js/Pages/Directory/Components/SubGroupCreateModal.vue
```

Read the current file, then update it to accept a `routeName` and `title` prop instead of hard-coding the team route. The component should accept:

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'

const props = defineProps({
    open: Boolean,
    groupHashid: String,
    routeName: { type: String, required: true },
    title: { type: String, required: true },
})

const emit = defineEmits(['close'])

const form = useForm({
    name: '',
    description: '',
})

function submit() {
    form.post(route(props.routeName, props.groupHashid), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            emit('close')
        },
    })
}
</script>
```

The template stays the same but uses `{{ title }}` for the dialog title.

- [ ] **Step 2: Update MemberAddModal to include level, title, can_manage_members**

Replace `resources/js/Pages/Directory/Components/MemberAddModal.vue` with:

```vue
<template>
    <Dialog :open="open" @update:open="$emit('close')">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ $t('directory_add_member') }}</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">{{ $t('directory_search_staff') }}</label>
                        <Command v-model="selectedUser" class="rounded-md border">
                            <CommandInput :placeholder="$t('directory_search_staff')" />
                            <CommandList class="max-h-48">
                                <CommandEmpty>No users found.</CommandEmpty>
                                <CommandGroup>
                                    <CommandItem
                                        v-for="user in staffMembers"
                                        :key="user.id"
                                        :value="user.hashid ?? user.id"
                                    >
                                        {{ user.name }}
                                    </CommandItem>
                                </CommandGroup>
                            </CommandList>
                        </Command>
                        <p v-if="form.errors.user_hashid" class="text-xs text-destructive mt-1">{{ form.errors.user_hashid }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">{{ $t('staff_profile_level') }}</label>
                        <Select v-model="form.level">
                            <SelectTrigger>
                                <span>{{ $t('level_' + form.level) }}</span>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="lvl in assignableLevels" :key="lvl" :value="lvl" :text-value="$t('level_' + lvl)">{{ $t('level_' + lvl) }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">{{ $t('staff_profile_title') }}</label>
                        <Input v-model="form.title" :placeholder="$t('staff_profile_title_placeholder')" />
                    </div>
                    <div v-if="groupType === 'department' || groupType === 'team'" class="flex items-center gap-2">
                        <Checkbox id="add_can_manage" v-model="form.can_manage_members" />
                        <label for="add_can_manage" class="text-sm">{{ $t('staff_profile_can_manage_members') }}</label>
                    </div>
                </div>
                <DialogFooter class="mt-4">
                    <Button type="button" variant="secondary" @click="$emit('close')">{{ $t('directory_cancel') }}</Button>
                    <Button type="submit" :disabled="form.processing || !selectedUser">{{ $t('directory_add_member') }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Checkbox } from '@/Components/ui/checkbox'
import { Select, SelectContent, SelectItem, SelectTrigger } from '@/Components/ui/select'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/Components/ui/command'

const props = defineProps({
    open: Boolean,
    groupHashid: String,
    groupType: String,
    assignableLevels: { type: Array, default: () => ['member'] },
})

const emit = defineEmits(['close'])

const page = usePage()
const staffMembers = computed(() => page.props.staffMemberList ?? [])

const selectedUser = ref(null)

const form = useForm({
    user_hashid: '',
    level: props.assignableLevels[0] ?? 'member',
    title: '',
    can_manage_members: false,
})

function submit() {
    form.user_hashid = selectedUser.value
    form.post(route('directory.members.store', props.groupHashid), {
        preserveScroll: true,
        onSuccess: () => {
            selectedUser.value = null
            form.reset()
            emit('close')
        },
    })
}
</script>
```

- [ ] **Step 3: Update MemberEditModal — conditional can_manage_members**

In `resources/js/Pages/Directory/Components/MemberEditModal.vue`, add `groupType` prop:

```js
const props = defineProps({
    open: Boolean,
    member: Object,
    groupHashid: String,
    groupType: String,
    assignableLevels: { type: Array, default: () => ['member'] },
})
```

Wrap the `can_manage_members` checkbox div with `v-if`:

```html
<div v-if="groupType === 'department' || groupType === 'team'" class="flex items-center gap-2">
```

- [ ] **Step 4: Update DirectoryShow.vue — wire new props and dynamic create button**

In `resources/js/Pages/Directory/DirectoryShow.vue`:

Add new props:

```js
canCreateChildGroup: Boolean,
childGroupType: { type: String, default: null },
```

Pass `groupType` to MemberAddModal and MemberEditModal:

```html
<MemberAddModal ... :group-type="group.type" :assignable-levels="assignableLevels" />
<MemberEditModal ... :group-type="group.type" />
```

Replace the `TeamCreateModal` import/usage with `SubGroupCreateModal`:

```js
import SubGroupCreateModal from './Components/SubGroupCreateModal.vue'
```

Compute the route name and title dynamically:

```js
const childGroupRouteName = computed(() =>
    props.childGroupType === 'department' ? 'directory.departments.store' : 'directory.teams.store'
)
const childGroupLabel = computed(() =>
    props.childGroupType === 'department' ? t('directory_create_department') : t('directory_create_team')
)
```

Show the create button only when `canCreateChildGroup` is true:

```html
<Button v-if="canCreateChildGroup" @click="showCreateSubGroup = true">{{ childGroupLabel }}</Button>
<SubGroupCreateModal
    v-if="canCreateChildGroup"
    :open="showCreateSubGroup"
    :group-hashid="group.hashid"
    :route-name="childGroupRouteName"
    :title="childGroupLabel"
    @close="showCreateSubGroup = false"
/>
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Directory/
git commit -m "feat: update frontend modals for level rules and department creation"
```

---

## Task 7: Feature tests — level rules and group creation

**Files:**
- Create: `tests/Feature/Directory/DirectoryLevelRulesTest.php`

- [ ] **Step 1: Write feature tests**

Create `tests/Feature/Directory/DirectoryLevelRulesTest.php`:

```php
<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupDirectoryHierarchy(): array
{
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);
    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $division = Group::factory()->division()->create(['name' => 'Division', 'parent_id' => $root->id]);
    $department = Group::factory()->department()->create(['name' => 'Department', 'parent_id' => $division->id]);
    $team = Group::factory()->team()->create(['name' => 'Team', 'parent_id' => $department->id]);

    return [$staffGroup, $root, $division, $department, $team];
}

function makeStaffUserForTest($staffGroup, array $attrs = []): User
{
    $user = User::factory()->create($attrs);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    return $user;
}

// --- StoreMember level validation ---

test('admin can add DivisionDirector to division', function () {
    [$staffGroup, , $division] = setupDirectoryHierarchy();
    $admin = makeStaffUserForTest($staffGroup, ['is_admin' => true]);
    $newUser = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('directory.members.store', $division), [
            'user_hashid' => $newUser->hashid,
            'level' => 'division_director',
        ])
        ->assertRedirect();

    expect($division->users()->where('user_id', $newUser->id)->first()->pivot->level)
        ->toBe(GroupUserLevel::DivisionDirector);
});

test('admin cannot add Member to division', function () {
    [$staffGroup, , $division] = setupDirectoryHierarchy();
    $admin = makeStaffUserForTest($staffGroup, ['is_admin' => true]);
    $newUser = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('directory.members.store', $division), [
            'user_hashid' => $newUser->hashid,
            'level' => 'member',
        ])
        ->assertSessionHasErrors('level');
});

test('hr user can add Director to department', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $hr = makeStaffUserForTest($staffGroup, ['is_hr' => true]);
    $newUser = User::factory()->create();

    $this->actingAs($hr)
        ->post(route('directory.members.store', $department), [
            'user_hashid' => $newUser->hashid,
            'level' => 'director',
        ])
        ->assertRedirect();
});

test('DD can add Director to child department', function () {
    [$staffGroup, , $division, $department] = setupDirectoryHierarchy();
    $dd = makeStaffUserForTest($staffGroup);
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);
    $newUser = User::factory()->create();

    $this->actingAs($dd)
        ->post(route('directory.members.store', $department), [
            'user_hashid' => $newUser->hashid,
            'level' => 'director',
        ])
        ->assertRedirect();
});

test('DD cannot add DD to own division (peer assignment)', function () {
    [$staffGroup, , $division] = setupDirectoryHierarchy();
    $dd = makeStaffUserForTest($staffGroup);
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);
    $newUser = User::factory()->create();

    $this->actingAs($dd)
        ->post(route('directory.members.store', $division), [
            'user_hashid' => $newUser->hashid,
            'level' => 'division_director',
        ])
        ->assertSessionHasErrors('level');
});

test('Director cannot add Director to own department (peer assignment)', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $director = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);
    $newUser = User::factory()->create();

    $this->actingAs($director)
        ->post(route('directory.members.store', $department), [
            'user_hashid' => $newUser->hashid,
            'level' => 'director',
        ])
        ->assertSessionHasErrors('level');
});

test('delegate member in department can add TeamLead to child team', function () {
    [$staffGroup, , , $department, $team] = setupDirectoryHierarchy();
    $delegate = makeStaffUserForTest($staffGroup);
    $department->users()->attach($delegate, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);
    $newUser = User::factory()->create();

    $this->actingAs($delegate)
        ->post(route('directory.members.store', $team), [
            'user_hashid' => $newUser->hashid,
            'level' => 'team_lead',
        ])
        ->assertRedirect();
});

test('plain member cannot add anyone', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $member = makeStaffUserForTest($staffGroup);
    $department->users()->attach($member, ['level' => GroupUserLevel::Member]);
    $newUser = User::factory()->create();

    $this->actingAs($member)
        ->post(route('directory.members.store', $department), [
            'user_hashid' => $newUser->hashid,
            'level' => 'member',
        ])
        ->assertForbidden();
});

// --- Group creation ---

test('DD can create department under division', function () {
    [$staffGroup, , $division] = setupDirectoryHierarchy();
    $dd = makeStaffUserForTest($staffGroup);
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);

    $this->actingAs($dd)
        ->post(route('directory.departments.store', $division), ['name' => 'New Dept'])
        ->assertRedirect();

    expect(Group::where('name', 'New Dept')->where('type', GroupTypeEnum::Department)->exists())->toBeTrue();
});

test('Director can create team under department', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $director = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);

    $this->actingAs($director)
        ->post(route('directory.teams.store', $department), ['name' => 'New Team'])
        ->assertRedirect();

    expect(Group::where('name', 'New Team')->where('type', GroupTypeEnum::Team)->exists())->toBeTrue();
});

test('Director cannot create department under division', function () {
    [$staffGroup, , $division, $department] = setupDirectoryHierarchy();
    $director = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);

    $this->actingAs($director)
        ->post(route('directory.departments.store', $division), ['name' => 'Nope'])
        ->assertForbidden();
});

test('cannot create child under team', function () {
    [$staffGroup, , , , $team] = setupDirectoryHierarchy();
    $admin = makeStaffUserForTest($staffGroup, ['is_admin' => true]);

    // Route doesn't exist for teams.store under a team, but testing via departments route
    $this->actingAs($admin)
        ->post(route('directory.departments.store', $team), ['name' => 'Nope'])
        ->assertForbidden();
});

// --- can_manage_members stripped on division ---

test('can_manage_members is stripped when adding to division', function () {
    [$staffGroup, , $division] = setupDirectoryHierarchy();
    $admin = makeStaffUserForTest($staffGroup, ['is_admin' => true]);
    $newUser = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('directory.members.store', $division), [
            'user_hashid' => $newUser->hashid,
            'level' => 'division_director',
            'can_manage_members' => true,
        ])
        ->assertRedirect();

    expect($division->users()->where('user_id', $newUser->id)->first()->pivot->can_manage_members)->toBeFalse();
});

// --- UpdateMember level validation ---

test('Director can update member level to TeamLead in child team', function () {
    [$staffGroup, , , $department, $team] = setupDirectoryHierarchy();
    $director = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);
    $member = User::factory()->create();
    $team->users()->attach($member, ['level' => GroupUserLevel::Member]);

    $this->actingAs($director)
        ->patch(route('directory.members.update', [$team, $member]), [
            'level' => 'team_lead',
            'title' => 'Lead',
            'can_manage_members' => false,
        ])
        ->assertRedirect();

    expect($team->users()->find($member)->pivot->level)->toBe(GroupUserLevel::TeamLead);
});

test('Director cannot set Director level in own department (peer)', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $director = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);
    $member = User::factory()->create();
    $department->users()->attach($member, ['level' => GroupUserLevel::Member]);

    $this->actingAs($director)
        ->patch(route('directory.members.update', [$department, $member]), [
            'level' => 'director',
            'title' => null,
            'can_manage_members' => false,
        ])
        ->assertSessionHasErrors('level');
});

test('admin can update member to any type-allowed level', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $admin = makeStaffUserForTest($staffGroup, ['is_admin' => true]);
    $member = User::factory()->create();
    $department->users()->attach($member, ['level' => GroupUserLevel::Member]);

    $this->actingAs($admin)
        ->patch(route('directory.members.update', [$department, $member]), [
            'level' => 'director',
            'title' => 'Dept Director',
            'can_manage_members' => false,
        ])
        ->assertRedirect();

    expect($department->users()->find($member)->pivot->level)->toBe(GroupUserLevel::Director);
});

// --- Remove member scope ---

test('Director cannot remove peer Director', function () {
    [$staffGroup, , , $department] = setupDirectoryHierarchy();
    $director1 = makeStaffUserForTest($staffGroup);
    $department->users()->attach($director1, ['level' => GroupUserLevel::Director]);
    $director2 = User::factory()->create();
    $department->users()->attach($director2, ['level' => GroupUserLevel::Director]);

    $this->actingAs($director1)
        ->delete(route('directory.members.destroy', [$department, $director2]))
        ->assertForbidden();
});

test('DD can remove Director from child department', function () {
    [$staffGroup, , $division, $department] = setupDirectoryHierarchy();
    $dd = makeStaffUserForTest($staffGroup);
    $division->users()->attach($dd, ['level' => GroupUserLevel::DivisionDirector]);
    $director = User::factory()->create();
    $department->users()->attach($director, ['level' => GroupUserLevel::Director]);

    $this->actingAs($dd)
        ->delete(route('directory.members.destroy', [$department, $director]))
        ->assertRedirect();

    expect($department->users()->where('user_id', $director->id)->exists())->toBeFalse();
});
```

- [ ] **Step 2: Run feature tests**

Run: `php artisan test tests/Feature/Directory/DirectoryLevelRulesTest.php`
Expected: All tests PASS (all backend changes are in place from Tasks 1-5).

- [ ] **Step 3: Run all existing directory tests to check for regressions**

Run: `php artisan test tests/Feature/Directory/`
Expected: Existing tests in `GroupManagementTest.php` may need updating — the `store` route now requires a `level` field. Fix any failures:

In `GroupManagementTest.php`, the test `'manager can add member to group'` posts only `user_hashid`. Update it to include `'level' => 'member'`:

```php
test('manager can add member to group', function () {
    [$manager, $department] = setupManager();
    $newUser = User::factory()->create();

    $this->actingAs($manager)
        ->post(route('directory.members.store', $department), [
            'user_hashid' => $newUser->hashid,
            'level' => 'member',
        ])
        ->assertRedirect();

    expect($department->users()->where('user_id', $newUser->id)->exists())->toBeTrue();
});
```

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Directory/DirectoryLevelRulesTest.php tests/Feature/Directory/GroupManagementTest.php
git commit -m "test: add level rules and group creation feature tests"
```

---

## Task 8: Final verification

- [ ] **Step 1: Run full test suite**

Run: `php artisan test`
Expected: All tests pass.

- [ ] **Step 2: Check for any remaining references to old getAssignableLevels**

Run: `grep -rn 'getAssignableLevels' app/`
Expected: No results.

- [ ] **Step 3: Verify route list**

Run: `php artisan route:list --name=directory`
Expected: `directory.departments.store` route exists alongside existing routes.

- [ ] **Step 4: Commit any stragglers**

If anything was missed, commit it now.
