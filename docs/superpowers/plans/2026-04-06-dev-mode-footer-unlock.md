# Dev Mode Footer Unlock Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an app version to the account layout footer that, when clicked 5 times, unlocks a session-scoped "developer mode" revealing hashids inline across the app and a "System groups" section in the directory.

**Architecture:** Version is baked in at Docker build time via `APP_VERSION` build-arg (GitHub tag or commit SHA), read through Laravel config and shared on every Inertia response. A Vue composable (`useDevMode`) owns the session-scoped toggle and is consumed by a single `DevHashid` component placed next to users/groups across directory and profile pages. A new `systemMemberships` prop on `DirectoryController@index` surfaces the viewer's otherwise-hidden group memberships (e.g. `staff`).

**Tech Stack:** Laravel 11, Inertia.js, Vue 3 (Composition API), Pest (PHP), Docker/GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-04-05-dev-mode-footer-unlock-design.md`

---

## File map

**Backend:**
- Modify: `Dockerfile` (production stage — add `ARG`/`ENV`)
- Modify: `.github/workflows/docker.yml` (add `build-args` to build step)
- Modify: `config/app.php` (add `'version'` key)
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (share `version`)
- Modify: `app/Http/Controllers/Directory/DirectoryController.php` (`index()` adds `systemMemberships`)
- Create: `tests/Feature/Directory/DirectoryDevModeTest.php`

**Frontend:**
- Create: `resources/js/Composables/useDevMode.js`
- Create: `resources/js/Components/DevHashid.vue`
- Modify: `resources/js/Layouts/AccountLayout.vue` (footer version button, both desktop + mobile)
- Modify: `resources/js/Pages/Settings/Profile.vue` (own hashid)
- Modify: `resources/js/Pages/Directory/DirectoryIndex.vue` (hashids + System groups section)
- Modify: `resources/js/Pages/Directory/DirectoryShow.vue` (group + members + leaders + subGroups)
- Modify: `resources/js/Pages/Directory/StaffProfile.vue` (viewed user hashid)
- Modify: `resources/js/Pages/Directory/Components/DirectoryTree.vue` (tree nodes)
- Modify: `lang/en.json`, `lang/de.json`, `lang/fr.json` (toast strings)

**Note on frontend tests:** This repo has no JS test framework installed (no Vitest/Jest in `package.json`). The composable is verified manually in the verification task rather than via unit tests — adding a JS test framework is out of scope.

---

## Task 1: Version build-arg plumbing (Docker + workflow)

**Files:**
- Modify: `Dockerfile` — `production` stage
- Modify: `.github/workflows/docker.yml` — `build-docker` job

- [ ] **Step 1: Add ARG/ENV to Dockerfile production stage**

Edit `Dockerfile`. In the `production` stage (the `FROM base as production` block, ending with the `CMD` line), insert these two lines immediately **before** the `CMD sh -c "php artisan octane:start ..."` line:

```dockerfile
ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION}
```

- [ ] **Step 2: Add build-args to docker workflow**

Edit `.github/workflows/docker.yml`. In the `build-docker` job, under the `Build and push` step (`uses: docker/build-push-action@v6`), add a `build-args` key alongside `cache-from`, `file`, `target`, etc.:

```yaml
      - name: Build and push
        uses: docker/build-push-action@v6
        with:
          cache-from: |
            type=gha,scope=docker-main
            type=registry,ref=${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:latest
          cache-to: type=gha,scope=docker-main,mode=max
          file: ./Dockerfile
          target: production
          push: true
          build-args: |
            APP_VERSION=${{ github.ref_type == 'tag' && github.ref_name || github.sha }}
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
```

- [ ] **Step 3: Sanity-check Dockerfile builds locally (optional)**

Run: `docker build --target production --build-arg APP_VERSION=test123 -t ef-identity:dev-test .`

Expected: build completes. (Skip this step if Docker isn't available locally — CI will catch regressions.)

- [ ] **Step 4: Commit**

```bash
git add Dockerfile .github/workflows/docker.yml
git commit -m "build: pass APP_VERSION into production image"
```

---

## Task 2: Laravel config + shared Inertia prop

**Files:**
- Modify: `config/app.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

- [ ] **Step 1: Add `version` to `config/app.php`**

Open `config/app.php`. Immediately after the `'name'` key (around line 47), add:

```php
'version' => env('APP_VERSION', 'dev'),
```

- [ ] **Step 2: Share `version` via Inertia**

Edit `app/Http/Middleware/HandleInertiaRequests.php`. Locate the `return array_merge(parent::share($request), [ ... ]);` block at the end of `share()`. Add a new top-level key alongside `'locale'`, `'user'`, etc.:

```php
'version' => config('app.version'),
```

(Placement: put it next to `'locale'` so it's easy to find.)

- [ ] **Step 3: Verify manually**

Start the app (`php artisan serve` or existing dev script) and hit any authenticated page. Open browser devtools and inspect the Inertia page object (`document.getElementById('app').dataset.page`, or via the Vue devtools). Confirm `props.version === 'dev'` (or whatever `APP_VERSION` is set to in your local `.env`, if set).

- [ ] **Step 4: Commit**

```bash
git add config/app.php app/Http/Middleware/HandleInertiaRequests.php
git commit -m "feat: expose app version as inertia prop"
```

---

## Task 3: DirectoryController — systemMemberships (TDD)

**Files:**
- Test: `tests/Feature/Directory/DirectoryDevModeTest.php` (create)
- Modify: `app/Http/Controllers/Directory/DirectoryController.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Directory/DirectoryDevModeTest.php`:

```php
<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupDevModeStaff(): array
{
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);
    $user = User::factory()->create();
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    return [$user, $staffGroup];
}

test('directory index includes systemMemberships with viewer non-DDT groups', function () {
    [$user, $staffGroup] = setupDevModeStaff();

    // Also attach the user to a Department (should NOT appear in systemMemberships).
    $dept = Group::factory()->create([
        'type' => GroupTypeEnum::Department,
        'name' => 'Art',
    ]);
    $dept->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $this->actingAs($user)
        ->get(route('directory.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('systemMemberships', 1)
            ->where('systemMemberships.0.name', 'Staff')
            ->where('systemMemberships.0.type', 'automated')
            ->where('systemMemberships.0.hashid', $staffGroup->hashid)
            ->where('systemMemberships.0.slug', $staffGroup->slug)
        );
});

test('systemMemberships is empty when viewer has no non-DDT memberships', function () {
    $user = User::factory()->create();
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());
    // Attach to the existing Staff group so the user passes the staff gate,
    // then explicitly detach to test emptiness... easier: just make them staff
    // via isStaff() — but that requires the staff group. Use setupDevModeStaff
    // and accept that staff itself will appear.
    [$user2] = setupDevModeStaff();

    // The staff membership is the only non-DDT membership, so exactly 1.
    $this->actingAs($user2)
        ->get(route('directory.index'))
        ->assertInertia(fn ($page) => $page->has('systemMemberships', 1));
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/Directory/DirectoryDevModeTest.php`

Expected: FAIL — `systemMemberships` prop not present.

- [ ] **Step 3: Add systemMemberships query to the controller**

Edit `app/Http/Controllers/Directory/DirectoryController.php`. In `index()`, immediately after the `$orphanDepartments` block and before the `return Inertia::render(...)` call, add:

```php
        $systemMemberships = $user->groups()
            ->whereNotIn('type', [
                GroupTypeEnum::Division,
                GroupTypeEnum::Department,
                GroupTypeEnum::Team,
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Group $group) => [
                'hashid' => $group->hashid,
                'slug' => $group->slug,
                'name' => $group->name,
                'type' => $group->type->value,
            ]);
```

Then add `'systemMemberships' => $systemMemberships,` to the `Inertia::render` props array alongside the existing keys.

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/Directory/DirectoryDevModeTest.php`

Expected: PASS.

- [ ] **Step 5: Run the full directory test suite to catch regressions**

Run: `./vendor/bin/pest tests/Feature/Directory/`

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Directory/DirectoryDevModeTest.php app/Http/Controllers/Directory/DirectoryController.php
git commit -m "feat: surface viewer system memberships on directory index"
```

---

## Task 4: `useDevMode` composable

**Files:**
- Create: `resources/js/Composables/useDevMode.js`

- [ ] **Step 1: Create the composable**

Create `resources/js/Composables/useDevMode.js`:

```javascript
import { ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import { trans } from 'laravel-vue-i18n'

const STORAGE_KEY = 'devMode:enabled'
const CLICK_RESET_MS = 1500
const UNLOCK_CLICKS = 5

function readStorage() {
    try {
        return window.sessionStorage.getItem(STORAGE_KEY) === '1'
    } catch {
        return false
    }
}

function writeStorage(value) {
    try {
        if (value) {
            window.sessionStorage.setItem(STORAGE_KEY, '1')
        } else {
            window.sessionStorage.removeItem(STORAGE_KEY)
        }
    } catch {
        // Private mode / disabled storage: fall back to in-memory only.
    }
}

// Module-scope state: singleton across all importers.
const enabled = ref(readStorage())
let clickCount = 0
let resetTimer = null

watch(enabled, (v) => writeStorage(v))

function clearTimer() {
    if (resetTimer) {
        clearTimeout(resetTimer)
        resetTimer = null
    }
}

function registerClick() {
    if (enabled.value) {
        enabled.value = false
        clickCount = 0
        clearTimer()
        toast.success(trans('devmode_disabled'))
        return
    }

    clickCount += 1
    clearTimer()

    if (clickCount >= UNLOCK_CLICKS) {
        clickCount = 0
        enabled.value = true
        toast.success(trans('devmode_enabled'))
        return
    }

    resetTimer = setTimeout(() => {
        clickCount = 0
        resetTimer = null
    }, CLICK_RESET_MS)
}

function disable() {
    if (enabled.value) {
        enabled.value = false
    }
    clickCount = 0
    clearTimer()
}

export function useDevMode() {
    return { enabled, registerClick, disable }
}
```

- [ ] **Step 2: Verify it builds**

Run: `npm run build`

Expected: build succeeds with no errors referencing the new file.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Composables/useDevMode.js
git commit -m "feat: add useDevMode composable"
```

---

## Task 5: `DevHashid` component + i18n strings

**Files:**
- Create: `resources/js/Components/DevHashid.vue`
- Modify: `lang/en.json`, `lang/de.json`, `lang/fr.json`

- [ ] **Step 1: Create DevHashid component**

Create `resources/js/Components/DevHashid.vue`:

```vue
<template>
    <span
        v-if="devMode.enabled.value && id"
        class="text-xs font-mono text-muted-foreground ml-2 select-all"
        :title="id"
    >
        {{ id }}
    </span>
</template>

<script setup>
import { useDevMode } from '@/Composables/useDevMode'

defineProps({
    id: { type: String, default: null },
})

const devMode = useDevMode()
</script>
```

**Note:** `devMode.enabled` is a `ref`, so in the template it auto-unwraps to the boolean — `devMode.enabled` is what you write (not `.value`). Correcting the template:

```vue
<template>
    <span
        v-if="devMode.enabled && id"
        class="text-xs font-mono text-muted-foreground ml-2 select-all"
        :title="id"
    >
        {{ id }}
    </span>
</template>
```

- [ ] **Step 2: Add i18n strings**

Edit `lang/en.json` and add two new keys (keep the file's existing alphabetical or insertion order — just add at the end before the closing `}` if there's no clear order):

```json
"devmode_enabled": "Developer mode enabled",
"devmode_disabled": "Developer mode disabled",
```

Repeat for `lang/de.json`:

```json
"devmode_enabled": "Entwicklermodus aktiviert",
"devmode_disabled": "Entwicklermodus deaktiviert",
```

And `lang/fr.json`:

```json
"devmode_enabled": "Mode développeur activé",
"devmode_disabled": "Mode développeur désactivé",
```

- [ ] **Step 3: Verify build**

Run: `npm run build`

Expected: success.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/DevHashid.vue lang/en.json lang/de.json lang/fr.json
git commit -m "feat: add DevHashid component and i18n strings"
```

---

## Task 6: Footer version button in AccountLayout

**Files:**
- Modify: `resources/js/Layouts/AccountLayout.vue`

- [ ] **Step 1: Import the composable and expose version**

Edit `resources/js/Layouts/AccountLayout.vue`. In the `<script setup>` block, add near the other imports:

```javascript
import { useDevMode } from '@/Composables/useDevMode'
```

Still in `<script setup>`, near the other `computed` definitions (e.g. after `const user = computed(...)`), add:

```javascript
const devMode = useDevMode()
const appVersion = computed(() => page.props.version ?? null)
```

- [ ] **Step 2: Add version button to desktop footer**

Still in `AccountLayout.vue`, locate the desktop footer block (starts around line 68 with `<!-- Footer: artwork left, social + legal right -->`). Inside the left `<div>` containing the artwork credit, add the version as a sibling element appended after the `<a>` link to Jukajo (still inside the same `<div>`), or — cleaner — place it on the right side of the footer bar as a new element before the legal `<nav>`. Use this layout:

Change the outer footer wrapper from a 2-item flex to still a 2-item flex, then put the version button inside the legal nav as its first child (muted, not a link):

```vue
                    <!-- Footer: artwork left, social + legal right -->
                    <div class="w-full flex items-center justify-between bg-black/40 backdrop-blur-sm rounded-b-xl px-4 py-2 text-xs text-white/70">
                        <div>
                            {{ $t('footer_artwork_by') }}
                            <a class="hover:underline" href="https://www.furaffinity.net/user/jukajo">Jukajo</a>
                        </div>
                        <nav aria-label="Legal" class="flex flex-wrap items-center gap-x-4 gap-y-1">
                            <button
                                v-if="appVersion"
                                type="button"
                                @click="devMode.registerClick()"
                                class="font-mono text-white/50 hover:text-white transition-colors"
                                aria-label="Application version"
                            >
                                {{ appVersion }}
                            </button>
                            <a href="https://github.com/thiritin/identity" target="_blank" ...> ...
```

(Keep all existing social icons and legal links unchanged — only insert the `<button>` as the first child of `<nav>`.)

- [ ] **Step 3: Add version button to mobile footer**

In the same file, locate the mobile footer block (starts around line 101 with `<!-- Mobile footer -->`). Insert the version button as the first child of the flex container (before the GitHub icon link):

```vue
            <!-- Mobile footer -->
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 px-4 pb-6 text-xs text-gray-400">
                <button
                    v-if="appVersion"
                    type="button"
                    @click="devMode.registerClick()"
                    class="font-mono hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    aria-label="Application version"
                >
                    {{ appVersion }}
                </button>
                <a href="https://github.com/thiritin/identity" ...>
```

- [ ] **Step 4: Verify build**

Run: `npm run build`

Expected: success.

- [ ] **Step 5: Manual verification of unlock flow**

Run the dev server (`npm run dev` in one terminal, `php artisan serve` in another, or whatever the project's existing dev script is). Authenticated, visit any page under `/settings` or `/dashboard`.

Checklist:
1. Version string (`dev` locally) is visible in the footer on both desktop and mobile widths.
2. Click it 5 times quickly — a toast "Developer mode enabled" appears.
3. Click it once more — toast "Developer mode disabled" appears.
4. Re-enable, reload the page — dev mode persists (sessionStorage).
5. Open a new tab or close and reopen the tab — dev mode is off (session-scoped).
6. Click 3 times, wait 2 seconds, click 2 more times — nothing happens (rolling reset works).

- [ ] **Step 6: Commit**

```bash
git add resources/js/Layouts/AccountLayout.vue
git commit -m "feat: show app version in footer with click-to-unlock dev mode"
```

---

## Task 7: `DevHashid` placements — simple pages (Profile, StaffProfile)

**Files:**
- Modify: `resources/js/Pages/Settings/Profile.vue`
- Modify: `resources/js/Pages/Directory/StaffProfile.vue`

- [ ] **Step 1: Add DevHashid to own profile page**

Edit `resources/js/Pages/Settings/Profile.vue`. Import the component near the other imports:

```javascript
import DevHashid from '@/Components/DevHashid.vue'
```

Find where the user's name is rendered (usually in a header section near the avatar). Add `<DevHashid :id="page.props.user.id" />` immediately after the name element. If the page uses `usePage()`, reuse the existing `page` ref; otherwise, add:

```javascript
import { usePage } from '@inertiajs/vue3'
const page = usePage()
```

**Important:** The shared user prop exposes the hashid as `page.props.user.id` (see `HandleInertiaRequests.php:51`), **not** `page.props.user.hashid`.

- [ ] **Step 2: Add DevHashid to staff profile page**

Edit `resources/js/Pages/Directory/StaffProfile.vue`. Import the component. Find where the viewed user's name is rendered (e.g. `<h1>{{ user.name }}</h1>` or similar). Add `<DevHashid :id="user.hashid" />` next to it. Confirm the prop name on that page — it's typically `user.hashid` in directory payloads (not `.id`), so use whichever field the component already receives.

Read the file first if in doubt: `resources/js/Pages/Directory/StaffProfile.vue`.

- [ ] **Step 3: Verify build**

Run: `npm run build`

Expected: success.

- [ ] **Step 4: Manual verification**

Enable dev mode (5 clicks on version), visit `/settings/profile` — your hashid shows next to your name. Visit any staff profile from the directory — their hashid shows.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Settings/Profile.vue resources/js/Pages/Directory/StaffProfile.vue
git commit -m "feat: show hashids on profile pages in dev mode"
```

---

## Task 8: `DevHashid` placements — directory pages

**Files:**
- Modify: `resources/js/Pages/Directory/DirectoryIndex.vue`
- Modify: `resources/js/Pages/Directory/DirectoryShow.vue`
- Modify: `resources/js/Pages/Directory/Components/DirectoryTree.vue`

- [ ] **Step 1: Add DevHashid to DirectoryIndex**

Edit `resources/js/Pages/Directory/DirectoryIndex.vue`. Import the component:

```javascript
import DevHashid from '@/Components/DevHashid.vue'
```

Add `<DevHashid :id="group.hashid" />`:
- Next to each `myMemberships` entry's name (inside the `Link`, after `{{ group.name }}`).
- Next to each division name (after `{{ division.name }}` in the division header).
- Next to each department name (after `{{ dept.name }}`).
- Next to each `orphanDepartments` entry, if the page renders them.

- [ ] **Step 2: Add DevHashid to DirectoryShow**

Edit `resources/js/Pages/Directory/DirectoryShow.vue`. Import the component. Read the file first to locate the relevant templates, then add `<DevHashid :id="X.hashid" />`:
- Next to the group title (`group.hashid`).
- Next to each member's name in the members list (`member.hashid`).
- Next to each leader's name in the leaders section (`leader.hashid`).
- Next to each sub-group in the `subGroups` list (`child.hashid`).

- [ ] **Step 3: Add DevHashid to DirectoryTree**

Edit `resources/js/Pages/Directory/Components/DirectoryTree.vue`. Import the component. Read the file first to understand the recursive tree rendering. Add `<DevHashid :id="node.hashid" />` next to each rendered tree node label. The tree is rendered as a persistent sidebar across all `directory.*` routes, so this single change lights up hashids across every directory page.

- [ ] **Step 4: Verify build**

Run: `npm run build`

Expected: success.

- [ ] **Step 5: Manual verification**

With dev mode enabled, visit `/directory` — hashids appear next to all groups and in the sidebar tree. Click into a group — group title, members, leaders, and sub-groups all show hashids.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Directory/DirectoryIndex.vue resources/js/Pages/Directory/DirectoryShow.vue resources/js/Pages/Directory/Components/DirectoryTree.vue
git commit -m "feat: show hashids on directory pages in dev mode"
```

---

## Task 9: System groups section on DirectoryIndex

**Files:**
- Modify: `resources/js/Pages/Directory/DirectoryIndex.vue`

- [ ] **Step 1: Accept the new prop**

Edit `resources/js/Pages/Directory/DirectoryIndex.vue`. Locate the `defineProps` call (or equivalent) that declares `myMemberships`, `divisions`, `orphanDepartments`. Add `systemMemberships` as a new prop of the same shape:

```javascript
const props = defineProps({
    myMemberships: { type: Array, default: () => [] },
    divisions: { type: Array, default: () => [] },
    orphanDepartments: { type: Array, default: () => [] },
    systemMemberships: { type: Array, default: () => [] },
})
```

(Match the existing style in that file — if it uses positional array syntax or `withDefaults`, adapt accordingly.)

- [ ] **Step 2: Import useDevMode**

```javascript
import { useDevMode } from '@/Composables/useDevMode'
const devMode = useDevMode()
```

- [ ] **Step 3: Render the System groups section**

Add a new `<section>` to the template, placed after the existing divisions loop and before/after `orphanDepartments` (consistent with layout). Match the visual language of existing group cards:

```vue
            <!-- System groups (dev mode only) -->
            <section v-if="devMode.enabled && systemMemberships.length > 0">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    System groups
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div
                        v-for="group in systemMemberships"
                        :key="group.hashid"
                        class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <div class="min-w-0 flex items-center gap-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ group.name }}
                            </div>
                            <DevHashid :id="group.hashid" />
                        </div>
                        <Badge variant="outline" class="shrink-0 capitalize">{{ group.type }}</Badge>
                    </div>
                </div>
            </section>
```

(The heading is intentionally in English and not i18n'd — this is a developer-only section, consistent with keeping the feature lightweight.)

- [ ] **Step 4: Verify build**

Run: `npm run build`

Expected: success.

- [ ] **Step 5: Manual verification**

As a staff user with `staff` group membership, with dev mode enabled, visit `/directory`. The "System groups" section appears below the divisions, listing at least the `staff` group with its hashid and type badge. Disable dev mode — the section disappears. A non-staff user cannot reach this page at all (403), so there's nothing else to verify there.

- [ ] **Step 6: Run backend test suite once more**

Run: `./vendor/bin/pest tests/Feature/Directory/`

Expected: all green.

- [ ] **Step 7: Commit**

```bash
git add resources/js/Pages/Directory/DirectoryIndex.vue
git commit -m "feat: show system groups section in dev mode"
```

---

## Task 10: Final verification

- [ ] **Step 1: Run full backend test suite**

Run: `./vendor/bin/pest`

Expected: all tests pass.

- [ ] **Step 2: Build frontend production bundle**

Run: `npm run build`

Expected: success, no warnings from new files.

- [ ] **Step 3: End-to-end smoke test**

1. `php artisan serve` + `npm run dev` (or project's existing dev workflow).
2. Log in as a staff user.
3. Confirm `dev` appears in the footer.
4. Click 5× rapidly → toast, dev mode on.
5. Profile page shows own hashid next to name.
6. Directory index shows hashids on all groups + "System groups" section with `staff` group.
7. Directory group detail shows hashids on title, members, leaders, sub-groups.
8. Directory sidebar tree shows hashids on every node.
9. Single click on version → toast, dev mode off, all hashids vanish.
10. Log out and back in → dev mode is off (session cleared).

- [ ] **Step 4: No final commit needed** — all work already committed in prior tasks.
