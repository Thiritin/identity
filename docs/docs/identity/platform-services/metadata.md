---
title: User Metadata
---

# User Metadata

The User Metadata API is a small key/value store attached to each user, scoped to the calling OAuth client. Reach for it when you need to persist lightweight per-user state — UI preferences, onboarding flags, last-used settings — without standing up a database of your own.

## Data Model

Metadata entries live in the `user_app_metadata` table and are uniquely identified by the triple `(user_id, client_id, key)`:

| Field        | Type                  | Notes                                                       |
| ------------ | --------------------- | ----------------------------------------------------------- |
| `key`        | string                | URL-safe: must match `[a-zA-Z0-9._-]+`.                     |
| `value`      | string (TEXT column)  | Required; max **65535** characters.                         |
| `expires_at` | ISO-8601 datetime/null | Optional; if set, must be in the future. Expired entries are purged by a scheduled job. |

**Scoping is per OAuth client, not global.** The `client_id` is taken from the access token used to call the API, so two different applications writing to the key `theme` for the same user keep completely separate values. There is no way to read another client's metadata.

Values are opaque strings. If you need structured data, serialize to JSON yourself before PUT and parse on read.

## Reading Metadata

List every key the calling app has stored for the authenticated user:

```bash
curl https://identity.eurofurence.org/api/v2/metadata \
  -H "Authorization: Bearer $ACCESS_TOKEN"
```

```json
{
  "data": [
    { "key": "theme",  "value": "dark", "expires_at": null },
    { "key": "locale", "value": "en",   "expires_at": null }
  ]
}
```

Fetch a single key:

```bash
curl https://identity.eurofurence.org/api/v2/metadata/theme \
  -H "Authorization: Bearer $ACCESS_TOKEN"
```

```json
{ "key": "theme", "value": "dark", "expires_at": null }
```

A missing key returns `404 Not Found`.

## Writing Metadata

`PUT` is create-or-update (upsert). The URL path carries the key; the JSON body carries the value.

```bash
curl -X PUT https://identity.eurofurence.org/api/v2/metadata/theme \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"value": "dark"}'
```

Returns `201 Created` on first write, `200 OK` on subsequent updates. The response body is the stored resource:

```json
{ "key": "theme", "value": "dark", "expires_at": null }
```

You can also set an optional expiry:

```bash
curl -X PUT https://identity.eurofurence.org/api/v2/metadata/onboarding_banner \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"value": "dismissed", "expires_at": "2026-12-31T23:59:59Z"}'
```

`expires_at` must be in the future. Expired entries are removed by a scheduled prune job.

## Deleting Metadata

```bash
curl -X DELETE https://identity.eurofurence.org/api/v2/metadata/theme \
  -H "Authorization: Bearer $ACCESS_TOKEN"
```

Returns `204 No Content` on success, `404 Not Found` if the key does not exist for this user and client.

## Scopes and Auth

All endpoints require a user-bearing access token on the `auth:api` guard (i.e. issued via the normal OAuth2 authorization code flow, not raw client credentials — the token must resolve to a user).

| Operation           | Required scope    |
| ------------------- | ----------------- |
| `GET  /metadata`    | `metadata.read`   |
| `GET  /metadata/{key}` | `metadata.read`   |
| `PUT  /metadata/{key}` | `metadata.write`  |
| `DELETE /metadata/{key}` | `metadata.write`  |

Requests missing the required scope are rejected with `403 Forbidden`.

## Reference

See the [API v2 reference](/identity/api/v2/user-metadata) for the full endpoint list.
