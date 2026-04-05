# App Management Refactor + First-Party Webhooks Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Simplify the developer-facing app create form to three fields, replace the monolithic detail page with a route-based left-sidebar layout (General / OAuth / Logout / Credentials / Webhooks / Danger), and add first-party-only `user.updated` webhooks with HMAC signing, retries, a 7-day delivery log, and Docusaurus documentation.

**Architecture:** New columns on `apps` (`webhook_url`, `webhook_secret` [encrypted], `webhook_subscribed_fields`, `webhook_event_name`) plus a new `webhook_deliveries` table. Dispatch is driven by a single `UserObserver::updated()` → `WebhookDispatcher` (pure service) → `DeliverWebhook` job on a dedicated `webhooks` Horizon queue. Signing is a pure `WebhookSigner` class unit-tested in isolation. The developer portal splits `AppsController@update` into per-section endpoints and introduces a new `AppWebhookController`. Vue pages are restructured under `resources/js/Pages/Settings/Apps/AppDetail/` with a shared left-sidebar layout. Webhooks are documented in Docusaurus (OpenAPI 2.0 has no webhook construct).

**Tech Stack:** Laravel 10+, Inertia.js v3, Vue 3 (Composition API), Pinia-free `useForm`, Tailwind, shadcn-vue, Horizon, Spatie ActivityLog (already installed), Pest.

**Spec:** `docs/superpowers/specs/2026-04-06-app-management-refactor-and-webhooks-design.md`

**Critical codebase note (read before Phase 2):** The user-facing "username" is stored in the `users.name` column, not `users.username`. `RegisterController.php:29` maps `username` → `name`. Everywhere the webhook UI and payload say `"username"`, the underlying DB column is `name`. The dispatcher does this mapping in exactly one place. **Do not rename** the DB column.

---

## File Structure

### New files

**Backend — Models, migrations, commands**
- `database/migrations/2026_04_06_000001_add_webhook_columns_to_apps_table.php`
- `database/migrations/2026_04_06_000002_create_webhook_deliveries_table.php`
- `app/Models/WebhookDelivery.php`
- `app/Console/Commands/PruneWebhookDeliveries.php`

**Backend — Services, jobs, observer**
- `app/Services/Webhooks/WebhookSigner.php`
- `app/Services/Webhooks/WebhookDispatcher.php`
- `app/Services/Webhooks/UserFieldMap.php` (static map between external field names like `username` and DB columns like `name`)
- `app/Jobs/Webhooks/DeliverWebhook.php`
- `app/Observers/UserObserver.php`

**Backend — Requests, controllers, policy additions**
- `app/Http/Requests/Developer/StoreAppRequest.php`
- `app/Http/Requests/Developer/UpdateAppGeneralRequest.php`
- `app/Http/Requests/Developer/UpdateAppOAuthRequest.php`
- `app/Http/Requests/Developer/UpdateAppLogoutRequest.php`
- `app/Http/Requests/Developer/UpdateAppWebhookRequest.php`
- `app/Http/Controllers/Profile/Settings/AppWebhookController.php`

**Frontend — Vue**
- `resources/js/Pages/Settings/Apps/AppDetail/Layout.vue` (sidebar wrapper — **not** an Inertia page, a normal component)
- `resources/js/Pages/Settings/Apps/AppDetail/General.vue`
- `resources/js/Pages/Settings/Apps/AppDetail/OAuth.vue`
- `resources/js/Pages/Settings/Apps/AppDetail/Logout.vue`
- `resources/js/Pages/Settings/Apps/AppDetail/Credentials.vue`
- `resources/js/Pages/Settings/Apps/AppDetail/Webhooks.vue`
- `resources/js/Pages/Settings/Apps/AppDetail/Danger.vue`
- `resources/js/Pages/Settings/Apps/AppDetail/sidebar.js` (shared nav constant)

**Docs**
- `docs/docs/identity/integration/webhooks.md`

**Tests**
- `tests/Unit/Webhooks/WebhookSignerTest.php`
- `tests/Feature/Webhooks/WebhookDispatcherTest.php`
- `tests/Feature/Webhooks/UserUpdatedDispatchTest.php`
- `tests/Feature/Webhooks/DeliverWebhookTest.php`
- `tests/Feature/Webhooks/PruneWebhookDeliveriesTest.php`
- `tests/Feature/Developer/AppCreateTest.php`
- `tests/Feature/Developer/AppDetailSectionsTest.php`
- `tests/Feature/Developer/AppWebhookControllerTest.php`

### Modified files

- `app/Models/App.php` — add fillable columns, casts, `webhookDeliveries()` relation.
- `app/Models/User.php` — register `UserObserver`.
- `app/Http/Controllers/Profile/Settings/AppsController.php` — remove `show`/`edit`, replace `update` with `updateGeneral`/`updateOAuth`/`updateLogout`, trim `store`, add a `redirect` action for `/developers/{app}`.
- `app/Policies/AppPolicy.php` — add `manageWebhooks`, `viewWebhookSecret`.
- `app/Providers/AppServiceProvider.php` — register `User::observe(UserObserver::class)`.
- `config/horizon.php` — add `webhooks` queue to existing supervisor(s).
- `routes/apps/portal.php` — replace the `developers.show`/`edit`/`update` block with per-section routes and the webhook routes.
- `resources/js/Pages/Settings/Apps/AppCreate.vue` — shrink to three fields.
- `resources/js/Pages/Settings/Apps/AppShow.vue` — **delete**.
- `resources/js/Pages/Settings/Apps/AppEdit.vue` — **delete**.
- `resources/js/Pages/Developers.vue` — update list links to `developers.general`.
- `docs/docs/identity/integration/build-an-application.md` — link to new webhooks page.
- `docs/sidebars.ts` — register new page under Integration.
- `app/Http/Requests/Staff/StoreAppRequest.php`, `app/Http/Requests/Staff/UpdateAppRequest.php` — **delete** (moved to `Developer/`).

---

## Phase 0 — Preflight

### Task 0.1: Verify current working tree & run baseline tests

- [ ] **Step 1:** Confirm you are on a clean working tree on a dedicated branch/worktree.

```bash
git status
git rev-parse --abbrev-ref HEAD
```

- [ ] **Step 2:** Run the existing test suite once to establish a green baseline.

```bash
php artisan test
```

Expected: all tests pass. If anything is red before we start, stop and surface to the human.

---

## Phase 1 — Database schema

### Task 1.1: Migration — add webhook columns to `apps`

**Files:**
- Create: `database/migrations/2026_04_06_000001_add_webhook_columns_to_apps_table.php`

- [ ] **Step 1:** Create migration.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->string('webhook_url', 2000)->nullable()->after('allow_notifications');
            $table->text('webhook_secret')->nullable()->after('webhook_url');
            $table->json('webhook_subscribed_fields')->nullable()->after('webhook_secret');
            $table->string('webhook_event_name', 64)->nullable()->after('webhook_subscribed_fields');
        });
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn(['webhook_url', 'webhook_secret', 'webhook_subscribed_fields', 'webhook_event_name']);
        });
    }
};
```

- [ ] **Step 2:** Run migration.

```bash
php artisan migrate
```

Expected: new columns present. Verify with `php artisan tinker` → `Schema::getColumnListing('apps')`.

- [ ] **Step 3:** Commit.

```bash
git add database/migrations/2026_04_06_000001_add_webhook_columns_to_apps_table.php
git commit -m "feat: add webhook columns to apps table"
```

### Task 1.2: Migration — create `webhook_deliveries`

**Files:**
- Create: `database/migrations/2026_04_06_000002_create_webhook_deliveries_table.php`

- [ ] **Step 1:** Create migration.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->string('event', 64);
            $table->string('url', 2000);
            $table->json('payload');
            $table->string('signature', 255);
            $table->enum('status', ['pending', 'delivered', 'failed', 'retrying'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['app_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
```

- [ ] **Step 2:** Run migration.

```bash
php artisan migrate
```

- [ ] **Step 3:** Commit.

```bash
git add database/migrations/2026_04_06_000002_create_webhook_deliveries_table.php
git commit -m "feat: create webhook_deliveries table"
```

### Task 1.3: `WebhookDelivery` model

**Files:**
- Create: `app/Models/WebhookDelivery.php`

- [ ] **Step 1:** Create model.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'app_id',
        'event',
        'url',
        'payload',
        'signature',
        'status',
        'attempts',
        'response_code',
        'response_body',
        'error',
        'next_retry_at',
        'delivered_at',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'response_code' => 'integer',
        'next_retry_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
