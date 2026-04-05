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

- **Build time.** `Dockerfile` declares `ARG APP_VERSION` in the production stage and sets `ENV APP_VERSION=${APP_VERSION}` so it is available to Laravel at runtime. `.github/workflows/docker.yml` passes `build-args: APP_VERSION=${{ github.ref_type == 'tag' && github.ref_name || github.sha }}` to `docker/build-push-action`. Tag builds get e.g. `v2.3.1`; main builds get the commit SHA.
- **Runtime.** `config/app.php` adds `'version' => env('APP_VERSION', 'dev')`. Local `php artisan serve` thus shows `dev`.
- **Shared prop.** `HandleInertiaRequests::share()` adds `'version' => config('app.version')` alongside the existing `user` prop, so every Inertia page receives `page.props.version`.

### Footer + click-to-unlock

`AccountLayout.vue` renders the version in both the desktop footer bar and the mobile footer row as a `<button>` element with muted text styling consistent with surrounding links. `aria-label="Application version"`. Clicking calls `useDevMode().registerClick()`.

A new composable at `resources/js/Composables/useDevMode.js` owns state:

- `enabled` — reactive boolean, initial value hydrated from `sessionStorage.getItem('devMode:enabled')`.
- `registerClick()` — if `enabled` is already true, flips it off and shows a toast ("Developer mode disabled"). Otherwise increments an internal click counter with a 1.5s rolling reset timer; on reaching 5 it flips `enabled` to true and shows a toast ("Developer mode enabled").
- `disable()` — setter for programmatic disable.
- Any change to `enabled` is mirrored into `sessionStorage`.

The composable is a singleton module (state declared at module scope) so all components share one reactive instance.

### Hashid display

A single presentational component `resources/js/Components/DevHashid.vue`:

```vue
<template>
  <span v-if="devMode.enabled" class="text-xs font-mono text-muted-foreground ml-2">{{ id }}</span>
</template>
<script setup>
import { useDevMode } from '@/Composables/useDevMode'
const props = defineProps({ id: { type: String, required: true } })
const devMode = useDevMode()
</script>
```

Placements (all receive `hashid` in existing payloads — no backend changes for these):

- `Pages/Settings/Profile.vue` — next to the user's name/avatar block.
- `Pages/Directory/DirectoryIndex.vue` — next to each division/department card title.
- `Pages/Directory/DirectoryShow.vue` — next to the group title and next to each member row.
- `Pages/Directory/StaffProfile.vue` — next to the viewed user's name.
- `Pages/Directory/Components/DirectoryTree.vue` — next to each tree node.

### System groups section

`DirectoryController@index` adds a query for the viewer's memberships in groups whose `type` is not in `[Division, Department, Team]`:

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
