# Metadata Expiry Design

## Motivation

Apps store user metadata (e.g. shipping addresses) via the v2 metadata API. Under data-minimization / storage-limitation principles, PII should not be retained indefinitely when the user is inactive. This spec adds a per-key expiry mechanism so apps can declare when their metadata should be deleted.

## Scope

Add optional `expires_at` to `user_app_metadata`, allow apps to set it on write, and delete expired rows via a daily scheduled job.

### Out of scope

- Lazy filtering of expired rows at read time.
- Per-client default TTL or client-level retention config.
- Sliding window / refresh-on-read behavior.
- User-facing notifications before expiry.

## Design decisions

1. **Who sets expiry:** The writing app, per key, on each write. Null/omitted means never expires.
2. **Activity model:** Only explicit writes affect `expires_at`. Reads are side-effect-free.
3. **Deletion strategy:** Daily scheduled job hard-deletes expired rows. No read-time filtering — there is a known window (up to 24h) between expiry and deletion where expired data is still returned. Accepted trade-off for simplicity.
4. **API shape:** Absolute ISO 8601 timestamp in the request body (`expires_at`), not a relative TTL.
5. **Omission semantics:** On upsert, omitting `expires_at` (or sending null) sets it to null = never expires. Each write fully specifies the intent; there is no "leave unchanged" behavior. Apps that want to preserve an existing expiry must re-send it.

## Implementation

### Schema

Migration adds a nullable `expires_at` timestamp column to `user_app_metadata`, with an index to support the cleanup query:

```
$table->timestamp('expires_at')->nullable()->index();
```

### Model (`app/Models/UserAppMetadata.php`)

- Add `expires_at` to `$fillable`.
- Add `expires_at` to `$casts` as `datetime`.

### Request (`app/Http/Requests/Api/v2/UpsertMetadataRequest.php`)

Add validation:

- `expires_at` is optional.
- When present, must be a valid date and `after:now`.
- Null is allowed and means "never expires."

### Controller (`app/Http/Controllers/Api/v2/MetadataController.php`)

`upsert` passes `expires_at` through to `updateOrCreate`'s values array. When the field is omitted or explicitly null in the request, the stored value becomes null (omission = clear).

### Resource (`app/Http/Resources/V2/MetadataResource.php`)

Include `expires_at` in the serialized output so apps can see the current expiry.

### Cleanup job

New artisan command `metadata:prune-expired`:

```php
$count = UserAppMetadata::where('expires_at', '<', now())->delete();
Log::info("Pruned {$count} expired metadata rows");
```

Registered in `routes/console.php` (or `app/Console/Kernel.php` per project convention) to run daily.

## Tests

Feature tests in `tests/Feature/Api/v2/MetadataTest.php`:

1. Upsert with valid future `expires_at` persists it and returns it in the response.
2. Upsert with past `expires_at` returns 422.
3. Upsert without `expires_at` on a row that previously had one clears it to null.
4. `GET` includes `expires_at` in the response body.

Unit/feature test for the prune command:

5. `metadata:prune-expired` deletes rows whose `expires_at` is in the past.
6. Leaves rows with null `expires_at` untouched.
7. Leaves rows with future `expires_at` untouched.

## Migration / rollout

- No backfill needed: existing rows get null `expires_at` (never expires), which preserves current behavior.
- No client changes required; `expires_at` is opt-in per write.