```

- [ ] **Step 2:** Commit.

```bash
git add app/Models/WebhookDelivery.php
git commit -m "feat: add WebhookDelivery model"
```

### Task 1.4: Extend `App` model

**Files:**
- Modify: `app/Models/App.php`

- [ ] **Step 1:** Update `App` model — add fillable, casts, relation. Because `$guarded = ['client_secret']` is currently used, fillable isn't necessary, but casts are.

Add to `$casts` array:
```php
'webhook_subscribed_fields' => 'array',
'webhook_secret' => 'encrypted',
```

Add method:
```php
public function webhookDeliveries(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(WebhookDelivery::class);
}
```

- [ ] **Step 2:** Commit.

```bash
git add app/Models/App.php
git commit -m "feat: add webhook casts and relation to App model"
```

---

## Phase 2 — Pure services (signing + field map)

### Task 2.1: `UserFieldMap`

**Files:**
- Create: `app/Services/Webhooks/UserFieldMap.php`

- [ ] **Step 1:** Create the class. This is the single source of truth for the external name ↔ DB column mapping.

```php
<?php

namespace App\Services\Webhooks;

class UserFieldMap
{
    /**
     * External field name => users table column.
     * External names are what developers configure in the UI and what appears
     * in webhook payloads. DB columns are what Eloquent reports in getChanges().
     */
    public const MAP = [
        'email' => 'email',
        'username' => 'name',
    ];

    /** @return list<string> */
    public static function subscribableFields(): array
    {
        return array_keys(self::MAP);
    }

    public static function columnFor(string $externalField): ?string
    {
        return self::MAP[$externalField] ?? null;
    }

    public static function externalNameFor(string $column): ?string
    {
        $flipped = array_flip(self::MAP);
        return $flipped[$column] ?? null;
    }
}
```

- [ ] **Step 2:** Commit.

```bash
git add app/Services/Webhooks/UserFieldMap.php
git commit -m "feat: add webhook user field map"
```

### Task 2.2: `WebhookSigner` — TDD

**Files:**
- Create: `app/Services/Webhooks/WebhookSigner.php`
- Create: `tests/Unit/Webhooks/WebhookSignerTest.php`

- [ ] **Step 1:** Write failing test with known vectors.

```php
<?php

namespace Tests\Unit\Webhooks;

use App\Services\Webhooks\WebhookSigner;
use PHPUnit\Framework\TestCase;

class WebhookSignerTest extends TestCase
{
    public function test_sign_produces_expected_known_vector(): void
    {
        $signer = new WebhookSigner();

        $secret = 'supersecret';
        $timestamp = 1_700_000_000;
        $body = '{"event":"user.updated"}';

        // Precomputed: hmac_sha256("supersecret", "1700000000.{...body}")
        $expected = 'v1,' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        $this->assertSame($expected, $signer->sign($secret, $timestamp, $body));
    }

    public function test_verify_accepts_valid_signature(): void
    {
        $signer = new WebhookSigner();
        $secret = 'supersecret';
        $timestamp = time();
        $body = '{"ok":true}';

        $signature = $signer->sign($secret, $timestamp, $body);

        $this->assertTrue($signer->verify($secret, $timestamp, $body, $signature, toleranceSeconds: 300));
    }

    public function test_verify_rejects_tampered_body(): void
    {
        $signer = new WebhookSigner();
        $secret = 'supersecret';
        $timestamp = time();
        $signature = $signer->sign($secret, $timestamp, '{"ok":true}');

        $this->assertFalse($signer->verify($secret, $timestamp, '{"ok":false}', $signature, toleranceSeconds: 300));
    }

    public function test_verify_rejects_stale_timestamp(): void
    {
        $signer = new WebhookSigner();
        $secret = 'supersecret';
        $staleTs = time() - 3600;
        $body = '{"ok":true}';
        $signature = $signer->sign($secret, $staleTs, $body);

        $this->assertFalse($signer->verify($secret, $staleTs, $body, $signature, toleranceSeconds: 300));
    }

    public function test_verify_rejects_wrong_prefix(): void
    {
        $signer = new WebhookSigner();
        $this->assertFalse(
            $signer->verify('s', time(), 'b', 'v2,deadbeef', toleranceSeconds: 300),
        );
    }
}
```

- [ ] **Step 2:** Run test, expect failure.

```bash
php artisan test tests/Unit/Webhooks/WebhookSignerTest.php
```

Expected: class not found / methods missing.

- [ ] **Step 3:** Implement.

```php
<?php

namespace App\Services\Webhooks;

class WebhookSigner
{
    public const PREFIX = 'v1,';

    public function sign(string $secret, int $timestamp, string $body): string
    {
        return self::PREFIX . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    public function verify(
        string $secret,
        int $timestamp,
        string $body,
        string $signature,
        int $toleranceSeconds,
    ): bool {
        if (! str_starts_with($signature, self::PREFIX)) {
            return false;
        }

        if (abs(time() - $timestamp) > $toleranceSeconds) {
            return false;
        }

        $expected = $this->sign($secret, $timestamp, $body);

        return hash_equals($expected, $signature);
    }
}
```

- [ ] **Step 4:** Run tests, expect pass.

```bash
php artisan test tests/Unit/Webhooks/WebhookSignerTest.php
```

- [ ] **Step 5:** Commit.

```bash
git add app/Services/Webhooks/WebhookSigner.php tests/Unit/Webhooks/WebhookSignerTest.php
git commit -m "feat: add WebhookSigner with HMAC sign/verify"
```

---

## Phase 3 — Dispatcher + delivery job

### Task 3.1: `WebhookDispatcher` — TDD

**Files:**
- Create: `app/Services/Webhooks/WebhookDispatcher.php`
- Create: `tests/Feature/Webhooks/WebhookDispatcherTest.php`

- [ ] **Step 1:** Write failing test. Use `RefreshDatabase`, model factories, `Queue::fake()`.

```php
<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\Webhooks\DeliverWebhook;
use App\Models\App;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_for_first_party_app_when_subscribed_field_changed(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $app = App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email', 'username'],
        ]);

        $user = User::factory()->create(['email' => 'new@example.com', 'name' => 'new_handle']);

        app(WebhookDispatcher::class)->dispatchUserUpdated(
            $user,
            oldValues: ['email' => 'old@example.com'],
            changedExternalFields: ['email'],
        );

        Queue::assertPushedOn('webhooks', DeliverWebhook::class);
        $this->assertDatabaseHas('webhook_deliveries', [
            'app_id' => $app->id,
            'event' => 'user.updated',
            'status' => 'pending',
            'url' => 'https://example.test/hook',
        ]);

        $delivery = WebhookDelivery::first();
        $this->assertSame(['email'], array_keys($delivery->payload['changed']));
        $this->assertSame('old@example.com', $delivery->payload['changed']['email']['old']);
        $this->assertSame('new@example.com', $delivery->payload['changed']['email']['new']);
    }

    public function test_skips_third_party_apps(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => false,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email'],
        ]);

        app(WebhookDispatcher::class)->dispatchUserUpdated(
            User::factory()->create(),
            oldValues: ['email' => 'old@example.com'],
            changedExternalFields: ['email'],
        );

        Queue::assertNothingPushed();
    }

    public function test_skips_apps_without_webhook_url(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => null,
            'webhook_subscribed_fields' => ['email'],
        ]);

        app(WebhookDispatcher::class)->dispatchUserUpdated(
            User::factory()->create(),
            oldValues: ['email' => 'old@example.com'],
            changedExternalFields: ['email'],
        );

        Queue::assertNothingPushed();
    }

    public function test_skips_apps_without_field_intersection(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['username'],
        ]);

        app(WebhookDispatcher::class)->dispatchUserUpdated(
            User::factory()->create(),
            oldValues: ['email' => 'old@example.com'],
            changedExternalFields: ['email'],
        );

        Queue::assertNothingPushed();
    }

    public function test_payload_is_filtered_to_subscribed_intersection(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email'], // only email
        ]);

        $user = User::factory()->create(['email' => 'new@example.com', 'name' => 'new_handle']);

        app(WebhookDispatcher::class)->dispatchUserUpdated(
            $user,
            oldValues: ['email' => 'old@example.com', 'name' => 'old_handle'],
            changedExternalFields: ['email', 'username'], // both changed but app only subscribed to email
        );

        $delivery = WebhookDelivery::first();
        $this->assertSame(['email'], array_keys($delivery->payload['changed']));
    }
}
```

- [ ] **Step 2:** Run, expect failure (class missing).

```bash
php artisan test tests/Feature/Webhooks/WebhookDispatcherTest.php
```

- [ ] **Step 3:** Implement.

```php
<?php

namespace App\Services\Webhooks;

