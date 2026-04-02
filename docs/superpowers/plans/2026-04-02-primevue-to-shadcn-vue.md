# PrimeVue to shadcn/vue Migration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace PrimeVue and Headless UI with shadcn/vue as the sole component library, upgrading Tailwind CSS from v3 to v4 as a prerequisite.

**Architecture:** This is a phased migration: (1) upgrade Tailwind v3→v4 and install shadcn/vue, (2) add the specific shadcn components needed, (3) migrate each page file-by-file replacing PrimeVue/HeadlessUI usage, (4) remove old dependencies and presets. Each task produces a working, testable state.

**Tech Stack:** Vue 3, shadcn/vue (Reka UI primitives), Tailwind CSS v4, Vite 6, Lucide icons, vue-sonner (toast)

---

## Important Notes

- **No TypeScript** — this project uses plain JavaScript. The shadcn CLI generates `.ts` files by default; set `"typescript": false` in `components.json` or convert generated files to `.js` after.
- **`@/` alias** — already works via `laravel-vite-plugin`. Task 1 formalizes it in `vite.config.js` for shadcn CLI compatibility. Existing imports won't break.
- **Component directory casing** — existing components live in `resources/js/Components/` (capital C). New shadcn components go in `resources/js/Components/ui/` (same capital C) to avoid casing conflicts on case-sensitive filesystems (Linux/Docker). Update `components.json` aliases accordingly.
- **Color source of truth** — the project has two color definitions: `tailwind.config.js` (hex values used in Tailwind classes) and `resources/css/app.css` (RGB triplets used by PrimeVue's `rgb(var(...))` pattern). For Tailwind v4, use the **hex values from `tailwind.config.js`** as they are the ones consumed by Tailwind utility classes. The RGB triplet variables from `app.css` are PrimeVue-specific and will be removed.
- **`@tailwindcss/forms`** — installed but never enabled in `tailwind.config.js` plugins. Not needed — PrimeVue styled all forms, and shadcn components bring their own form styling.
- **`ResetPassword.vue`** — imports PrimeVue `Button` but uses a raw `<button>` for submit. The import should be removed; optionally convert to shadcn Button for consistency.

---

## File Structure

### New files created:
- `components.json` — shadcn/vue configuration
- `resources/js/lib/utils.js` — `cn()` utility (clsx + tailwind-merge)
- `resources/js/Components/ui/button/` — shadcn Button
- `resources/js/Components/ui/input/` — shadcn Input
- `resources/js/Components/ui/checkbox/` — shadcn Checkbox
- `resources/js/Components/ui/dialog/` — shadcn Dialog
- `resources/js/Components/ui/select/` — shadcn Select
- `resources/js/Components/ui/badge/` — shadcn Badge (replaces Tag)
- `resources/js/Components/ui/tabs/` — shadcn Tabs (replaces TabMenu)
- `resources/js/Components/ui/toggle-group/` — shadcn ToggleGroup (replaces SelectButton)
- `resources/js/Components/ui/input-otp/` — shadcn InputOTP
- `resources/js/Components/ui/dropdown-menu/` — shadcn DropdownMenu (replaces HeadlessUI Menu)
- `resources/js/Components/ui/sheet/` — shadcn Sheet (replaces HeadlessUI Dialog for mobile sidebar)
- `resources/js/Components/ui/sonner/` — shadcn Sonner wrapper (replaces PrimeVue Toast)
- `resources/js/Components/ui/command/` — shadcn Command (for searchable dropdown in TabHeader)

### Files modified:
- `package.json` — dependency changes
- `vite.config.js` — add @tailwindcss/vite plugin, formalize @ alias
- `resources/css/app.css` — rewrite for Tailwind v4 syntax + shadcn CSS variables
- `postcss.config.js` — can be deleted (Tailwind v4 handled by Vite plugin)
- `resources/js/app.js` — remove PrimeVue setup, add Sonner Toaster early
- `resources/js/Layouts/AppLayout.vue` — replace HeadlessUI + PrimeVue Toast + heroicons
- `resources/js/Components/Auth/AuthHeader.vue` — replace HeadlessUI Menu + heroicons
- `resources/js/Profile/AvatarModal.vue` — replace HeadlessUI Dialog + heroicons
- `resources/js/Pages/Profile/Show.vue` — replace heroicons with Lucide
- `resources/js/Pages/Dashboard.vue` — replace heroicons with Lucide
- `resources/js/Pages/Staff/Dashboard.vue` — replace heroicons with Lucide
- `resources/js/Components/BaseAlert.vue` — replace heroicons with Lucide
- All pages in `resources/js/Pages/Auth/` that use PrimeVue (6 files)
- All pages in `resources/js/Pages/Settings/` that use PrimeVue (4 files)
- All staff pages in `resources/js/Pages/Staff/` that use PrimeVue (5 files)

### Files deleted:
- `resources/js/presets/aura/` — entire directory (92 files)
- `resources/js/presets/lara/` — entire directory
- `resources/js/presets/` — entire directory
- `tailwind.config.js` — replaced by CSS-based config in Tailwind v4
- `postcss.config.js` — no longer needed (Tailwind v4 uses Vite plugin)

---

## Component Mapping Reference

Use this table when migrating templates:

| PrimeVue / HeadlessUI | shadcn/vue | Notes |
|---|---|---|
| `InputText` | `Input` | `:invalid` → class binding `:class="{ 'border-destructive': ... }"` |
| `Button` | `Button` | `:loading` → `:disabled`; `:severity` → `variant`; `:label` → slot content; `outlined` → `variant="outline"` |
| `Checkbox` | `Checkbox` | `:binary` not needed; uses `:checked` + `@update:checked` |
| `InlineMessage severity="error"` | `<p class="text-sm text-destructive">` | No component needed, just styled text |
| `Dialog` | `Dialog` | `v-model:visible` → `v-model:open`; `modal` prop not needed (default) |
| `Dropdown` | `Select` or `Command` (searchable) | Completely different API — see task details |
| `SelectButton` | `ToggleGroup` | `:options` → explicit children |
| `TabMenu` | `Tabs` | `:model` → explicit `TabsTrigger` children |
| `Tag` | `Badge` | `severity` → `variant` |
| `Toast` + `useToast` | `Sonner` + `toast()` from vue-sonner | `toast.add({severity, summary, detail})` → `toast.success(detail)` |
| `InputOtp` | `InputOTP` | Similar API, uses slot-based groups |
| HeadlessUI `Menu` | `DropdownMenu` | Similar composable pattern |
| HeadlessUI `Dialog` (sidebar) | `Sheet` | Side-panel variant of Dialog |
| HeadlessUI `Dialog` (modal) | `Dialog` | Same shadcn Dialog |
| `primeicons` (`pi pi-*`) | `lucide-vue-next` | Import individual icon components |
| `@heroicons/vue` | `lucide-vue-next` | `Bars3Icon` → `Menu`, `XMarkIcon` → `X`, `HomeIcon` → `Home`, `UsersIcon` → `Users`, `CheckIcon` → `Check`, `PencilIcon` → `Pencil`, `ChevronRightIcon` → `ChevronRight`, `ChevronDownIcon` → `ChevronDown`, `ChevronLeftIcon` → `ChevronLeft`, `ExclamationTriangleIcon` → `AlertTriangle`, `PhoneIcon` → `Phone` |

---

## Task 1: Upgrade Tailwind CSS v3 → v4 and Vite config

**Files:**
- Modify: `package.json`
- Modify: `vite.config.js`
- Delete: `postcss.config.js`
- Modify: `resources/css/app.css`
- Delete: `tailwind.config.js`

This task converts the build pipeline. The app will look identical after — just powered by Tailwind v4.

- [ ] **Step 1: Install Tailwind v4 and its Vite plugin, remove v3 deps**

```bash
vendor/bin/sail npm remove tailwindcss postcss autoprefixer @tailwindcss/forms @tailwindcss/typography
vendor/bin/sail npm install tailwindcss@4 @tailwindcss/vite @tailwindcss/typography@next
```

Note: `@tailwindcss/typography@next` installs the Tailwind v4-compatible version.

- [ ] **Step 2: Update vite.config.js — add Tailwind plugin and formalize @ alias**

Replace the full file with:

```javascript
import { defineConfig } from 'vite';
import path from 'node:path';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
});
```

- [ ] **Step 3: Delete postcss.config.js**

Tailwind v4 is handled entirely by the Vite plugin. PostCSS config is no longer needed:

```bash
rm postcss.config.js
```

- [ ] **Step 4: Rewrite resources/css/app.css for Tailwind v4**

Tailwind v4 uses `@import "tailwindcss"` instead of `@tailwind` directives. Use the **hex color values from `tailwind.config.js`** as the source of truth. The RGB triplet variables from the old `app.css` were PrimeVue-specific and are dropped.

```css
@import "tailwindcss";

@plugin "@tailwindcss/typography";

/*
 * Custom theme — teal primary + neutral surface palette.
 * Source of truth: tailwind.config.js hex values.
 */
@theme {
    --color-primary-100: #E6EFEE;
    --color-primary-200: #CBDEDD;
    --color-primary-300: #AEC6C4;
    --color-primary-400: #69A3A2;
    --color-primary-500: #005953;
    --color-primary-600: #00504B;
    --color-primary-700: #003532;
    --color-primary-800: #002825;
    --color-primary-900: #001B19;

    --color-surface-0: #FFFFFF;
    --color-surface-50: #F9FAFB;
    --color-surface-100: #F9FAFB;
    --color-surface-200: #F4F6F8;
    --color-surface-300: #E5E7EB;
    --color-surface-400: #D2D6DC;
    --color-surface-500: #9FA6B2;
    --color-surface-600: #6B7280;
    --color-surface-700: #4B5563;
    --color-surface-800: #374151;
    --color-surface-900: #1F2937;
    --color-surface-950: #111827;
}
```

Note: `danger`, `success`, and `warning` colors from the old config referenced Tailwind's built-in `colors.rose`, `colors.green`, and `colors.yellow` — these are available by default in Tailwind v4 as `rose-*`, `green-*`, `yellow-*`.

- [ ] **Step 5: Delete tailwind.config.js**

```bash
rm tailwind.config.js
```

Dark mode `class` strategy is the default in Tailwind v4 when using the `dark:` variant. The typography plugin is imported via `@plugin` in CSS.

- [ ] **Step 6: Build and verify**

```bash
vendor/bin/sail npm run build
```

Expected: Build succeeds with no errors. If there are Tailwind class warnings, check that all custom classes from the old config are accounted for in the new `@theme` block.

- [ ] **Step 7: Visual smoke test**

Open the app in the browser and verify pages look the same. Check:
- Login page (`/auth/login`)
- Dashboard (after login)
- Dark mode toggle still works

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Upgrade Tailwind CSS v3 to v4 with Vite plugin"
```

---

## Task 2: Install shadcn/vue and add base utility

**Files:**
- Create: `components.json`
- Create: `resources/js/lib/utils.js`
- Modify: `package.json`
- Modify: `resources/css/app.css` (append shadcn CSS variables)

- [ ] **Step 1: Install shadcn/vue dependencies**

```bash
vendor/bin/sail npm install clsx tailwind-merge class-variance-authority lucide-vue-next reka-ui vue-sonner
```

- [ ] **Step 2: Create components.json in project root**

```json
{
    "$schema": "https://shadcn-vue.com/schema.json",
    "style": "new-york",
    "typescript": false,
    "tailwind": {
        "config": "",
        "css": "resources/css/app.css",
        "baseColor": "neutral",
        "cssVariables": true,
        "prefix": ""
    },
    "aliases": {
        "components": "@/Components",
        "composables": "@/composables",
        "utils": "@/lib/utils",
        "ui": "@/Components/ui",
        "lib": "@/lib"
    },
    "iconLibrary": "lucide"
}
```

Note: Both `components` and `ui` aliases use `@/Components` (capital C) to match the existing project convention and avoid casing conflicts on case-sensitive filesystems.

- [ ] **Step 3: Create the cn() utility**

Create `resources/js/lib/utils.js`:

```javascript
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}
```

- [ ] **Step 4: Add shadcn CSS variables to app.css**

Append the shadcn/vue CSS variable block to `resources/css/app.css` (after the existing theme). These are needed by shadcn components:

```css
/* shadcn/vue design tokens */
:root {
    --background: 0 0% 100%;
    --foreground: 0 0% 3.9%;
    --card: 0 0% 100%;
    --card-foreground: 0 0% 3.9%;
    --popover: 0 0% 100%;
    --popover-foreground: 0 0% 3.9%;
    --muted: 0 0% 96.1%;
    --muted-foreground: 0 0% 45.1%;
    --accent: 0 0% 96.1%;
    --accent-foreground: 0 0% 9%;
    --destructive: 0 84.2% 60.2%;
    --destructive-foreground: 0 0% 98%;
    --border: 0 0% 89.8%;
    --input: 0 0% 89.8%;
    --ring: 0 0% 3.9%;
    --radius: 0.5rem;
}

