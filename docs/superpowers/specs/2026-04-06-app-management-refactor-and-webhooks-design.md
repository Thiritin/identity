# Developer App Management Refactor + First-Party Webhooks

**Status:** Draft
**Date:** 2026-04-06
**Owner:** identity team

## 1. Summary

Two linked changes to the developer-facing app management surface:

1. **Refactor the developer portal's app management UI.** The create form is reduced to three fields (name, one redirect URL, scopes). The app detail page is replaced with a route-based left-sidebar layout where each section (General, OAuth, Logout, Credentials, Webhooks, Danger zone) is its own deep-linkable Inertia page. The monolithic `AppsController@update` splits into per-section update endpoints with per-section FormRequests.
2. **Add first-party webhooks.** First-party apps can register a single webhook URL with a rotatable signing secret, subscribe to a subset of user profile fields (`email`, `username`), and receive HMAC-signed `user.updated` POST callbacks whenever a subscribed field changes. Every dispatch is recorded in a `webhook_deliveries` table with a 7-day retention; developers see a paginated delivery history with payloads, response codes, errors, and a re-deliver action. The flow is documented in Docusaurus.

These two pieces ship together because the new Webhooks section only exists inside the refactored detail page, and the new per-section controllers are the natural home for the webhook endpoints.

## 2. Goals and non-goals

### Goals

- Minimal creation form so getting an OAuth client takes seconds.
- Clear, discoverable, deep-linkable settings sections for existing apps.
- Reliable `user.updated` webhook delivery to first-party apps with retries and developer-visible debugging (payload, status, response body, errors).
- Verifiable signatures using a per-app secret the developer can reveal and rotate.
- Docusaurus documentation covering payload shape, headers, signing, verification, and troubleshooting.
- Thorough test coverage of dispatch rules, signing, retry/log behavior, subscription filtering, first-party gating, and every new route.

### Non-goals

- Events other than `user.updated`.
- Subscribable fields other than `email` and `username`. (The UI has a free-text "event name" field per product intent, but the backend hardcodes `user.updated`.)
- Multiple webhook endpoints per app.
- Third-party webhooks.
- Dead-letter alerting, external observability, retention beyond 7 days.
- Adding outbound webhooks to `identity.oas.2.0.yml` (OpenAPI 2.0 has no webhook construct; covered in Docusaurus instead).

## 3. Data model

### 3.1 Changes to `apps`

Single migration adds four columns, all owned by us (not inside the Hydra-owned `data` JSON):

| Column | Type | Notes |
|---|---|---|
| `webhook_url` | `string` nullable | Target URL. |
| `webhook_secret` | `text` nullable | Laravel `encrypted` cast. Plaintext revealable and rotatable by the owner. |
| `webhook_subscribed_fields` | `json` nullable | Allowlisted subset, e.g. `["email","username"]`. |
| `webhook_event_name` | `string(64)` nullable | Free-text label surfaced in the UI; backend treats dispatch as `user.updated` regardless. |

All four are writable only when `apps.first_party = true`. Enforced by the form request and `AppPolicy@manageWebhooks`.

### 3.2 New table `webhook_deliveries`

```
id                 ulid (primary)
app_id             fk apps.id, cascade delete, indexed
event              string           // "user.updated"
url                string           // snapshot at send time
payload            json             // exact body sent
signature          string           // value of X-EF-Signature
status             enum('pending','delivered','failed','retrying')
attempts           unsigned tinyint
response_code      unsigned smallint nullable
response_body      text nullable    // truncated to 2048 bytes
error              text nullable
next_retry_at      timestamp nullable
delivered_at       timestamp nullable
created_at         timestamp
```

Indexes:

- `(app_id, created_at desc)` — developer list view.
- `(created_at)` — pruning sweep.

Status transitions:

```
pending -> delivered          (2xx on any attempt)
pending -> retrying -> ...    (non-2xx / exception, attempt < max)
retrying -> delivered
retrying -> failed            (attempts exhausted or job failed() hook)
```

`response_body` is truncated at 2048 bytes on write; bigger bodies are stored with an explicit `…(truncated)` suffix. `payload` is the exact bytes the HTTP client sent, so what developers see in the UI is what their app received.

### 3.3 Pruning

`php artisan webhooks:prune-deliveries` — deletes rows where `created_at < now()->subDays(7)`. Registered in the scheduler to run daily at 03:00 UTC.

### 3.4 Eloquent

- New `WebhookDelivery` model with a `belongsTo(App::class)`.
- `App::webhookDeliveries()` HasMany.
- `App` adds the four columns to `$fillable` and sets `webhook_secret` + `webhook_subscribed_fields` casts.

## 4. Webhook dispatch pipeline

### 4.1 Trigger — `User` model observer