use App\Jobs\Webhooks\DeliverWebhook;
use App\Models\App;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookDispatcher
{
    public function __construct(private readonly WebhookSigner $signer)
    {
    }

    /**
     * @param  array<string,mixed>  $oldValues  keyed by external field name (e.g. "email", "username")
     * @param  list<string>  $changedExternalFields
     */
    public function dispatchUserUpdated(User $user, array $oldValues, array $changedExternalFields): void
    {
        if ($changedExternalFields === []) {
            return;
        }

        $apps = App::query()
            ->where('first_party', true)
            ->whereNotNull('webhook_url')
            ->whereNotNull('webhook_secret')
            ->get();

        foreach ($apps as $app) {
            $subscribed = (array) ($app->webhook_subscribed_fields ?? []);
            $intersection = array_values(array_intersect($subscribed, $changedExternalFields));

            if ($intersection === []) {
                continue;
            }

            $changed = [];
            foreach ($intersection as $field) {
                $column = UserFieldMap::columnFor($field);
                if ($column === null) {
                    continue;
                }
                $changed[$field] = [
                    'old' => $oldValues[$field] ?? null,
                    'new' => $user->{$column},
                ];
            }

            if ($changed === []) {
                continue;
            }

            $deliveryId = (string) Str::ulid();

            $payload = [
                'event' => 'user.updated',
                'id' => $deliveryId,
                'occurred_at' => now()->toIso8601String(),
                'subject' => (string) $user->getKey(),
                'changed' => $changed,
            ];

            $body = json_encode($payload, JSON_THROW_ON_ERROR);
            $timestamp = now()->timestamp;
            $signature = $this->signer->sign($app->webhook_secret, $timestamp, $body);

            $delivery = WebhookDelivery::create([
                'id' => $deliveryId,
                'app_id' => $app->id,
                'event' => 'user.updated',
                'url' => $app->webhook_url,
                'payload' => $payload,
                'signature' => $signature,
                'status' => 'pending',
                'attempts' => 0,
                'created_at' => now(),
            ]);

            DeliverWebhook::dispatch($delivery->id, $timestamp)->onQueue('webhooks');
        }
    }
}
```

- [ ] **Step 4:** Write `DeliverWebhook` stub so class-resolution passes (real impl in Task 3.2).

```php
<?php

namespace App\Jobs\Webhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $deliveryId, public int $timestamp)
    {
    }

    public function handle(): void
    {
        // implemented in Task 3.2
    }
}
```

- [ ] **Step 5:** Add factory state bits if missing. In `database/factories/AppFactory.php`, ensure the factory can accept the new columns. (No new method needed if factory passes attributes through to `create`.)

Verify with:

```bash
php artisan test tests/Feature/Webhooks/WebhookDispatcherTest.php
```

Expected: pass.

- [ ] **Step 6:** Commit.

```bash
git add app/Services/Webhooks/WebhookDispatcher.php app/Jobs/Webhooks/DeliverWebhook.php tests/Feature/Webhooks/WebhookDispatcherTest.php
git commit -m "feat: add WebhookDispatcher with subscription filtering"
```

### Task 3.2: `DeliverWebhook` job — TDD

**Files:**
- Modify: `app/Jobs/Webhooks/DeliverWebhook.php`
- Create: `tests/Feature/Webhooks/DeliverWebhookTest.php`

- [ ] **Step 1:** Write failing feature test. Use `Http::fake()`.

```php
<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\Webhooks\DeliverWebhook;
use App\Models\App;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeliverWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function makeDelivery(array $overrides = []): WebhookDelivery
    {
        $owner = User::factory()->create();
        $app = App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email'],
        ]);

        return WebhookDelivery::create(array_merge([
            'id' => (string) Str::ulid(),
            'app_id' => $app->id,
            'event' => 'user.updated',
            'url' => $app->webhook_url,
            'payload' => ['event' => 'user.updated', 'changed' => []],
            'signature' => 'v1,abc',
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => now(),
        ], $overrides));
    }

    public function test_2xx_marks_delivered(): void
    {
        Http::fake(['example.test/*' => Http::response('ok', 200)]);
        $delivery = $this->makeDelivery();

        (new DeliverWebhook($delivery->id, now()->timestamp))->handle();

        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);
        $this->assertSame(200, $delivery->response_code);
        $this->assertNotNull($delivery->delivered_at);
    }

    public function test_5xx_marks_retrying_and_throws(): void
    {
        Http::fake(['example.test/*' => Http::response('boom', 500)]);
        $delivery = $this->makeDelivery();

        $job = new DeliverWebhook($delivery->id, now()->timestamp);

        $this->expectException(\RuntimeException::class);
        $job->handle();

        $delivery->refresh();
        $this->assertSame('retrying', $delivery->status);
        $this->assertSame(500, $delivery->response_code);
        $this->assertStringContainsString('boom', $delivery->response_body);
    }

    public function test_response_body_truncated_to_2048(): void
    {
        Http::fake(['example.test/*' => Http::response(str_repeat('x', 5000), 200)]);
        $delivery = $this->makeDelivery();
        (new DeliverWebhook($delivery->id, now()->timestamp))->handle();

        $delivery->refresh();
        $this->assertLessThanOrEqual(2100, strlen($delivery->response_body));
        $this->assertStringEndsWith('(truncated)', $delivery->response_body);
    }

    public function test_failed_hook_marks_failed(): void
    {
        $delivery = $this->makeDelivery(['status' => 'retrying', 'attempts' => 5]);
        (new DeliverWebhook($delivery->id, now()->timestamp))->failed(new \RuntimeException('exhausted'));

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertStringContainsString('exhausted', $delivery->error);
    }

    public function test_skips_when_already_delivered(): void
    {
        Http::fake();
        $delivery = $this->makeDelivery(['status' => 'delivered', 'delivered_at' => now()]);

        (new DeliverWebhook($delivery->id, now()->timestamp))->handle();

        Http::assertNothingSent();
    }
}
```

- [ ] **Step 2:** Run test, expect failures.

```bash
php artisan test tests/Feature/Webhooks/DeliverWebhookTest.php
```

- [ ] **Step 3:** Implement the real job.

```php
<?php

namespace App\Jobs\Webhooks;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    public int $timeout = 20;

    public function __construct(public string $deliveryId, public int $timestamp)
    {
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300, 1800, 7200, 21600];
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::with('app')->find($this->deliveryId);
        if (! $delivery || $delivery->status === 'delivered' || ! $delivery->app) {
            return;
        }

        $body = json_encode($delivery->payload, JSON_THROW_ON_ERROR);

        try {
            /** @var Response $response */
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'User-Agent' => 'EF-Identity-Webhooks/1.0',
                'X-EF-Event' => $delivery->event,
                'X-EF-Delivery' => $delivery->id,
                'X-EF-Timestamp' => (string) $this->timestamp,
                'X-EF-Signature' => $delivery->signature,
            ])
                ->connectTimeout(8)
                ->timeout(15)
                ->withBody($body, 'application/json')
                ->post($delivery->url);

            $delivery->attempts = $this->attempts();
            $delivery->response_code = $response->status();
            $delivery->response_body = $this->truncate((string) $response->body());

            if ($response->successful()) {
                $delivery->status = 'delivered';
                $delivery->delivered_at = now();
                $delivery->save();
                return;
            }

            $this->markRetryingOrThrow($delivery, 'HTTP ' . $response->status());
        } catch (Throwable $e) {
            $delivery->attempts = $this->attempts();
            $delivery->error = $e->getMessage();
            $this->markRetryingOrThrow($delivery, $e->getMessage(), rethrow: $e);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $delivery = WebhookDelivery::find($this->deliveryId);
        if (! $delivery) {
            return;
        }
        $delivery->status = 'failed';
        if ($exception && ! $delivery->error) {
            $delivery->error = $exception->getMessage();
        }
        $delivery->save();
    }

    private function markRetryingOrThrow(WebhookDelivery $delivery, string $reason, ?Throwable $rethrow = null): void
    {
        // NOTE: when $attempts >= $tries we intentionally do NOT touch $delivery->status here.
        // We still rethrow so the worker records the failure; the failed() hook then marks
        // status=failed after the final attempt. Do not "fix" this by setting status='failed'
        // in this branch — it would race with failed() and lose the final error message.
        $attempts = $this->attempts();
        if ($attempts < $this->tries) {
            $delivery->status = 'retrying';
            $nextBackoff = $this->backoff()[$attempts] ?? 21600;
            $delivery->next_retry_at = now()->addSeconds($nextBackoff);
        }
        $delivery->save();

        throw $rethrow ?? new \RuntimeException($reason);
    }

    private function truncate(string $body): string
    {
        if (strlen($body) <= 2048) {
            return $body;
        }
        return substr($body, 0, 2048) . '…(truncated)';
    }
}
```

- [ ] **Step 4:** Run tests.

```bash
php artisan test tests/Feature/Webhooks/DeliverWebhookTest.php
```

- [ ] **Step 5:** Commit.

```bash
git add app/Jobs/Webhooks/DeliverWebhook.php tests/Feature/Webhooks/DeliverWebhookTest.php
git commit -m "feat: implement DeliverWebhook job with retries and truncation"
```

---

## Phase 4 — Observer wiring

### Task 4.1: `UserObserver` + AppServiceProvider registration — TDD

**Files:**
- Create: `app/Observers/UserObserver.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Feature/Webhooks/UserUpdatedDispatchTest.php`

- [ ] **Step 1:** Write failing integration test.

```php
<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\Webhooks\DeliverWebhook;
use App\Models\App;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UserUpdatedDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_change_creates_delivery(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email'],
        ]);

        $user = User::factory()->create(['email' => 'old@example.com']);
        $user->update(['email' => 'new@example.com']);

        Queue::assertPushed(DeliverWebhook::class);
        $delivery = WebhookDelivery::firstOrFail();
        $this->assertSame('old@example.com', $delivery->payload['changed']['email']['old']);
        $this->assertSame('new@example.com', $delivery->payload['changed']['email']['new']);
    }

    public function test_username_change_uses_name_column(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['username'],
        ]);

        $user = User::factory()->create(['name' => 'old_handle']);
        $user->update(['name' => 'new_handle']);

        $delivery = WebhookDelivery::firstOrFail();
        $this->assertSame('old_handle', $delivery->payload['changed']['username']['old']);
        $this->assertSame('new_handle', $delivery->payload['changed']['username']['new']);
    }

    public function test_unrelated_field_does_not_dispatch(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email', 'username'],
        ]);

        $user = User::factory()->create();
        $user->update(['first_name' => 'Jane']); // not in allowlist

        Queue::assertNothingPushed();
        $this->assertSame(0, WebhookDelivery::count());
    }

    public function test_created_user_does_not_dispatch(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        App::factory()->for($owner, 'owner')->create([
            'first_party' => true,
            'webhook_url' => 'https://example.test/hook',
            'webhook_secret' => 'secret',
            'webhook_subscribed_fields' => ['email'],
        ]);

        User::factory()->create();

        Queue::assertNothingPushed();
    }
}
```

- [ ] **Step 2:** Run, expect failures.

```bash
php artisan test tests/Feature/Webhooks/UserUpdatedDispatchTest.php
```

- [ ] **Step 3:** Implement observer.

```php
<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Webhooks\UserFieldMap;
use App\Services\Webhooks\WebhookDispatcher;