.dark {
    --background: 0 0% 3.9%;
    --foreground: 0 0% 98%;
    --card: 0 0% 3.9%;
    --card-foreground: 0 0% 98%;
    --popover: 0 0% 3.9%;
    --popover-foreground: 0 0% 98%;
    --muted: 0 0% 14.9%;
    --muted-foreground: 0 0% 63.9%;
    --accent: 0 0% 14.9%;
    --accent-foreground: 0 0% 98%;
    --destructive: 0 62.8% 30.6%;
    --destructive-foreground: 0 0% 98%;
    --border: 0 0% 14.9%;
    --input: 0 0% 14.9%;
    --ring: 0 0% 83.1%;
}
```

- [ ] **Step 5: Build and verify**

```bash
vendor/bin/sail npm run build
```

Expected: Build succeeds. No visual changes yet — just infrastructure.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Install shadcn/vue dependencies and base config"
```

---

## Task 3: Add shadcn/vue UI components and Toaster

**Files:**
- Create: `resources/js/Components/ui/` (all component subdirectories)
- Modify: `resources/js/Layouts/AppLayout.vue` (add Toaster only)

- [ ] **Step 1: Add all needed components via shadcn CLI**

Run from the project root. The CLI reads `components.json` and generates component files:

```bash
npx shadcn-vue@latest add button input checkbox dialog select badge tabs toggle-group input-otp dropdown-menu sheet sonner command --yes --force
```