A single `UserObserver::updated()` is the only dispatch site. In `updated()`:

1. Read `$user->getChanges()`.
2. Intersect keys with the allowlist `['email', 'username']`.
3. If non-empty, call `WebhookDispatcher::dispatchUserUpdated($user, $oldValues, $changedFields)` with old values snapshotted from `$user->getOriginal()`.

Using an observer means every future code path that mutates the user flows through the same hook — we do not have to remember to add calls in `EmailController`, a username change endpoint, or a future admin edit.

**Only `updated` is observed**, not `created`. Account creation is not a profile change and has no "old value"; consuming apps receive new users via the normal login/`/userinfo` flow.

### 4.2 `WebhookDispatcher` service

Pure service (no framework facades inside), injected where needed:

1. Queries first-party apps where `webhook_url is not null` and `webhook_subscribed_fields` intersects the changed fields.
2. For each match, computes the filtered `changed` payload (only subscribed fields), creates a `webhook_deliveries` row with `status=pending`, and dispatches one `DeliverWebhook` job per delivery id.

Separating the dispatcher from the observer lets us unit test dispatch rules without touching HTTP or models beyond fixtures.

### 4.3 `DeliverWebhook` job

- Queue: `webhooks` (new supervisor in `config/horizon.php`).
- `$tries = 6`.
- `public function backoff(): array` returns `[10, 60, 300, 1800, 7200, 21600]` — 10s, 1m, 5m, 30m, 2h, 6h.
- `$timeout = 15` (8s HTTP connect timeout, 15s total request timeout enforced by Guzzle config).

Execution (retry bookkeeping is driven by throwing — Horizon/Laravel handle the reschedule via the configured `backoff()`; we do **not** use `release()` in parallel):

1. Load delivery. If already `delivered` or the app is soft-deleted/missing, return.
2. Build headers (see 4.5).
3. POST via Laravel HTTP client.
4. On 2xx: set `status=delivered`, `response_code`, truncated `response_body`, `delivered_at`, `attempts = $this->attempts()`. Job completes.
5. On non-2xx or throwable, before rethrowing: set `status=retrying` (only if more attempts remain — otherwise leave for the `failed()` hook), record `attempts = $this->attempts()`, `response_code`/`error`, `next_retry_at = now()->addSeconds(backoff[attempts])`. Rethrow so the worker reschedules.
6. `failed()` hook (runs after the final attempt): mark delivery `status=failed`, persist final error and `response_code` if not already stored.

`pending` is the pre-first-attempt state written by `WebhookDispatcher`. As soon as `DeliverWebhook` runs, the status is always one of `delivered`, `retrying`, or `failed`.

### 4.4 Payload

Fat payload — includes new values so consuming apps don't have to round-trip to `/userinfo`:

```json
{
  "event": "user.updated",
  "id": "01HX…delivery_ulid",
  "occurred_at": "2026-04-06T12:34:56Z",
  "subject": "42",
  "changed": {
    "email":    { "old": "a@example.com", "new": "b@example.com" },
    "username": { "old": "old_handle",    "new": "new_handle"    }
  }
}
```

`changed` only contains keys in the app's `webhook_subscribed_fields` **and** in the actual diff. `subject` is the user's sub/id, matching the `sub` claim consuming apps already have.

### 4.5 Signing and headers

```
Content-Type:    application/json
User-Agent:      EF-Identity-Webhooks/1.0
X-EF-Event:      user.updated
X-EF-Delivery:   <delivery ulid>
X-EF-Timestamp:  <unix seconds>
X-EF-Signature:  v1,<hex(hmac_sha256(secret, timestamp + "." + raw_body))>
```

Verification contract (documented and enforced by tests):

1. Recompute the HMAC over `X-EF-Timestamp + "." + raw_body` using the app's secret.
2. Constant-time compare against the value after `v1,`.
3. Reject if `|now - X-EF-Timestamp| > 300` (replay window).

`WebhookSigner` is a pure class so the signing algorithm is unit-tested against known vectors independent of HTTP.

## 5. Developer portal refactor

### 5.1 Simplified create form

`resources/js/Pages/Settings/Apps/AppCreate.vue` keeps exactly three inputs:

- **Name** → `client_name`, required, string max 255.
- **Redirect URL** → single URL input, required. Submitted as `redirect_uris: [value]`.
- **Scopes** → checkbox list of unrestricted scopes, defaults to `['openid']`.

Removed from create: icon, description, app URL, developer name, privacy/ToS URLs, post-logout URIs, front/back-channel URIs, first-party toggle. Staff-only first-party assignment still happens server-side based on `auth()->user()->isStaff()`.

`StoreAppRequest` rules shrink to match. On success, redirect to `/developers/{app}/general` so developers land directly in the new detail layout to fill in whatever else they need.

### 5.2 Route-based detail layout