class UserObserver
{
    public function __construct(private readonly WebhookDispatcher $dispatcher)
    {
    }

    public function updated(User $user): void
    {
        $changedColumns = array_keys($user->getChanges());
        $original = $user->getOriginal();

        $externalChanged = [];
        $oldValues = [];

        foreach (UserFieldMap::MAP as $external => $column) {
            if (in_array($column, $changedColumns, true)) {
                $externalChanged[] = $external;
                $oldValues[$external] = $original[$column] ?? null;
            }
        }

        if ($externalChanged === []) {
            return;
        }

        $this->dispatcher->dispatchUserUpdated($user, $oldValues, $externalChanged);
    }
}
```

- [ ] **Step 4:** Register in `AppServiceProvider::boot()`.

```php
use App\Models\User;
use App\Observers\UserObserver;

// inside boot():
User::observe(UserObserver::class);
```

- [ ] **Step 5:** Run tests.

```bash
php artisan test tests/Feature/Webhooks/UserUpdatedDispatchTest.php
```

- [ ] **Step 6:** Commit.

```bash
git add app/Observers/UserObserver.php app/Providers/AppServiceProvider.php tests/Feature/Webhooks/UserUpdatedDispatchTest.php
git commit -m "feat: dispatch user.updated webhooks via User observer"
```

---

## Phase 5 — Prune command + Horizon wiring

### Task 5.1: `PruneWebhookDeliveries` command — TDD

**Files:**
- Create: `app/Console/Commands/PruneWebhookDeliveries.php`
- Create: `tests/Feature/Webhooks/PruneWebhookDeliveriesTest.php`

- [ ] **Step 1:** Write failing test.

```php
<?php

namespace Tests\Feature\Webhooks;

use App\Models\App;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PruneWebhookDeliveriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_prunes_rows_older_than_seven_days(): void
    {
        $owner = User::factory()->create();
        $app = App::factory()->for($owner, 'owner')->create(['first_party' => true]);

        $old = WebhookDelivery::create([
            'id' => (string) Str::ulid(), 'app_id' => $app->id, 'event' => 'user.updated',
            'url' => 'https://x', 'payload' => [], 'signature' => 'v1,a', 'status' => 'delivered',
            'attempts' => 1, 'created_at' => now()->subDays(8),
        ]);
        $fresh = WebhookDelivery::create([
            'id' => (string) Str::ulid(), 'app_id' => $app->id, 'event' => 'user.updated',
            'url' => 'https://x', 'payload' => [], 'signature' => 'v1,a', 'status' => 'delivered',
            'attempts' => 1, 'created_at' => now()->subDays(6),
        ]);

        $this->artisan('webhooks:prune-deliveries')->assertSuccessful();

        $this->assertDatabaseMissing('webhook_deliveries', ['id' => $old->id]);
        $this->assertDatabaseHas('webhook_deliveries', ['id' => $fresh->id]);
    }
}
```

- [ ] **Step 2:** Run, expect failure.

- [ ] **Step 3:** Implement.

```php
<?php

namespace App\Console\Commands;

use App\Models\WebhookDelivery;
use Illuminate\Console\Command;

class PruneWebhookDeliveries extends Command
{
    protected $signature = 'webhooks:prune-deliveries';

    protected $description = 'Delete webhook_deliveries rows older than 7 days';

    public function handle(): int
    {
        $cutoff = now()->subDays(7);
        $deleted = WebhookDelivery::where('created_at', '<', $cutoff)->delete();
        $this->info("Pruned {$deleted} webhook deliveries older than {$cutoff->toDateTimeString()}.");
        return self::SUCCESS;
    }
}
```

- [ ] **Step 4:** Schedule it. In `bootstrap/app.php` (or `routes/console.php`, whichever the project uses):

```php
use Illuminate\Console\Scheduling\Schedule;

->withSchedule(function (Schedule $schedule) {
    $schedule->command('webhooks:prune-deliveries')->dailyAt('03:00');
})
```

If `bootstrap/app.php` doesn't already have `->withSchedule`, inspect and add it consistent with the Laravel version in use.

- [ ] **Step 5:** Run tests.

- [ ] **Step 6:** Commit.

```bash
git add app/Console/Commands/PruneWebhookDeliveries.php tests/Feature/Webhooks/PruneWebhookDeliveriesTest.php bootstrap/app.php
git commit -m "feat: prune webhook deliveries older than 7 days"
```

### Task 5.2: Register `webhooks` Horizon queue

**Files:**
- Modify: `config/horizon.php`

- [ ] **Step 1:** In every environment's supervisor that currently lists `'queue' => ['default']`, change to `['default', 'webhooks']`. Do this for `production`, `local`, and any other environment blocks in the file.

- [ ] **Step 2:** Verify config.

```bash
php artisan config:show horizon | head -50
```

- [ ] **Step 3:** Commit.

```bash
git add config/horizon.php
git commit -m "chore: add webhooks queue to Horizon supervisors"
```

---

## Phase 6 — Authorization + form requests

### Task 6.1: Extend `AppPolicy`

**Files:**
- Modify: `app/Policies/AppPolicy.php`

- [ ] **Step 1:** Add methods.

```php
public function manageWebhooks(User $user, App $app): bool
{
    return $this->update($user, $app) && $app->isFirstParty();
}

public function viewWebhookSecret(User $user, App $app): bool
{
    return $this->manageWebhooks($user, $app);
}
```

- [ ] **Step 2:** Commit.

```bash
git add app/Policies/AppPolicy.php
git commit -m "feat: add webhook policy methods"
```

### Task 6.2: New developer form requests

**Files:**
- Create: `app/Http/Requests/Developer/StoreAppRequest.php`
- Create: `app/Http/Requests/Developer/UpdateAppGeneralRequest.php`
- Create: `app/Http/Requests/Developer/UpdateAppOAuthRequest.php`
- Create: `app/Http/Requests/Developer/UpdateAppLogoutRequest.php`
- Create: `app/Http/Requests/Developer/UpdateAppWebhookRequest.php`
- (Deletion of `app/Http/Requests/Staff/StoreAppRequest.php` and `app/Http/Requests/Staff/UpdateAppRequest.php` is deferred to Task 7.1 so the repo stays compilable at every commit.)

- [ ] **Step 1:** Create `Developer/StoreAppRequest.php` — trimmed to three fields.

```php
<?php

namespace App\Http\Requests\Developer;

use App\Http\Controllers\Profile\Settings\AppsController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\App::class);
    }

    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'redirect_uri' => ['required', 'url', 'max:2000'],
            'scope' => ['nullable', 'array'],
            'scope.*' => ['required', 'string', Rule::notIn(AppsController::RESTRICTED_SCOPES)],
        ];
    }
}
```

- [ ] **Step 2:** Create `UpdateAppGeneralRequest.php`.

```php
<?php

namespace App\Http\Requests\Developer;