If the CLI has issues with the non-standard directory structure (`resources/js` instead of `src`), create the components manually by copying from the [shadcn-vue docs](https://www.shadcn-vue.com). The components are just Vue files — they don't have build-time magic.

Important: After running the CLI, verify components were placed in `resources/js/Components/ui/`. If they ended up elsewhere, move them. If the CLI generated `.ts` files, rename to `.js` and remove type annotations.

- [ ] **Step 2: Verify component files exist**

```bash
ls resources/js/Components/ui/
```

Expected: Directories for each component added above.

- [ ] **Step 3: Add Toaster to AppLayout early**

Add `<Toaster />` to `resources/js/Layouts/AppLayout.vue` now (alongside the existing PrimeVue `<Toast />`). This ensures vue-sonner toasts work immediately as we migrate pages in subsequent tasks, avoiding a broken toast gap.

Add import:
```javascript
import { Toaster } from '@/Components/ui/sonner';
```

Add in template (at root level, next to existing `<Toast />`):
```vue
<Toaster />
```

Do NOT remove `<Toast />` yet — it will be removed in Task 10 when the full layout migration happens.

- [ ] **Step 4: Build and verify**

```bash
vendor/bin/sail npm run build
```

Expected: Build succeeds. Components are tree-shaken — unused ones don't add bundle size.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Add shadcn/vue UI components and Toaster"
```

---

## Task 4: Migrate app.js — remove PrimeVue

**Files:**
- Modify: `resources/js/app.js`

- [ ] **Step 1: Read current app.js**

Read `resources/js/app.js` to see the full current setup.

- [ ] **Step 2: Remove PrimeVue imports and plugin registration**

Remove these lines:
- `import PrimeVue from 'primevue/config'`
- `import Aura from './presets/aura'`
- `import ToastService from 'primevue/toastservice'`
- `import 'primeicons/primeicons.css'`
- `.use(PrimeVue, { unstyled: true, pt: Aura })`
- `.use(ToastService)`

Do NOT remove other `.use()` calls (VueCookies, Ziggy, Matice, etc.).

- [ ] **Step 3: Build and verify**

```bash
vendor/bin/sail npm run build
```

Expected: Build succeeds. Pages that still use PrimeVue components will show runtime errors in the browser (expected — we fix them in subsequent tasks). The Toaster added in Task 3 ensures vue-sonner toasts already work.

- [ ] **Step 4: Commit**

```bash
git add resources/js/app.js
git commit -m "Remove PrimeVue plugin from app entry point"
```

---

## Task 5: Migrate Auth pages — Login

**Files:**
- Modify: `resources/js/Pages/Auth/Login.vue`

This is the most-used page, so migrate it first as a template for the others.

- [ ] **Step 1: Read the current Login.vue**

Read `resources/js/Pages/Auth/Login.vue` fully.

- [ ] **Step 2: Replace PrimeVue imports with shadcn imports**

Replace:
```javascript
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import InlineMessage from 'primevue/inlinemessage';
```

With:
```javascript
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
```

- [ ] **Step 3: Migrate template — InputText → Input**

Replace each `<InputText>` with `<Input>`. Key changes:
- Remove `:invalid` prop — instead use class binding: `:class="{ 'border-destructive': form.invalid('email') }"`
- `v-model.trim.lazy` → `v-model` (or use `@input` / `@change` for lazy behavior)
- Keep `id`, `type`, `autocomplete` props as-is

Before:
```vue
<InputText id="email" v-model.trim.lazy="form.email" autocomplete="email" :invalid="form.invalid('email')" @change="form.validate('email')" />
```

After:
```vue
<Input id="email" v-model="form.email" type="email" autocomplete="email" :class="{ 'border-destructive': form.invalid('email') }" @change="form.validate('email')" />
```

- [ ] **Step 4: Migrate template — Button**

Replace `<Button>` usage. Key changes:
- `:label="text"` → slot content: `{{ text }}`
- `:loading="form.processing"` → `:disabled="form.processing"` (add a spinner icon or text if desired)
- `type="submit"` stays the same

Before:
```vue
<Button :loading="form.processing" type="submit" :label="$trans('sign_in')" />
```

After:
```vue
<Button type="submit" :disabled="form.processing">
    {{ $trans('sign_in') }}
</Button>
```

- [ ] **Step 5: Migrate template — Checkbox**

Replace PrimeVue `<Checkbox>` with shadcn `<Checkbox>`.

Before:
```vue
<Checkbox input-id="remember" :binary="true" v-model="form.remember" name="remember" />
```

After:
```vue
<Checkbox id="remember" :checked="form.remember" @update:checked="form.remember = $event" />
```

- [ ] **Step 6: Migrate template — InlineMessage**

Replace `<InlineMessage severity="error">` with plain styled text.

Before:
```vue
<InlineMessage v-if="form.invalid('email')" severity="error">{{ form.errors.email }}</InlineMessage>
```

After:
```vue
<p v-if="form.invalid('email')" class="text-sm text-destructive">{{ form.errors.email }}</p>
```

- [ ] **Step 7: Build and test in browser**

```bash
vendor/bin/sail npm run build
```

Visit `/auth/login` and verify:
- Form renders correctly
- Validation errors display
- Login submission works
- Dark mode works

- [ ] **Step 8: Commit**

```bash
git add resources/js/Pages/Auth/Login.vue
git commit -m "Migrate Login page from PrimeVue to shadcn/vue"
```

---

## Task 6: Migrate remaining Auth pages

**Files:**
- Modify: `resources/js/Pages/Auth/Register.vue`
- Modify: `resources/js/Pages/Auth/ForgotPassword.vue`
- Modify: `resources/js/Pages/Auth/ResetPassword.vue`
- Modify: `resources/js/Pages/Auth/VerifyEmail.vue`
- Modify: `resources/js/Pages/Auth/TwoFactor.vue`

These all follow the same pattern as Login: InputText → Input, Button → Button, InlineMessage → styled `<p>`.

- [ ] **Step 1: Migrate Register.vue**

Same pattern as Login. Replace `InputText`, `InlineMessage`, `Button`. Note: line 18 has a typo `seveGrity` — fix this when converting to the `<p>` class approach.

- [ ] **Step 2: Migrate ForgotPassword.vue**

Same pattern. 1x InputText, 1x InlineMessage, 1x Button.

- [ ] **Step 3: Migrate ResetPassword.vue**

3x InputText, 3x InlineMessage. Note: the submit button is a **raw `<button>` element**, not a PrimeVue Button (the PrimeVue `Button` import is unused). Remove the unused import. Optionally convert the raw `<button>` to shadcn `<Button>` for consistency.

- [ ] **Step 4: Migrate VerifyEmail.vue**

Only uses Button. Simple replacement.

- [ ] **Step 5: Migrate TwoFactor.vue**

Uses InputText, InlineMessage, Button, and **InputOtp**. For InputOtp:

Before:
```vue
<InputOtp :length="6" v-model.trim.lazy="form.code" autocomplete="one-time-code" :invalid="form.invalid('code')" />
```

After:
```vue
<InputOTP v-model="form.code" :maxlength="6">
    <InputOTPGroup>
        <InputOTPSlot v-for="i in 6" :key="i" :index="i - 1" />
    </InputOTPGroup>
</InputOTP>
```

Import:
```javascript
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/Components/ui/input-otp';
```

- [ ] **Step 6: Build and visual test**

```bash
vendor/bin/sail npm run build
```

Visit each page and verify forms render and work.

- [ ] **Step 7: Commit**

```bash
git add resources/js/Pages/Auth/
git commit -m "Migrate all Auth pages from PrimeVue to shadcn/vue"
```

---

## Task 7: Migrate Settings pages

**Files:**
- Modify: `resources/js/Pages/Settings/Profile.vue`
- Modify: `resources/js/Pages/Settings/UpdatePassword.vue`
- Modify: `resources/js/Pages/Settings/TwoFactor/AuthenticatorApp.vue`
- Modify: `resources/js/Pages/Settings/TwoFactor/YubikeySetup.vue`

- [ ] **Step 1: Migrate Profile.vue**

Same InputText → Input, InlineMessage → `<p>`, Button → Button pattern.

- [ ] **Step 2: Migrate UpdatePassword.vue**

Same pattern. 3x InputText (all type="password"), 3x InlineMessage, 1x Button.

- [ ] **Step 3: Migrate AuthenticatorApp.vue**

Same pattern. 2x InputText, 2x InlineMessage, 1x Button.

- [ ] **Step 4: Migrate YubikeySetup.vue**

Has extra complexity:
- Button with `icon="pi pi-trash"` → use Lucide icon:
  ```javascript
  import { Trash2 } from 'lucide-vue-next';
  ```
  ```vue
  <Button variant="ghost" size="sm" @click="disableKeyId = key.id">
      <Trash2 class="h-4 w-4" />
  </Button>
  ```
- Button with `severity="secondary"` → `variant="secondary"`
- Button with `severity="danger"` → `variant="destructive"`
- Button with `link` prop → `variant="link"`
- Button with `outlined` prop → `variant="outline"`

- [ ] **Step 5: Build and visual test**

```bash
vendor/bin/sail npm run build
```

Visit settings pages and verify all forms work.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Settings/
git commit -m "Migrate Settings pages from PrimeVue to shadcn/vue"
```

---

## Task 8: Migrate Staff pages — TabComponent and TabHeader (most complex)

**Files:**
- Modify: `resources/js/Pages/Staff/Groups/Tabs/TabComponent.vue`
- Modify: `resources/js/Pages/Staff/Groups/Tabs/TabHeader.vue`

- [ ] **Step 1: Migrate TabComponent.vue — TabMenu → Tabs**

Before:
```vue
import TabMenu from 'primevue/tabmenu';
// model: [{label, icon, command}]
<TabMenu :activeIndex="activeIndex" :model="tabMenuItems" />
```

After:
```vue
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Box, Users, LayoutGrid } from 'lucide-vue-next';
```

```vue
<Tabs :model-value="activeTab" @update:model-value="navigateTab">
    <TabsList>
        <TabsTrigger value="info">
            <Box class="mr-2 h-4 w-4" /> Info
        </TabsTrigger>
        <TabsTrigger value="members">
            <Users class="mr-2 h-4 w-4" /> Members
        </TabsTrigger>
        <TabsTrigger v-if="group.parent_id === null" value="teams">
            <LayoutGrid class="mr-2 h-4 w-4" /> Teams
        </TabsTrigger>
    </TabsList>
</Tabs>
```

Adapt the `navigateTab` function to call `router.visit()` based on the tab value.

- [ ] **Step 2: Migrate TabHeader.vue — Dialog, Dropdown, SelectButton, InputText, Button, Toast**

This is the most complex file. Replace each component:

**Dialog** (4 instances: Add Member, Add Team, Delete Team, Edit Team) → shadcn Dialog:
```vue
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
```

Before:
```vue
<Dialog v-model:visible="showAddMemberDialog" modal header="Add Member">
```

After:
```vue
<Dialog v-model:open="showAddMemberDialog">
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Add Member</DialogTitle>
        </DialogHeader>
        <!-- content -->
        <DialogFooter>
            <!-- buttons -->
        </DialogFooter>
    </DialogContent>
</Dialog>
```

**SelectButton** → ToggleGroup:
```vue
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
```

Before:
```vue
<SelectButton v-model="addVia" :options="options" />
```

After:
```vue
<ToggleGroup v-model="addVia" type="single">
    <ToggleGroupItem value="Staff List">Staff List</ToggleGroupItem>
    <ToggleGroupItem value="Email">Email</ToggleGroupItem>
</ToggleGroup>
```

**Dropdown** (searchable, with virtual scroll) → Select:
```vue
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
```

Before:
```vue
<Dropdown v-model="addUserForm.user_id" :options="staffMemberList" optionLabel="name" optionValue="id" filter placeholder="Select a Staff Member" />
```

After:
```vue
<Select v-model="addUserForm.user_id">
    <SelectTrigger>
        <SelectValue placeholder="Select a Staff Member" />
    </SelectTrigger>
    <SelectContent>
        <SelectItem v-for="member in staffMemberList" :key="member.id" :value="String(member.id)">
            {{ member.name }}
        </SelectItem>
    </SelectContent>
</Select>
```

Note: If the staff list is very large and needs filtering/virtual scroll, use the Command component (combobox pattern) instead of Select. Evaluate during implementation.

**Button** — additional prop mappings for this file:
- `outlined` → `variant="outline"`
- `severity="danger"` → `variant="destructive"`

**Toast** → vue-sonner:
```javascript
import { toast } from 'vue-sonner';
// Replace: toast.add({severity: 'success', summary: 'Success', detail: 'msg'})
// With:    toast.success('msg')
```

- [ ] **Step 3: Build and test**

```bash
vendor/bin/sail npm run build
```

Visit a group page in the staff section and verify:
- Tab navigation works
- Add member dialog opens and works
- Add team dialog opens and works
- Delete team confirmation works
- Edit team dialog works
- Toast notifications appear

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Staff/Groups/Tabs/TabComponent.vue resources/js/Pages/Staff/Groups/Tabs/TabHeader.vue
git commit -m "Migrate TabComponent and TabHeader from PrimeVue to shadcn/vue"
```

---

## Task 9: Migrate remaining Staff pages

**Files:**
- Modify: `resources/js/Pages/Staff/Groups/Tabs/MemberTab.vue`
- Modify: `resources/js/Pages/Staff/Groups/Tabs/TeamTab.vue`
- Modify: `resources/js/Pages/Staff/Groups/Tabs/GroupInfoTab.vue`
- Modify: `resources/js/Pages/Staff/GroupMember/GroupMemberEdit.vue`

- [ ] **Step 1: Migrate MemberTab.vue**

Replace Dialog, Button, Tag, useToast:
- `Tag severity="secondary"` → `<Badge variant="secondary">{{ team.name }}</Badge>`
- Dialog + buttons → same pattern as TabHeader
- `toast.add(...)` → `toast.success(...)`
- `severity="danger"` on Button → `variant="destructive"`
- `severity="secondary"` on Button → `variant="secondary"`
- `p-text-secondary` CSS class → `text-muted-foreground`

Import:
```javascript
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { toast } from 'vue-sonner';
```

- [ ] **Step 2: Migrate TeamTab.vue**

Same pattern as MemberTab: Dialog, Button, toast.

- [ ] **Step 3: Migrate GroupInfoTab.vue**

Only uses Button. Replace:
- `severity="danger"` → `variant="destructive"`
- `size="small"` → `size="sm"`

- [ ] **Step 4: Migrate GroupMemberEdit.vue**

Replace Dropdown → Select:

Before:
```vue
import Dropdown from 'primevue/dropdown';
<Dropdown v-model="form.level" :options="levels" option-value="value" optionLabel="name" />
```

After:
```vue
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

<Select v-model="form.level">
    <SelectTrigger class="w-full md:w-56">
        <SelectValue />
    </SelectTrigger>
    <SelectContent>
        <SelectItem v-for="level in levels" :key="level.value" :value="level.value">
            {{ level.name }}
        </SelectItem>
    </SelectContent>
</Select>
```

- [ ] **Step 5: Build and visual test**

```bash
vendor/bin/sail npm run build
```

Test all staff group pages.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Staff/
git commit -m "Migrate remaining Staff pages from PrimeVue to shadcn/vue"
```

---

## Task 10: Migrate Layouts — AppLayout and AuthHeader (HeadlessUI + heroicons)

**Files:**
- Modify: `resources/js/Layouts/AppLayout.vue`
- Modify: `resources/js/Components/Auth/AuthHeader.vue`

- [ ] **Step 1: Migrate AppLayout.vue — HeadlessUI → shadcn + heroicons → Lucide**

This file uses HeadlessUI, PrimeVue Toast, and heroicons. Replace all three:

**Heroicon imports** → Lucide:
```javascript
// Remove:
import { Bars3Icon, HomeIcon, UsersIcon, XMarkIcon } from '@heroicons/vue/24/outline';
// Add:
import { Menu, Home, Users, X } from 'lucide-vue-next';
```

**Mobile sidebar (HeadlessUI Dialog)** → Sheet:
```vue
import { Sheet, SheetContent } from '@/Components/ui/sheet';
```

Before (simplified):
```vue
<TransitionRoot :show="sidebarOpen">
    <Dialog @close="sidebarOpen = false">
        <TransitionChild><!-- overlay --></TransitionChild>
        <TransitionChild>
            <DialogPanel><!-- sidebar content --></DialogPanel>
        </TransitionChild>
    </Dialog>
</TransitionRoot>
```

After:
```vue
<Sheet v-model:open="sidebarOpen">
    <SheetContent side="left" class="w-72 p-0">
        <!-- sidebar content (same inner HTML) -->
    </SheetContent>
</Sheet>
```

**Profile dropdown (HeadlessUI Menu)** → DropdownMenu:
```vue
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
```

Before:
```vue
<Menu>
    <MenuButton><!-- avatar --></MenuButton>
    <MenuItems>
        <MenuItem v-slot="{ active }"><!-- link --></MenuItem>
    </MenuItems>
</Menu>
```

After:
```vue
<DropdownMenu>
    <DropdownMenuTrigger as-child>
        <!-- avatar button -->
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
        <DropdownMenuItem as-child>
            <Link href="...">Profile</Link>
        </DropdownMenuItem>
        <!-- more items -->
        <DropdownMenuItem @click="logout">Logout</DropdownMenuItem>
    </DropdownMenuContent>
</DropdownMenu>
```

**Toast** — Remove the PrimeVue `<Toast />` import and component. The `<Toaster />` was already added in Task 3.

Remove:
```javascript
import Toast from 'primevue/toast';
```

- [ ] **Step 2: Migrate AuthHeader.vue — HeadlessUI Menu → DropdownMenu + heroicons → Lucide**

Replace:
```javascript
// Remove:
import { ChevronDownIcon, ChevronLeftIcon } from '@heroicons/vue/24/outline';
// Add:
import { ChevronDown, ChevronLeft } from 'lucide-vue-next';
```

Same Menu → DropdownMenu pattern as AppLayout.

- [ ] **Step 3: Build and visual test**

```bash
vendor/bin/sail npm run build
```

Test:
- Mobile sidebar opens/closes on narrow viewport
- Profile dropdown works on both mobile and desktop
- Toast notifications still appear (trigger one from a staff page action)
- Navigation links work

- [ ] **Step 4: Commit**

```bash
git add resources/js/Layouts/AppLayout.vue resources/js/Components/Auth/AuthHeader.vue
git commit -m "Migrate layouts from HeadlessUI to shadcn/vue"
```

---

## Task 11: Migrate AvatarModal and remaining heroicon files

**Files:**
- Modify: `resources/js/Profile/AvatarModal.vue`
- Modify: `resources/js/Pages/Profile/Show.vue`
- Modify: `resources/js/Pages/Dashboard.vue`
- Modify: `resources/js/Pages/Staff/Dashboard.vue`
- Modify: `resources/js/Components/BaseAlert.vue`

- [ ] **Step 1: Migrate AvatarModal.vue — HeadlessUI Dialog → shadcn Dialog + heroicons → Lucide**

Before:
```vue
import { Dialog, DialogOverlay, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { CheckIcon } from '@heroicons/vue/24/outline';
```

After:
```vue
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Check } from 'lucide-vue-next';
```

Replace the TransitionRoot/Dialog/TransitionChild nesting with:
```vue
<Dialog v-model:open="open">
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Crop Avatar</DialogTitle>
        </DialogHeader>
        <!-- cropper and form content stays the same -->
    </DialogContent>
</Dialog>
```

shadcn Dialog handles transitions and overlay internally.

- [ ] **Step 2: Migrate remaining heroicon files**

These files only use heroicons (no PrimeVue or HeadlessUI):

**`Pages/Profile/Show.vue`:**
```javascript
// Remove: import { PencilIcon, PhoneIcon } from '@heroicons/vue/solid';
// Add:    import { Pencil, Phone } from 'lucide-vue-next';
```
Update template: `<PencilIcon>` → `<Pencil>`, `<PhoneIcon>` → `<Phone>`

**`Pages/Dashboard.vue`:**
```javascript
// Remove: import { ChevronRightIcon } from '@heroicons/vue/24/outline';
// Add:    import { ChevronRight } from 'lucide-vue-next';
```

**`Pages/Staff/Dashboard.vue`:**
```javascript
// Remove: import { ChevronRightIcon } from '@heroicons/vue/24/outline/index.js';
// Add:    import { ChevronRight } from 'lucide-vue-next';
```

**`Components/BaseAlert.vue`:**
```javascript
// Remove: import { ExclamationTriangleIcon } from '@heroicons/vue/24/solid/index.js';
// Add:    import { AlertTriangle } from 'lucide-vue-next';
```

Note: Lucide icon components don't have the `Icon` suffix. Also, Lucide icons use `class="h-5 w-5"` (or similar) for sizing — same as heroicons.

- [ ] **Step 3: Build and test**

```bash
vendor/bin/sail npm run build
```

Test avatar upload modal opens, cropper works, upload works. Verify icons display on Dashboard, Profile, Staff Dashboard, and BaseAlert.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Profile/AvatarModal.vue resources/js/Pages/Profile/Show.vue resources/js/Pages/Dashboard.vue resources/js/Pages/Staff/Dashboard.vue resources/js/Components/BaseAlert.vue
git commit -m "Migrate AvatarModal and remaining heroicons to Lucide"
```

---

## Task 12: Remove PrimeVue, Headless UI, heroicons, and old presets

**Files:**
- Modify: `package.json`
- Delete: `resources/js/presets/aura/` (92 files)
- Delete: `resources/js/presets/lara/` (entire directory)
- Delete: `resources/js/presets/` (now empty)

- [ ] **Step 1: Verify no remaining PrimeVue, HeadlessUI, or heroicon imports**

```bash
grep -r "primevue\|primeicons\|@headlessui\|@heroicons" resources/js/ --include="*.vue" --include="*.js"
```

Expected: No results. If any remain, go back and fix them.

- [ ] **Step 2: Remove npm packages**

```bash
vendor/bin/sail npm remove primevue primeicons @headlessui/vue @heroicons/vue
```

- [ ] **Step 3: Delete PrimeVue preset directories**

```bash
rm -rf resources/js/presets/
```

- [ ] **Step 4: Clean up app.css**

Remove the `.customized-primary` CSS block — it was PrimeVue-specific theming and is no longer referenced. Keep the `@theme` block (Tailwind v4 colors) and shadcn CSS variables.

- [ ] **Step 5: Build and full test**

```bash
vendor/bin/sail npm run build
```

Visit every page type:
- Login, Register, Forgot Password, Reset Password, Verify Email, Two Factor
- Dashboard
- Settings: Profile, Password, Two Factor (Authenticator + Yubikey)
- Staff: Groups list, Group tabs (Info, Members, Teams), Group member edit
- Profile page, Avatar upload
- Dark mode on all pages

- [ ] **Step 6: Run existing tests**

```bash
vendor/bin/sail artisan test --compact
```

Expected: All tests pass. The backend tests shouldn't be affected by frontend component changes.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Remove PrimeVue, HeadlessUI, heroicons, and old presets"
```

---

## Task 13: Final cleanup and smoke test

**Files:**
- Possibly modify: various files for CSS class cleanup

- [ ] **Step 1: Search for leftover PrimeVue CSS classes**

```bash
grep -rn "p-text-\|p-button\|p-input\|p-dialog\|p-component" resources/js/ --include="*.vue"
```

If found, replace with Tailwind equivalents:
- `p-text-secondary` → `text-muted-foreground`

- [ ] **Step 2: Search for leftover primeicons class usage**

```bash
grep -rn "pi pi-" resources/js/ --include="*.vue"
```

If found, replace with Lucide icon component imports.

- [ ] **Step 3: Verify bundle size**

```bash
vendor/bin/sail npm run build
```

Check the build output for bundle sizes. shadcn/vue with tree-shaking should be comparable or smaller than PrimeVue.

- [ ] **Step 4: Full visual regression test**

Open every page in the browser (both light and dark mode) and verify no visual regressions:
- Auth flow (login → dashboard → settings → logout)
- Staff flow (dashboard → groups → group detail tabs → member edit)
- Avatar upload modal
- Toast notifications

- [ ] **Step 5: Run full test suite**

```bash
vendor/bin/sail artisan test --compact
```

- [ ] **Step 6: Final commit**

```bash
git add -A
git commit -m "Final cleanup: remove leftover PrimeVue references"
```