Replace `AppShow.vue` and `AppEdit.vue` with a shared `resources/js/Pages/Settings/Apps/AppDetail/Layout.vue` used by a family of section pages. The layout renders the left sidebar and the active child section.

Routes (all under `developers.` name prefix):

```
GET  /developers/{app}                         -> redirect to general
GET  /developers/{app}/general                 -> AppDetail/General.vue
GET  /developers/{app}/oauth                   -> AppDetail/OAuth.vue
GET  /developers/{app}/logout                  -> AppDetail/Logout.vue
GET  /developers/{app}/credentials             -> AppDetail/Credentials.vue
GET  /developers/{app}/webhooks                -> AppDetail/Webhooks.vue           [first-party]
GET  /developers/{app}/danger                  -> AppDetail/Danger.vue
PUT  /developers/{app}/general                 -> AppsController@updateGeneral
PUT  /developers/{app}/oauth                   -> AppsController@updateOAuth
PUT  /developers/{app}/logout                  -> AppsController@updateLogout
PUT  /developers/{app}/webhooks                -> AppWebhookController@update       [first-party]
POST /developers/{app}/webhooks/reveal-secret  -> AppWebhookController@revealSecret [first-party]
POST /developers/{app}/webhooks/rotate-secret  -> AppWebhookController@rotateSecret [first-party]
POST /developers/{app}/webhooks/test           -> AppWebhookController@sendTest     [first-party]
GET  /developers/{app}/webhooks/deliveries     -> AppWebhookController@deliveries   [first-party]
POST /developers/{app}/webhooks/deliveries/{delivery}/redeliver
                                               -> AppWebhookController@redeliver   [first-party]
POST /developers/{app}/regenerate-secret       -> existing
DELETE /developers/{app}                       -> existing
```

Sidebar items come from a shared JS constant; the Webhooks entry is hidden when `!app.first_party`. Each section owns its own `useForm` so there is no cross-section dirty-state leakage.

The existing monolithic `AppsController@update` splits into `updateGeneral` / `updateOAuth` / `updateLogout`, each with its own FormRequest (`UpdateAppGeneralRequest`, `UpdateAppOAuthRequest`, `UpdateAppLogoutRequest`) under `app/Http/Requests/Developer/`.

### 5.3 Webhooks section UI

Form fields:

- **Webhook URL** — text/URL input.
- **Event name** — free-text field (frontend only; backend hardcodes `user.updated`).
- **Subscribed fields** — two checkboxes: `email`, `username`.
- **Signing secret** — read-only display with **Reveal** (POST to `revealSecret`, returns plaintext to authorized user, logged to activity log) and **Rotate** (POST to `rotateSecret`, replaces the secret, invalidates all previous signatures).
- **Send test delivery** — POST to `sendTest`. Requires the webhook URL to already be **saved** (validates against the persisted `webhook_url`, not unsaved form state — the button is disabled in the UI until the section is saved). Creates a synthetic `user.updated` delivery for the authenticated user's own id and dispatches the real job pipeline, so test deliveries flow through the same code path and show up in the delivery log.

Below the form, a **Recent deliveries** table paginated 25/page, sourced from `GET webhooks/deliveries`:

| When | Event | Status | HTTP | Attempts | Actions |
|---|---|---|---|---|---|

Row click opens a drawer showing the full JSON payload, the signature header, response code, truncated response body, error message, and `next_retry_at`. A **Redeliver** button creates a new delivery row with the same payload bytes (so history is append-only) and dispatches a fresh `DeliverWebhook` job.

### 5.4 Authorization

- `AppPolicy@update` — reused by all section update endpoints.
- `AppPolicy@manageWebhooks(User, App)` — new: allows only if the user can update the app and `app.first_party === true`. Guards every webhook route and the `UpdateAppWebhookRequest::authorize()`.
- `AppPolicy@viewWebhookSecret(User, App)` — new: same rule as `manageWebhooks`; enforced by `revealSecret`. Listed again in §6 under new policy methods.

`revealSecret` and `rotateSecret` are always `POST` so secrets never land in access logs. Both write an activity log entry via the existing activity log package (already used by `ActivityResource`).

## 6. Services and structure