use App\Models\App;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppGeneralRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var App $app */
        $app = $this->route('app');
        return $this->user()->can('update', $app);
    }

    public function rules(): array
    {
        /** @var App $app */
        $app = $this->route('app');
        $isFirstParty = $app->isFirstParty();

        return array_merge([
            'client_name' => ['required', 'string', 'max:255'],
            'description' => [$isFirstParty ? 'nullable' : 'required', 'string', 'max:1000'],
            'app_url' => [$isFirstParty ? 'nullable' : 'required', 'url', 'max:2000'],
            'icon' => ['nullable', 'image', 'max:2048'],
        ], $isFirstParty ? [] : [
            'developer_name' => ['required', 'string', 'max:255'],
            'privacy_policy_url' => ['required', 'url', 'max:2000'],
            'terms_of_service_url' => ['required', 'url', 'max:2000'],
        ]);
    }
}
```

- [ ] **Step 3:** Create `UpdateAppOAuthRequest.php`.

```php
<?php

namespace App\Http\Requests\Developer;

use App\Http\Controllers\Profile\Settings\AppsController;
use App\Models\App;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppOAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var App $app */
        $app = $this->route('app');
        return $this->user()->can('update', $app);
    }

    public function rules(): array
    {
        return [
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'url', 'max:2000'],
            'scope' => ['nullable', 'array'],
            'scope.*' => ['required', 'string', Rule::notIn(AppsController::RESTRICTED_SCOPES)],
        ];
    }
}
```

- [ ] **Step 4:** Create `UpdateAppLogoutRequest.php`.

```php
<?php

namespace App\Http\Requests\Developer;

use App\Models\App;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppLogoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var App $app */
        $app = $this->route('app');
        return $this->user()->can('update', $app);
    }

    public function rules(): array
    {
        return [
            'post_logout_redirect_uris' => ['nullable', 'array'],
            'post_logout_redirect_uris.*' => ['required', 'url', 'max:2000'],
            'frontchannel_logout_uri' => ['nullable', 'url', 'max:2000'],
            'backchannel_logout_uri' => ['nullable', 'url', 'max:2000'],
        ];
    }
}
```

- [ ] **Step 5:** Create `UpdateAppWebhookRequest.php`.

```php
<?php

namespace App\Http\Requests\Developer;

use App\Models\App;
use App\Services\Webhooks\UserFieldMap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var App $app */
        $app = $this->route('app');
        return $this->user()->can('manageWebhooks', $app);
    }

    public function rules(): array
    {
        return [
            'webhook_url' => ['nullable', 'url', 'max:2000'],
            'webhook_event_name' => ['nullable', 'string', 'max:64'],
            'webhook_subscribed_fields' => ['nullable', 'array'],
            'webhook_subscribed_fields.*' => ['required', 'string', Rule::in(UserFieldMap::subscribableFields())],
        ];
    }
}
```

- [ ] **Step 6:** Leave the old `app/Http/Requests/Staff/{Store,Update}AppRequest.php` files in place for now — `AppsController` still references them. They will be deleted in Task 7.1 alongside the controller rewire so the tree compiles at every commit.

- [ ] **Step 7:** Commit. Everything compiles because we only added new files.

```bash
git add app/Http/Requests/Developer
git commit -m "feat: add developer form requests for per-section app updates"
```

---

## Phase 7 — Controller refactor

### Task 7.1: Refactor `AppsController` — split update, trim store, remove show/edit

**Files:**
- Modify: `app/Http/Controllers/Profile/Settings/AppsController.php`
- Delete: `app/Http/Requests/Staff/StoreAppRequest.php`
- Delete: `app/Http/Requests/Staff/UpdateAppRequest.php`

- [ ] **Step 1:** Replace the controller. Key changes:

- `store` uses `Developer\StoreAppRequest`, accepts only `client_name`, `redirect_uri` (singular), `scope`. First-party is inferred from `isStaff()`. Redirects to `developers.general`.
- `update` is removed and replaced with `updateGeneral` (`UpdateAppGeneralRequest`), `updateOAuth` (`UpdateAppOAuthRequest`), `updateLogout` (`UpdateAppLogoutRequest`).
- `show` and `edit` are removed (sections handle their own rendering).
- New methods `general`, `oauth`, `logout`, `credentials`, `danger` each return `Inertia::render('Settings/Apps/AppDetail/<Section>', [...])` with `formatApp($app)` as base prop.
- Factor common "format for frontend" logic into private `formatApp(App $app): array` (keep the existing method shape, add new fields).

- [ ] **Step 2:** Key code snippets (not a full re-paste — adapt from current file):

```php
public function store(\App\Http\Requests\Developer\StoreAppRequest $request)
{
    $validated = $request->validated();
    $firstParty = (bool) auth()->user()->isStaff();

    $data = [
        'client_name' => $validated['client_name'],
        'redirect_uris' => [$validated['redirect_uri']],
        'post_logout_redirect_uris' => [],
        'scope' => $validated['scope'] ?? ['openid'],
        'grant_types' => ['authorization_code', 'refresh_token'],
        'response_types' => ['code'],
        'token_endpoint_auth_method' => 'client_secret_post',
        'subject_type' => 'public',
    ];

    $app = auth()->user()->apps()->create([
        'data' => $data,
        'name' => $validated['client_name'],
        'description' => '',
        'first_party' => $firstParty,
    ]);

    // ... same secret extraction as before ...

    return redirect()->route('developers.general', $app);
}

public function general(App $app)
{
    Gate::authorize('view', $app);
    return Inertia::render('Settings/Apps/AppDetail/General', [
        'app' => $this->formatApp($app),
    ]);
}

public function updateGeneral(\App\Http\Requests\Developer\UpdateAppGeneralRequest $request, App $app)
{
    // mirror existing update() but only the general-scope fields
}

