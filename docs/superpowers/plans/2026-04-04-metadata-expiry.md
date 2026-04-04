# Metadata Expiry Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow apps to set an optional `expires_at` on user metadata keys, and prune expired rows daily.

**Architecture:** Add nullable `expires_at` column to `user_app_metadata`. Apps set it on upsert; omission/null = never expires. A daily scheduled artisan command hard-deletes expired rows. No read-time filtering (accepted 24h window per spec).

**Tech Stack:** Laravel 10, Pest (tests), MySQL.

**Spec:** `docs/superpowers/specs/2026-04-04-metadata-expiry-design.md`

---

## File Structure

- **Create:** `database/migrations/2026_04_04_XXXXXX_add_expires_at_to_user_app_metadata_table.php`
- **Create:** `app/Console/Commands/PruneExpiredMetadataCommand.php`
- **Modify:** `app/Models/UserAppMetadata.php` (fillable + cast)
- **Modify:** `app/Http/Requests/Api/v2/UpsertMetadataRequest.php` (validation)
- **Modify:** `app/Http/Controllers/Api/v2/MetadataController.php` (pass `expires_at` through)
- **Modify:** `app/Http/Resources/V2/MetadataResource.php` (expose `expires_at`)
- **Modify:** `app/Console/Kernel.php` (register command + schedule daily)
- **Modify:** `tests/Feature/Api/v2/MetadataTest.php` (feature tests)
- **Create:** `tests/Feature/Console/PruneExpiredMetadataCommandTest.php` (command tests)

---

## Task 1: Migration — add `expires_at` column

**Files:**
- Create: `database/migrations/2026_04_04_XXXXXX_add_expires_at_to_user_app_metadata_table.php`

- [ ] **Step 1: Create the migration**

Run: `php artisan make:migration add_expires_at_to_user_app_metadata_table --table=user_app_metadata`

- [ ] **Step 2: Fill in the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_app_metadata', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('user_app_metadata', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
```

- [ ] **Step 3: Run the migration**

Run: `php artisan migrate`
Expected: migration runs successfully.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/
git commit -m "Add expires_at column to user_app_metadata"
```

---

## Task 2: Model — fillable and cast

**Files:**
- Modify: `app/Models/UserAppMetadata.php`

- [ ] **Step 1: Add `expires_at` to `$fillable` and add `$casts`**

Current `$fillable` is `['user_id', 'client_id', 'key', 'value']`. Final state:

```php
protected $fillable = [
    'user_id',
    'client_id',
    'key',
    'value',
    'expires_at',
];

protected $casts = [
    'expires_at' => 'datetime',
];
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/UserAppMetadata.php
git commit -m "Add expires_at to UserAppMetadata model"
```

---

## Task 3: Request validation — accept `expires_at`

**Files:**
- Modify: `app/Http/Requests/Api/v2/UpsertMetadataRequest.php`
- Test: `tests/Feature/Api/v2/MetadataTest.php`

- [ ] **Step 1: Write failing tests**

Append to `tests/Feature/Api/v2/MetadataTest.php`:

```php
it('accepts a valid future expires_at on upsert', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    $expiresAt = now()->addYears(3)->startOfSecond();

    $this->putJson('/api/v2/metadata/address', [
        'value' => '123 Main St',
        'expires_at' => $expiresAt->toIso8601String(),
    ])
        ->assertCreated()
        ->assertJson([
            'key' => 'address',
            'value' => '123 Main St',
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

    $this->assertDatabaseHas('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'address',
        'expires_at' => $expiresAt->toDateTimeString(),
    ]);
});

it('rejects expires_at in the past', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    $this->putJson('/api/v2/metadata/address', [
        'value' => '123 Main St',
        'expires_at' => now()->subDay()->toIso8601String(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expires_at']);
});

it('accepts null expires_at meaning never expires', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    $this->putJson('/api/v2/metadata/address', [
        'value' => '123 Main St',
        'expires_at' => null,
    ])
        ->assertCreated()
        ->assertJson(['expires_at' => null]);
});
```

- [ ] **Step 2: Run tests, confirm they fail**

Run: `./vendor/bin/pest tests/Feature/Api/v2/MetadataTest.php --filter='expires_at|never expires'`
Expected: FAIL — validation error not thrown / field not persisted / field not returned.

- [ ] **Step 3: Update validation rules**

In `UpsertMetadataRequest::rules()`:

```php
return [
    'value' => ['required', 'string', 'max:65535'],
    'expires_at' => ['nullable', 'date', 'after:now'],
];
```

- [ ] **Step 4: Commit (tests still not fully passing — controller + resource come next)**

```bash
git add app/Http/Requests/Api/v2/UpsertMetadataRequest.php tests/Feature/Api/v2/MetadataTest.php
git commit -m "Add expires_at validation to metadata upsert request"
```

---

## Task 4: Controller — persist `expires_at`

**Files:**
- Modify: `app/Http/Controllers/Api/v2/MetadataController.php`

- [ ] **Step 1: Update `upsert` to pass `expires_at` through**

Replace the `updateOrCreate` call in `MetadataController::upsert` with:

```php
$metadata = UserAppMetadata::updateOrCreate(
    [
        'user_id' => $request->user()->id,
        'client_id' => $this->clientId(),
        'key' => $key,
    ],
    [
        'value' => $request->validated('value'),
        'expires_at' => $request->validated('expires_at'),
    ]
);
```