- `app/Services/Webhooks/WebhookSigner.php` — pure class: `sign(string $secret, int $timestamp, string $body): string` and `header(string $signature): string`. No dependencies on HTTP or models.
- `app/Services/Webhooks/WebhookDispatcher.php` — creates delivery rows and dispatches jobs given a user + field diff.
- `app/Jobs/Webhooks/DeliverWebhook.php` — single-delivery HTTP job with retries.
- `app/Observers/UserObserver.php` — observes `updated`, delegates to `WebhookDispatcher`. Registered in `AppServiceProvider::boot()`.
- `app/Policies/AppPolicy.php` — add `manageWebhooks` and `viewWebhookSecret` methods (see §5.4).
- `app/Http/Controllers/Profile/Settings/AppsController.php` — keeps index/create/store/destroy; `update` splits into `updateGeneral`/`updateOAuth`/`updateLogout`.
- `app/Http/Controllers/Profile/Settings/AppWebhookController.php` — new: show/update/revealSecret/rotateSecret/sendTest/deliveries/redeliver.
- `app/Http/Requests/Developer/{StoreAppRequest,UpdateAppGeneralRequest,UpdateAppOAuthRequest,UpdateAppLogoutRequest,UpdateAppWebhookRequest}.php` — replace the existing `Staff\StoreAppRequest` / `Staff\UpdateAppRequest`. (Those were misnamed — these are developer endpoints.)
- `app/Console/Commands/PruneWebhookDeliveries.php` — prune command.

## 7. Tests

All tests in `tests/Feature` unless otherwise noted.

### Unit

1. **`WebhookSignerTest`** — known-vector signing, header string format, verifies constant-time comparison behavior with tampered hex.
2. **`WebhookDispatcherTest`** — dispatches only for first-party apps with `webhook_url` set and intersecting subscribed fields; skips apps without intersection; does not dispatch when no allowlisted field changed; filters `changed` to subscribed intersection.

### Feature — delivery pipeline (HTTP faked via `Http::fake()`)

3. Updating `User.email` triggers `DeliverWebhook`, creates a delivery row, marks `delivered` on 2xx, stores truncated response body.
4. Updating `User.username` works the same way.
5. Updating a non-subscribed field (e.g. `first_name`) does not dispatch and does not create a delivery row.
6. Third-party app with a `webhook_url` somehow set does not receive a dispatch; the form request rejects the field with 422 on save.
7. Non-2xx response sets `status=retrying`, increments `attempts`, populates `response_code`, `error`, `next_retry_at`; after final attempt sets `failed`.
8. Signature header matches `WebhookSigner` output for the exact body bytes.
9. `X-EF-Timestamp` matches the dispatch time; replay-protection rejection is covered by the signer test with a stale timestamp.
10. Payload contains only subscribed-and-changed keys (e.g. `changed.email.old/new` but no `username` when only email changed).

### Feature — developer portal

11. Create form accepts only `client_name`, `redirect_uris`, `scope`; other keys are ignored/rejected.
12. Create redirects to `/developers/{app}/general` on success.
13. Each detail section route renders its Inertia component with correct props; non-owner gets 403.
14. Webhooks section returns 403 for a third-party app owner and 200 for first-party.
15. `updateWebhooks` persists URL, fields, event name, and seeds a secret on first save.
16. `revealSecret` returns plaintext to authorized users, 403 otherwise, writes an activity log entry.
17. `rotateSecret` issues a new secret; signatures built with the old secret no longer verify.
18. `sendTest` creates a delivery row with a synthetic payload for the authenticated user and dispatches the job.
19. `deliveries` index lists recent rows filtered by app, paginated 25/page.
20. `redeliver` creates a new delivery row referencing the same payload bytes and dispatches a fresh job.
21. `webhooks:prune-deliveries` deletes rows older than 7 days and leaves newer rows alone.

## 8. Documentation (Docusaurus)

- **New:** `docs/docs/identity/integration/webhooks.md` — concept, first-party-only gating, `user.updated` event, field subscriptions, payload schema with annotated example, header list, signature algorithm, PHP + Node verification snippets, retry/backoff table, 7-day delivery history, "Send test delivery" workflow, troubleshooting 4xx/5xx.
- **Update:** `docs/docs/identity/integration/build-an-application.md` — add a link to the webhooks page from the "after your app is running" section.
- **Update:** `docs/sidebars.ts` — register the new page under Integration.

The payload JSON example and signing pseudocode in the docs are authored to match the `WebhookSigner` test vectors so the docs and implementation cannot drift silently.

OpenAPI 2.0 (`identity.oas.2.0.yml`) is **not** touched — it models inbound endpoints, and OpenAPI 2.0 has no webhook construct. Docusaurus is the authoritative source for the outbound contract.

## 9. Rollout / safety

- Migration is additive (new columns, new table). No destructive changes to existing columns.
- New `webhooks` Horizon queue is added to supervisors; rollout requires a Horizon reload.
- The `User` observer is the only dispatch site. If webhooks need to be globally disabled, a single config flag (`services.webhooks.enabled`) is checked in `WebhookDispatcher` and short-circuits dispatch without touching the observer wiring.
- No third-party apps are affected; the feature is dark for them.

## 10. Open questions

None at design time. All product decisions (payload shape, endpoint cardinality, secret storage, retention, delivery log UX) were locked during brainstorming.