// oauth / logout / credentials / danger analogous
```

- [ ] **Step 3:** Update any references from `Staff\StoreAppRequest` / `Staff\UpdateAppRequest` to the new `Developer\` namespace.

- [ ] **Step 4:** Do NOT delete `regenerateSecret` and `destroy` — they move under `credentials` and `danger` sections respectively (routes still hit these controller methods).

- [ ] **Step 5:** Delete the now-unreferenced old request classes in the same commit so the tree compiles.

```bash
git rm app/Http/Requests/Staff/StoreAppRequest.php app/Http/Requests/Staff/UpdateAppRequest.php
```

- [ ] **Step 6:** Commit. Routes still reference the old `update`/`edit`/`show` names — that's wired up in Task 7.3; until then, tests touching those route names will fail, which is expected. Do not run the suite between Task 7.1 and Task 7.3.

```bash
git add app/Http/Controllers/Profile/Settings/AppsController.php app/Http/Requests/Staff
git commit -m "refactor: split AppsController update into per-section endpoints"
```

### Task 7.2: Create `AppWebhookController`

**Files:**
- Create: `app/Http/Controllers/Profile/Settings/AppWebhookController.php`

- [ ] **Step 1:** Implement.

```php
<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\UpdateAppWebhookRequest;
use App\Jobs\Webhooks\DeliverWebhook;
use App\Models\App;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\WebhookSigner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AppWebhookController extends Controller
{
    public function show(App $app)
    {
        Gate::authorize('manageWebhooks', $app);

        return Inertia::render('Settings/Apps/AppDetail/Webhooks', [
            'app' => [
                'id' => $app->id,
                'client_id' => $app->client_id,
                'first_party' => $app->isFirstParty(),
                'webhook_url' => $app->webhook_url,
                'webhook_event_name' => $app->webhook_event_name,
                'webhook_subscribed_fields' => $app->webhook_subscribed_fields ?? [],
                'has_secret' => (bool) $app->webhook_secret,
            ],
        ]);
    }

    public function update(UpdateAppWebhookRequest $request, App $app)
    {
        Gate::authorize('manageWebhooks', $app);

        $validated = $request->validated();

        $app->webhook_url = $validated['webhook_url'] ?? null;
        $app->webhook_event_name = $validated['webhook_event_name'] ?? null;
        $app->webhook_subscribed_fields = $validated['webhook_subscribed_fields'] ?? [];

        if ($app->webhook_url && ! $app->webhook_secret) {
            $app->webhook_secret = bin2hex(random_bytes(32));
        }

        $app->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('apps_webhook_saved')]);

        return redirect()->route('developers.webhooks', $app);
    }

    public function revealSecret(App $app)
    {
        Gate::authorize('viewWebhookSecret', $app);
        activity()->performedOn($app)->causedBy(auth()->user())->log('webhook.secret.revealed');
        return response()->json(['secret' => $app->webhook_secret]);
    }

    public function rotateSecret(App $app)
    {
        Gate::authorize('manageWebhooks', $app);
        $app->webhook_secret = bin2hex(random_bytes(32));
        $app->save();
        activity()->performedOn($app)->causedBy(auth()->user())->log('webhook.secret.rotated');
        return response()->json(['secret' => $app->webhook_secret]);
    }

    public function sendTest(App $app, WebhookSigner $signer)
    {
        Gate::authorize('manageWebhooks', $app);
        abort_unless($app->webhook_url && $app->webhook_secret, 422, 'Webhook URL and secret must be saved first.');

        $user = auth()->user();
        $payload = [
            'event' => 'user.updated',
            'id' => (string) Str::ulid(),
            'occurred_at' => now()->toIso8601String(),
            'subject' => (string) $user->getKey(),
            'changed' => [
                'email' => ['old' => $user->email, 'new' => $user->email],
            ],
            'test' => true,
        ];

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;
        $signature = $signer->sign($app->webhook_secret, $timestamp, $body);

        $delivery = WebhookDelivery::create([
            'id' => $payload['id'],
            'app_id' => $app->id,
            'event' => 'user.updated',
            'url' => $app->webhook_url,
            'payload' => $payload,
            'signature' => $signature,
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => now(),
        ]);

        DeliverWebhook::dispatch($delivery->id, $timestamp)->onQueue('webhooks');

        return response()->json(['delivery_id' => $delivery->id]);
    }

    public function deliveries(Request $request, App $app)
    {
        Gate::authorize('manageWebhooks', $app);

        $deliveries = $app->webhookDeliveries()
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($deliveries);
    }

    public function redeliver(App $app, WebhookDelivery $delivery, WebhookSigner $signer)
    {
        Gate::authorize('manageWebhooks', $app);
        abort_unless($delivery->app_id === $app->id, 404);

        $timestamp = now()->timestamp;
        $body = json_encode($delivery->payload, JSON_THROW_ON_ERROR);
        $signature = $signer->sign($app->webhook_secret, $timestamp, $body);

        $new = WebhookDelivery::create([
            'id' => (string) Str::ulid(),
            'app_id' => $app->id,
            'event' => $delivery->event,
            'url' => $app->webhook_url,
            'payload' => $delivery->payload,
            'signature' => $signature,
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => now(),
        ]);

        DeliverWebhook::dispatch($new->id, $timestamp)->onQueue('webhooks');

        return response()->json(['delivery_id' => $new->id]);
    }
}
```

- [ ] **Step 2:** Commit.

```bash
git add app/Http/Controllers/Profile/Settings/AppWebhookController.php
git commit -m "feat: add AppWebhookController"
```

### Task 7.3: Rewire routes

**Files:**
- Modify: `routes/apps/portal.php`

- [ ] **Step 1:** Replace the `developers.*` prefix group. Keep `index`, `create`, `store`, `destroy`, `regenerate-secret`, and `notification-types.*` routes intact. Remove `show`, `edit`, `update`. Add the new routes:

```php
Route::middleware('developer')->prefix('developers')->name('developers.')->group(function () {
    Route::get('/create', [AppsController::class, 'create'])->name('create');
    Route::post('/', [AppsController::class, 'store'])->name('store');

    Route::get('/{app}', fn (\App\Models\App $app) => redirect()->route('developers.general', $app))
        ->name('show');

    Route::get('/{app}/general', [AppsController::class, 'general'])->name('general');
    Route::put('/{app}/general', [AppsController::class, 'updateGeneral'])->name('general.update');

    Route::get('/{app}/oauth', [AppsController::class, 'oauth'])->name('oauth');
    Route::put('/{app}/oauth', [AppsController::class, 'updateOAuth'])->name('oauth.update');

    Route::get('/{app}/logout', [AppsController::class, 'logout'])->name('logout');
    Route::put('/{app}/logout', [AppsController::class, 'updateLogout'])->name('logout.update');

    Route::get('/{app}/credentials', [AppsController::class, 'credentials'])->name('credentials');
    Route::post('/{app}/regenerate-secret', [AppsController::class, 'regenerateSecret'])->name('regenerate-secret');

    Route::get('/{app}/webhooks', [\App\Http\Controllers\Profile\Settings\AppWebhookController::class, 'show'])->name('webhooks');
    Route::put('/{app}/webhooks', [\App\Http\Controllers\Profile\Settings\AppWebhookController::class, 'update'])->name('webhooks.update');
    Route::post('/{app}/webhooks/reveal-secret', [\App\Http\Controllers\Profile\Settings\AppWebhookController::class, 'revealSecret'])->name('webhooks.reveal-secret');
    Route::post('/{app}/webhooks/rotate-secret', [\App\Http\Controllers\Profile\Settings\AppWebhookController::class, 'rotateSecret'])->name('webhooks.rotate-secret');
    Route::post('/{app}/webhooks/test', [\App\Http\Controllers\Profile\Settings\AppWebhookController::class, 'sendTest'])->name('webhooks.test');
    Route::get('/{app}/webhooks/deliveries', [\App\Http\Controllers\Profile\Settings\AppWebhookController::class, 'deliveries'])->name('webhooks.deliveries');
    Route::post('/{app}/webhooks/deliveries/{delivery}/redeliver', [\App\Http\Controllers\Profile\Settings\AppWebhookController::class, 'redeliver'])->name('webhooks.redeliver');

    Route::get('/{app}/danger', [AppsController::class, 'danger'])->name('danger');
    Route::delete('/{app}', [AppsController::class, 'destroy'])->name('destroy');

    // existing notification-types routes unchanged
});
```

- [ ] **Step 2:** Run route:list and verify.

```bash
php artisan route:list --path=developers
```

- [ ] **Step 3:** Commit.

```bash
git add routes/apps/portal.php
git commit -m "feat: route developer portal to per-section endpoints"
```

### Task 7.4: Regression test sweep

- [ ] **Step 1:** Run the full backend test suite.

```bash
php artisan test
```

Expected: previously-passing tests still pass; any tests that hit the old `developers.edit`/`developers.update` routes by name will need updating (next task).

### Task 7.5: Update existing tests touching old routes

- [ ] **Step 1:** Grep for old route names.

```bash
rg "developers\.(edit|update|show)" tests/ -l
```

- [ ] **Step 2:** For each hit, switch to `developers.general`, `developers.general.update`, etc., as appropriate. If a test was checking a prop that no longer exists on the general page, move the assertion to the section that actually owns that field.

- [ ] **Step 3:** Run tests.

```bash
php artisan test
```

- [ ] **Step 4:** Commit.

```bash
git add tests/
git commit -m "test: update developer app tests for per-section routes"
```

---

## Phase 8 — Frontend: simplified create form

### Task 8.1: Shrink `AppCreate.vue`

**Files:**
- Modify: `resources/js/Pages/Settings/Apps/AppCreate.vue`

- [ ] **Step 1:** Replace the form. Three fields: Name, Redirect URL (single string, not array), Scopes. On submit post to `developers.store` with `{ client_name, redirect_uri, scope }`. Backend redirects to `/developers/{app}/general`, so no `onSuccess` branching is needed.

```vue
<template>
    <Head :title="$t('apps_create')" />
    <div class="grid md:grid-cols-3 gap-6 md:gap-10">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('apps_create') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('apps_create_description') }}</p>
        </div>
        <div class="md:col-span-2">
            <form class="space-y-6" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_name') }}</label>
                    <Input v-model="form.client_name" type="text" required class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.client_name" class="text-xs text-destructive mt-1">{{ form.errors.client_name }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_redirect_uri') }}</label>
                    <Input v-model="form.redirect_uri" type="url" placeholder="https://" required class="bg-white dark:bg-primary-950" />
                    <p v-if="form.errors.redirect_uri" class="text-xs text-destructive mt-1">{{ form.errors.redirect_uri }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ $t('apps_scopes') }}</label>
                    <div class="space-y-2">
                        <div v-for="scope in availableScopes" :key="scope" class="flex items-center gap-2">
                            <Checkbox
                                :id="'scope-' + scope"
                                :model-value="form.scope.includes(scope)"
                                @update:model-value="toggleScope(scope)"
                            />
                            <label :for="'scope-' + scope" class="text-sm">{{ scope }}</label>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button type="submit" :disabled="form.processing">{{ $t('apps_create') }}</Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('developers.index')">{{ $t('cancel') }}</Link>
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Checkbox } from '@/Components/ui/checkbox'

const props = defineProps({ availableScopes: Array })

const form = useForm({
    client_name: '',
    redirect_uri: '',
    scope: ['openid'],
})