Note: `$request->validated('expires_at')` returns `null` when the field is omitted or explicitly null — both cases clear the expiry, matching the spec's "omission = clear" semantics.

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/Api/v2/MetadataController.php
git commit -m "Persist expires_at on metadata upsert"
```

---

## Task 5: Resource — expose `expires_at`

**Files:**
- Modify: `app/Http/Resources/V2/MetadataResource.php`

- [ ] **Step 1: Include `expires_at` in response**

```php
public function toArray(Request $request): array
{
    return [
        'key' => $this->key,
        'value' => $this->value,
        'expires_at' => $this->expires_at?->toIso8601String(),
    ];
}
```

- [ ] **Step 2: Run the Task 3 tests, confirm they pass**

Run: `./vendor/bin/pest tests/Feature/Api/v2/MetadataTest.php`
Expected: all metadata tests PASS, including the three added in Task 3.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Resources/V2/MetadataResource.php
git commit -m "Expose expires_at in metadata resource"
```

---

## Task 6: Omission-clears-expiry test

**Files:**
- Test: `tests/Feature/Api/v2/MetadataTest.php`

- [ ] **Step 1: Add the test**

```php
it('clears expires_at when upsert omits the field', function () {
    $user = User::factory()->create();
    actingAsApiUser($user, 'app-one', ['metadata.write']);

    UserAppMetadata::create([
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'address',
        'value' => '123 Main St',
        'expires_at' => now()->addYear(),
    ]);

    $this->putJson('/api/v2/metadata/address', ['value' => '456 Oak Ave'])
        ->assertOk()
        ->assertJson(['expires_at' => null]);

    $this->assertDatabaseHas('user_app_metadata', [
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'address',
        'value' => '456 Oak Ave',
        'expires_at' => null,
    ]);
});
```

- [ ] **Step 2: Run and confirm it passes**

Run: `./vendor/bin/pest tests/Feature/Api/v2/MetadataTest.php --filter='clears expires_at'`
Expected: PASS (behavior already implemented in Task 4).

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Api/v2/MetadataTest.php
git commit -m "Test that omitting expires_at clears it"
```

---

## Task 7: Prune command — failing tests

**Files:**
- Create: `tests/Feature/Console/PruneExpiredMetadataCommandTest.php`

- [ ] **Step 1: Write the test file**

```php
<?php

use App\Models\User;
use App\Models\UserAppMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes rows whose expires_at is in the past', function () {
    $user = User::factory()->create();

    UserAppMetadata::create([
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'expired',
        'value' => 'gone',
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('metadata:prune-expired')->assertSuccessful();

    $this->assertDatabaseMissing('user_app_metadata', ['key' => 'expired']);
});

it('leaves rows with null expires_at untouched', function () {
    $user = User::factory()->create();

    UserAppMetadata::create([
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'forever',
        'value' => 'keep',
        'expires_at' => null,
    ]);

    $this->artisan('metadata:prune-expired')->assertSuccessful();

    $this->assertDatabaseHas('user_app_metadata', ['key' => 'forever']);
});

it('leaves rows with future expires_at untouched', function () {
    $user = User::factory()->create();

    UserAppMetadata::create([
        'user_id' => $user->id,
        'client_id' => 'app-one',
        'key' => 'future',
        'value' => 'keep',
        'expires_at' => now()->addYear(),
    ]);

    $this->artisan('metadata:prune-expired')->assertSuccessful();

    $this->assertDatabaseHas('user_app_metadata', ['key' => 'future']);
});
```

- [ ] **Step 2: Run, confirm they fail**

Run: `./vendor/bin/pest tests/Feature/Console/PruneExpiredMetadataCommandTest.php`
Expected: FAIL — `metadata:prune-expired` command does not exist.

---

## Task 8: Prune command — implementation

**Files:**
- Create: `app/Console/Commands/PruneExpiredMetadataCommand.php`
- Modify: `app/Console/Kernel.php`

- [ ] **Step 1: Create the command**

```php
<?php

namespace App\Console\Commands;

use App\Models\UserAppMetadata;
use Illuminate\Console\Command;

class PruneExpiredMetadataCommand extends Command
{
    protected $signature = 'metadata:prune-expired';

    protected $description = 'Delete user_app_metadata rows whose expires_at is in the past.';

    public function handle(): int
    {
        $count = UserAppMetadata::where('expires_at', '<', now())->delete();

        $this->info("Pruned {$count} expired metadata row(s).");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Register in `app/Console/Kernel.php`**

Add import and register in `$commands` and `schedule()`. The file currently imports `ClearUnverifiedCommand` etc.; add alongside:

```php
use App\Console\Commands\PruneExpiredMetadataCommand;
```

In `$commands`:

```php
PruneExpiredMetadataCommand::class,
```

In `schedule()`:

```php
$schedule->command('metadata:prune-expired')->daily();
```

- [ ] **Step 3: Run the command tests, confirm they pass**

Run: `./vendor/bin/pest tests/Feature/Console/PruneExpiredMetadataCommandTest.php`
Expected: all 3 PASS.

- [ ] **Step 4: Run full metadata test suite**

Run: `./vendor/bin/pest tests/Feature/Api/v2/MetadataTest.php tests/Feature/Console/PruneExpiredMetadataCommandTest.php`
Expected: ALL PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/PruneExpiredMetadataCommand.php app/Console/Kernel.php tests/Feature/Console/PruneExpiredMetadataCommandTest.php
git commit -m "Add metadata:prune-expired daily command"
```

---

## Task 9: Final verification

- [ ] **Step 1: Run the full test suite**

Run: `./vendor/bin/pest`
Expected: ALL PASS. If anything unrelated breaks, stop and investigate.

- [ ] **Step 2: Verify schedule**

Run: `php artisan schedule:list`
Expected: `metadata:prune-expired` appears with daily cadence.
