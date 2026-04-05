# Dev mode via footer version unlock

## Goal

Staff/developers building third-party apps need quick access to the hashids of users and groups so they can hardcode them into auth checks. Today those ids are not surfaced anywhere in the UI. We also need an unobtrusive way to display the app version to everyone.

Both needs are solved by a single feature: the app version is shown in the footer. Clicking it 5 times toggles a session-scoped "developer mode" that reveals hashids inline across the app and exposes otherwise-hidden system group memberships in the directory.

## Scope

- Display an app version string in the account layout footer (desktop + mobile), visible to all users.
- Click-to-unlock: 5 clicks within a 1.5s rolling window toggles dev mode on; a single click toggles it off when on.
- Dev mode is session-scoped (sessionStorage), no backend persistence.
- When dev mode is on: hashids render inline next to users and groups on relevant pages; a "System groups" section appears in the directory listing the viewer's memberships in non-Division/Department/Team groups (e.g. `staff`).
- No role gate on the unlock itself. Hashid exposure is naturally scoped by page access — a non-staff user has no directory access, so the only hashid they can reveal is their own on their profile page, which is harmless.

## Out of scope

- Copy-to-clipboard buttons or tooltips on hashids (users can select the text).
- Cross-device / persisted dev mode preference.
- Any changes to the auth/permissions model.
- Exposing hashids of entities the viewer cannot already see.

## Design

### Version plumbing

- **Dockerfile edits.** In the `production` stage (currently `Dockerfile:42`), add two lines before the `CMD`:
  ```dockerfile
  ARG APP_VERSION=dev
  ENV APP_VERSION=${APP_VERSION}
  ```
  The production stage does not run `php artisan config:cache`, so Octane/FrankenPHP workers read `env('APP_VERSION')` at worker boot and see the Docker-provided ENV. No config cache interaction to worry about.
- **Workflow edits.** In `.github/workflows/docker.yml`, add a `build-args:` key under the existing `docker/build-push-action@v6` step (currently lines 57-68, which has no `build-args` today):
  ```yaml
  build-args: |
    APP_VERSION=${{ github.ref_type == 'tag' && github.ref_name || github.sha }}
  ```
  Tag builds resolve to e.g. `v2.3.1`; main builds resolve to the commit SHA.
- **Laravel config.** `config/app.php` adds `'version' => env('APP_VERSION', 'dev')`. Local `php artisan serve` shows `dev`.
- **Shared prop.** `HandleInertiaRequests::share()` adds `'version' => config('app.version')` alongside the existing `user` prop, so every Inertia page receives `page.props.version`.

### Footer + click-to-unlock

`AccountLayout.vue` renders the version in both the desktop footer bar and the mobile footer row as a `<button>` element with muted text styling consistent with surrounding links. `aria-label="Application version"`. Clicking calls `useDevMode().registerClick()`.

A new composable at `resources/js/Composables/useDevMode.js` owns state:

- `enabled` — reactive boolean, initial value hydrated from `sessionStorage.getItem('devMode:enabled')`.
- `registerClick()` — if `enabled` is already true, flips it off and shows a toast. Otherwise increments an internal click counter; each click (re)arms a 1.5s timeout that resets the counter to 0 if no further click arrives. On reaching 5 it flips `enabled` to true, shows a toast, and clears the counter/timer.
- `disable()` — setter for programmatic disable.
- Any change to `enabled` is mirrored into `sessionStorage`.

The composable is a singleton module (state declared at module scope) so all components share one reactive instance.

Toast strings use `trans()` keys (`devmode_enabled`, `devmode_disabled`) to match the codebase's existing i18n pattern — added to the relevant `lang/*.json` files. Values: "Developer mode enabled" / "Developer mode disabled" in English.

### Hashid display

A single presentational component `resources/js/Components/DevHashid.vue`:

```vue
<template>
  <span v-if="devMode.enabled && id" class="text-xs font-mono text-muted-foreground ml-2">{{ id }}</span>
</template>
<script setup>
import { useDevMode } from '@/Composables/useDevMode'
const props = defineProps({ id: { type: String, default: null } })
const devMode = useDevMode()
</script>
```

The `v-if="id"` guard prevents rendering when a payload happens to carry a null hashid.

Placements (hashids already in existing payloads — no backend changes for these):

- `Pages/Settings/Profile.vue` — next to the user's name. **Note:** the shared user prop exposes the hashid as `page.props.user.id` (see `HandleInertiaRequests.php:51` which sets `'id' => $request->user()->hashid`), not `user.hashid`. Use `user.id`.
- `Pages/Directory/DirectoryIndex.vue` — next to each division/department card title.
- `Pages/Directory/DirectoryShow.vue` — next to the group title, next to each member row, next to each entry in `subGroups`, and in the `leaders` section.
- `Pages/Directory/StaffProfile.vue` — next to the viewed user's name.
- `Pages/Directory/Components/DirectoryTree.vue` — next to each tree node. This component is rendered as a persistent sidebar across all `directory.*` routes (payload comes from `App\Services\DirectoryTreeBuilder` via `HandleInertiaRequests`), so adding `DevHashid` here lights it up on every directory page simultaneously.

### System groups section

Because `DirectoryController@index` is staff-only by route gating, this section is staff-only by construction — non-staff users have no way to reach the code path regardless of dev mode state.

The controller adds a query for the viewer's memberships in groups whose `type` is not in `[Division, Department, Team]`. This deliberately includes `Automated`, `Root`, and `Default (none)` — the union of all currently-hidden types. The `staff` group (a named example) typically lives in one of those; all other hidden memberships the viewer holds are also surfaced for completeness.

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

Returned as a new `systemMemberships` Inertia prop.

`DirectoryIndex.vue` renders a "System groups" section below the existing divisions list, gated by `v-if="devMode.enabled && systemMemberships.length"`. Visual language matches the existing group cards; each entry shows its name, type badge, and `DevHashid`.

## Error handling

- `sessionStorage` access is wrapped in try/catch — privacy-mode browsers that throw on access simply fall back to in-memory state for the tab.
- If `page.props.version` is missing (e.g. during upgrade), the footer falls back to rendering nothing rather than erroring.

## Testing

- **Backend:** feature test for `DirectoryController@index` asserting `systemMemberships` is included and contains only the viewer's non-Division/Department/Team memberships. Existing directory tests continue to pass unchanged.
- **Frontend:** Vitest for `useDevMode` covering click-counter reset window, 5-click toggle on, single-click toggle off, sessionStorage hydration, sessionStorage-unavailable fallback.
- **Manual:** verify version appears in both desktop and mobile footer; 5 rapid clicks enable dev mode and hashids appear on profile; as staff, system groups section appears with the `staff` group; logging out clears dev mode.