function toggleScope(scope) {
    const i = form.scope.indexOf(scope)
    i === -1 ? form.scope.push(scope) : form.scope.splice(i, 1)
}
function submit() { form.post(route('developers.store')) }
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default { layout: AccountLayout }
</script>
```

- [ ] **Step 2:** Add the `apps_redirect_uri` i18n key to the project's language files. Grep for `apps_name` to find the file(s) and add the new key next to it.

- [ ] **Step 3:** Commit.

```bash
git add resources/js/Pages/Settings/Apps/AppCreate.vue lang/
git commit -m "feat: simplify app create form to name, redirect URL, scopes"
```

---

## Phase 9 — Frontend: detail layout + section pages

### Task 9.1: Shared sidebar constant + layout component

**Files:**
- Create: `resources/js/Pages/Settings/Apps/AppDetail/sidebar.js`
- Create: `resources/js/Pages/Settings/Apps/AppDetail/Layout.vue`

- [ ] **Step 1:** Create `sidebar.js`.

```js
export function sidebarItems(app) {
    const items = [
        { key: 'general', label: 'General', route: 'developers.general' },
        { key: 'oauth', label: 'OAuth', route: 'developers.oauth' },
        { key: 'logout', label: 'Logout', route: 'developers.logout' },
        { key: 'credentials', label: 'Credentials', route: 'developers.credentials' },
    ]
    if (app.first_party) {
        items.push({ key: 'webhooks', label: 'Webhooks', route: 'developers.webhooks' })
    }
    items.push({ key: 'danger', label: 'Danger zone', route: 'developers.danger' })
    return items
}
```

- [ ] **Step 2:** Create `Layout.vue`.

```vue
<template>
    <div class="grid md:grid-cols-[200px_1fr] gap-8">
        <nav class="space-y-1">
            <Link
                v-for="item in items"
                :key="item.key"
                :href="route(item.route, app.id)"
                :class="[
                    'block rounded-md px-3 py-2 text-sm',
                    activeKey === item.key ? 'bg-gray-100 dark:bg-primary-900 font-medium' : 'hover:bg-gray-50 dark:hover:bg-primary-900/50',
                ]"
            >
                {{ item.label }}
            </Link>
        </nav>
        <div>
            <slot />
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { sidebarItems } from './sidebar.js'

const props = defineProps({ app: Object, activeKey: String })
const items = computed(() => sidebarItems(props.app))
</script>
```

- [ ] **Step 3:** Commit.

```bash
git add resources/js/Pages/Settings/Apps/AppDetail/Layout.vue resources/js/Pages/Settings/Apps/AppDetail/sidebar.js
git commit -m "feat: add AppDetail sidebar layout"
```

### Task 9.2: `General.vue` section page

**Files:**
- Create: `resources/js/Pages/Settings/Apps/AppDetail/General.vue`

- [ ] **Step 1:** Implement. Use the existing `AppEdit.vue` fields relevant to "general" (name, icon, description, app URL; for third-party also developer name, privacy/ToS URLs).

Skeleton:

```vue
<template>
    <Head :title="app.client_name + ' · General'" />
    <AppDetailLayout :app="app" active-key="general">
        <form class="space-y-6" @submit.prevent="submit">
            <!-- name / icon / description / app_url / third-party fields -->
            <Button type="submit" :disabled="form.processing">{{ $t('apps_save') }}</Button>
        </form>
    </AppDetailLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import AppDetailLayout from './Layout.vue'

const props = defineProps({ app: Object })
const form = useForm({
    client_name: props.app.client_name,
    description: props.app.description || '',
    app_url: props.app.app_url || '',
    developer_name: props.app.developer_name || '',
    privacy_policy_url: props.app.privacy_policy_url || '',
    terms_of_service_url: props.app.terms_of_service_url || '',
    icon: null,
})
function submit() {
    form.post(route('developers.general.update', props.app.id), { _method: 'put', forceFormData: true })
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default { layout: AccountLayout }
</script>
```

- [ ] **Step 2:** Commit.

```bash
git add resources/js/Pages/Settings/Apps/AppDetail/General.vue
git commit -m "feat: add AppDetail General section"
```

### Task 9.3: `OAuth.vue` section page

**Files:**
- Create: `resources/js/Pages/Settings/Apps/AppDetail/OAuth.vue`

- [ ] **Step 1:** Implement redirect URIs multi-input (from existing `AppEdit.vue`) + scopes checkbox list. Submits `PUT developers.oauth.update`.

- [ ] **Step 2:** Commit.

```bash
git add resources/js/Pages/Settings/Apps/AppDetail/OAuth.vue
git commit -m "feat: add AppDetail OAuth section"
```

### Task 9.4: `Logout.vue` section page

**Files:**
- Create: `resources/js/Pages/Settings/Apps/AppDetail/Logout.vue`

- [ ] **Step 1:** Implement post-logout URIs + front/backchannel URI fields. Submits `PUT developers.logout.update`.

- [ ] **Step 2:** Commit.

```bash
git add resources/js/Pages/Settings/Apps/AppDetail/Logout.vue
git commit -m "feat: add AppDetail Logout section"
```

### Task 9.5: `Credentials.vue` section page

**Files:**
- Create: `resources/js/Pages/Settings/Apps/AppDetail/Credentials.vue`

- [ ] **Step 1:** Show `client_id`, display (and copy) the one-time client secret if present in props, and host the "Regenerate secret" button/dialog. This is the existing `AppEdit.vue` regenerate-secret behavior lifted into its own page.

- [ ] **Step 2:** Commit.

```bash
git add resources/js/Pages/Settings/Apps/AppDetail/Credentials.vue
git commit -m "feat: add AppDetail Credentials section"
```

### Task 9.6: `Danger.vue` section page

**Files:**
- Create: `resources/js/Pages/Settings/Apps/AppDetail/Danger.vue`

- [ ] **Step 1:** Delete button + confirmation dialog, POSTs to `developers.destroy`.

- [ ] **Step 2:** Commit.

```bash
git add resources/js/Pages/Settings/Apps/AppDetail/Danger.vue
git commit -m "feat: add AppDetail Danger section"
```

### Task 9.7: Remove obsolete pages + update Developers list link

**Files:**
- Delete: `resources/js/Pages/Settings/Apps/AppShow.vue`
- Delete: `resources/js/Pages/Settings/Apps/AppEdit.vue`
- Modify: `resources/js/Pages/Developers.vue`

- [ ] **Step 1:** Delete the old files.

```bash
git rm resources/js/Pages/Settings/Apps/AppShow.vue resources/js/Pages/Settings/Apps/AppEdit.vue
```

- [ ] **Step 2:** Update `Developers.vue` — the per-app `Link` should point to `developers.general` (or `developers.show`, which redirects there — either works; prefer the explicit one).

- [ ] **Step 3:** Build frontend and verify it compiles.

```bash
npm run build
```

- [ ] **Step 4:** Commit.

```bash
git add resources/js/Pages/Developers.vue resources/js/Pages/Settings/Apps/
git commit -m "refactor: remove AppShow/AppEdit in favor of AppDetail sections"
```

---

## Phase 10 — Frontend: Webhooks section + deliveries UI

### Task 10.1: `Webhooks.vue` — form and controls

**Files:**
- Create: `resources/js/Pages/Settings/Apps/AppDetail/Webhooks.vue`

- [ ] **Step 1:** Implement the form: URL, event name (free text), two checkboxes (`email`, `username`), reveal/rotate buttons, send-test button (disabled if URL not yet saved).

Skeleton:

```vue
<template>
    <Head :title="app.client_name + ' · Webhooks'" />
    <AppDetailLayout :app="app" active-key="webhooks">
        <form class="space-y-6" @submit.prevent="submit">
            <div>
                <label class="text-sm font-medium block mb-1">Webhook URL</label>
                <Input v-model="form.webhook_url" type="url" placeholder="https://..." />
            </div>
            <div>
                <label class="text-sm font-medium block mb-1">Event</label>
                <Input v-model="form.webhook_event_name" placeholder="user.updated" />
            </div>
            <div>
                <label class="text-sm font-medium block mb-1">Subscribed fields</label>
                <div class="flex items-center gap-2">
                    <Checkbox :model-value="form.webhook_subscribed_fields.includes('email')" @update:model-value="toggle('email')" /> <span>email</span>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox :model-value="form.webhook_subscribed_fields.includes('username')" @update:model-value="toggle('username')" /> <span>username</span>
                </div>
            </div>

            <div v-if="app.has_secret">
                <label class="text-sm font-medium block mb-1">Signing secret</label>
                <div class="flex gap-2 items-center">
                    <Input :model-value="revealedSecret || '••••••••••••'" readonly class="font-mono" />
                    <Button type="button" variant="outline" @click="reveal">Reveal</Button>
                    <Button type="button" variant="destructive" @click="rotate">Rotate</Button>
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">Save</Button>
                <Button type="button" variant="outline" :disabled="!app.webhook_url || !app.has_secret" @click="sendTest">Send test delivery</Button>
            </div>
        </form>

        <DeliveriesTable :app-id="app.id" ref="deliveriesTable" class="mt-10" />
    </AppDetailLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Checkbox } from '@/Components/ui/checkbox'
import AppDetailLayout from './Layout.vue'
import DeliveriesTable from './DeliveriesTable.vue'

const props = defineProps({ app: Object })
const form = useForm({
    webhook_url: props.app.webhook_url || '',
    webhook_event_name: props.app.webhook_event_name || 'user.updated',
    webhook_subscribed_fields: [...(props.app.webhook_subscribed_fields || [])],
})
const revealedSecret = ref(null)
const deliveriesTable = ref(null)

function toggle(f) {
    const i = form.webhook_subscribed_fields.indexOf(f)
    i === -1 ? form.webhook_subscribed_fields.push(f) : form.webhook_subscribed_fields.splice(i, 1)
}

function submit() { form.put(route('developers.webhooks.update', props.app.id)) }

async function reveal() {
    const r = await fetch(route('developers.webhooks.reveal-secret', props.app.id), { method: 'POST', headers: csrf() })
    revealedSecret.value = (await r.json()).secret
}
async function rotate() {
    if (!confirm('Rotate webhook secret? Existing signatures will stop verifying.')) return
    const r = await fetch(route('developers.webhooks.rotate-secret', props.app.id), { method: 'POST', headers: csrf() })
    revealedSecret.value = (await r.json()).secret
}
async function sendTest() {
    await fetch(route('developers.webhooks.test', props.app.id), { method: 'POST', headers: csrf() })
    deliveriesTable.value?.reload()
}
function csrf() {
    return { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
}
</script>

<script>
import AccountLayout from '@/Layouts/AccountLayout.vue'
export default { layout: AccountLayout }
</script>
```

- [ ] **Step 2:** Commit.

```bash
git add resources/js/Pages/Settings/Apps/AppDetail/Webhooks.vue
git commit -m "feat: add AppDetail Webhooks section form"
```

### Task 10.2: `DeliveriesTable.vue`

**Files:**
- Create: `resources/js/Pages/Settings/Apps/AppDetail/DeliveriesTable.vue`

- [ ] **Step 1:** Fetches `GET developers.webhooks.deliveries` on mount, renders paginated table (When / Event / Status / HTTP / Attempts / Actions), row-click expands to show full JSON payload + signature + response body + error + `next_retry_at`. Actions column has a "Redeliver" button calling `POST developers.webhooks.redeliver`.

Expose `reload()` via `defineExpose({ reload })` so the parent Webhooks page can refresh after "Send test delivery" and "Save".

- [ ] **Step 2:** Commit.

```bash
git add resources/js/Pages/Settings/Apps/AppDetail/DeliveriesTable.vue
git commit -m "feat: add webhook deliveries table with redeliver"
```

---

## Phase 11 — Documentation

### Task 11.1: `webhooks.md` docs page

**Files:**
- Create: `docs/docs/identity/integration/webhooks.md`
- Modify: `docs/docs/identity/integration/build-an-application.md`
- Modify: `docs/sidebars.ts`

- [ ] **Step 1:** Write `webhooks.md`. Sections:

1. **What are webhooks** — one paragraph. First-party only.
2. **Enabling webhooks** — point developers to the Webhooks section in the developer portal.
3. **Events** — `user.updated` only.
4. **Subscribing to fields** — `email`, `username`.
5. **Payload schema** — annotated example copied from `WebhookSigner` test vectors / `WebhookDispatcherTest`.
6. **Headers** — full list with meaning of each.
7. **Signing algorithm** — prose + PHP snippet + Node snippet that match `WebhookSigner::verify` behavior (HMAC-SHA-256 over `timestamp + "." + raw_body`, `v1,` prefix, constant-time compare, 5-minute replay window).
8. **Retries** — table of the 6 backoff intervals.
9. **Delivery history** — 7-day retention, visible in the portal, redeliver action.
10. **Testing** — how to use "Send test delivery".
11. **Troubleshooting** — common 4xx/5xx causes.

- [ ] **Step 2:** Add a paragraph + link in `build-an-application.md`.

- [ ] **Step 3:** Register in `docs/sidebars.ts` under the Integration category (find the existing entries for `getting-started`, `build-an-application`, etc., and add `identity/integration/webhooks`).

- [ ] **Step 4:** Verify docs build.

```bash
cd docs && npm run build && cd ..
```

- [ ] **Step 5:** Commit.

```bash
git add docs/docs/identity/integration/webhooks.md docs/docs/identity/integration/build-an-application.md docs/sidebars.ts
git commit -m "docs: document first-party user.updated webhooks"
```

---

## Phase 12 — Integration tests across developer portal

### Task 12.1: `AppCreateTest` — simplified create form

**Files:**
- Create: `tests/Feature/Developer/AppCreateTest.php`

- [ ] **Step 1:** Tests:

- Developer can create an app with only `client_name`, `redirect_uri`, `scope`.
- Extra fields (`description`, `app_url`, etc.) are silently ignored.
- Validation fails if `client_name` or `redirect_uri` missing.
- Staff-created apps get `first_party = true` automatically.
- Non-staff apps get `first_party = false` regardless of request payload.
- Successful create redirects to `developers.general`.

- [ ] **Step 2:** Run and fix until green. Commit.

```bash
git add tests/Feature/Developer/AppCreateTest.php
git commit -m "test: cover simplified app create flow"
```

### Task 12.2: `AppDetailSectionsTest`

**Files:**
- Create: `tests/Feature/Developer/AppDetailSectionsTest.php`

- [ ] **Step 1:** Tests:

- Each of `general`, `oauth`, `logout`, `credentials`, `danger` renders the expected Inertia component for the owner.
- Non-owner gets 403.
- `PUT developers.general.update` persists name/description/app URL.
- `PUT developers.oauth.update` persists redirect URIs + scope and rejects restricted scopes with 422.
- `PUT developers.logout.update` persists post-logout/front/back URIs.

- [ ] **Step 2:** Run, fix, commit.

```bash
git add tests/Feature/Developer/AppDetailSectionsTest.php
git commit -m "test: cover AppDetail section routes and updates"
```

### Task 12.3: `AppWebhookControllerTest`

**Files:**
- Create: `tests/Feature/Developer/AppWebhookControllerTest.php`

- [ ] **Step 1:** Tests:

- Webhooks section returns 403 for a third-party app owner.
- Webhooks section returns 200 for a first-party app owner.
- `PUT webhooks.update` persists url/fields/event name and seeds a secret on first save.
- `PUT webhooks.update` with `webhook_url` for a third-party app returns 403 (policy denies authorize).
- `POST reveal-secret` returns plaintext for authorized users, 403 otherwise, writes an activity log entry (`activity_log` table row with `description='webhook.secret.revealed'`).
- `POST rotate-secret` issues a new secret; a signature built from the old secret no longer verifies via `WebhookSigner`.
- `POST test` 422s when no URL/secret, creates a delivery + dispatches a job when saved.
- `GET deliveries` lists recent rows filtered by app, paginated.
- `POST deliveries/{id}/redeliver` creates a new delivery row with the same payload and dispatches.

- [ ] **Step 2:** Run, fix, commit.

```bash
git add tests/Feature/Developer/AppWebhookControllerTest.php
git commit -m "test: cover AppWebhookController endpoints"
```

---

## Phase 13 — Final verification

### Task 13.1: Full test suite + manual smoke

- [ ] **Step 1:** Run the full backend test suite.

```bash
php artisan test
```

Expected: all green.

- [ ] **Step 2:** Build frontend.

```bash
npm run build
```

Expected: success.

- [ ] **Step 3:** Build docs.

```bash
cd docs && npm run build && cd ..
```

Expected: success.

- [ ] **Step 4:** Manual smoke walkthrough (record findings, do not commit anything new unless bugs are found):

1. Log in as a developer. Go to `/developers`. Create an app with just name/redirect/scope.
2. Land on General page. Fill description + app URL, save.
3. Visit OAuth, add a second redirect URI, save.
4. Visit Credentials, regenerate the client secret.
5. For a first-party app, visit Webhooks, set URL + subscribe to `email`, save.
6. Click Reveal, confirm secret appears. Click Rotate, confirm value changes.
7. Set up a local echo server (e.g. `nc -l 9000`), point webhook URL at it, click Send test delivery, verify the delivery row appears in the table with correct response.
8. Change the test user's email via the profile flow, confirm a new row appears.
9. Click Redeliver on an old row, confirm a new delivery is created.
10. Visit Danger zone, delete a throwaway test app.

- [ ] **Step 5:** If all green, the plan is complete.

---

## Notes for the engineer

- **Don't conflate `users.username` with `users.name`.** Read `app/Services/Webhooks/UserFieldMap.php` first. External-facing field is `username`; the DB column is `name`. Everywhere you touch webhooks, route field names through `UserFieldMap`.
- **Never log secrets.** `revealSecret` is POST (not GET) specifically so secrets don't land in access logs; don't add GET fallbacks.
- **The `Spatie\Activitylog` package is already installed.** Use `activity()->performedOn($app)->causedBy(auth()->user())->log(...)` — no new setup.
- **The `developer` middleware already gates the route group**; don't re-check `$user->is_developer` inside controllers.
- **Horizon already runs**, but any config changes require a Horizon reload in the running environment.
- **Spec is the source of truth.** If implementation ever diverges, update the spec in the same commit that changes the code.
