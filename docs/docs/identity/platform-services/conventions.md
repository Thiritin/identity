---
title: Conventions API
---

# Conventions API

Eurofurence Identity exposes the current and historical conventions as a first-class resource. Use this service when your app needs to know which convention is "live" right now, or when it needs to reason about attendance across years.

## What a Convention Is

A Convention represents one edition of Eurofurence (one year). The resource is intentionally small — it describes the event itself, not registration or attendance.

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Internal identifier for the convention. |
| `name` | string | Human-readable name, e.g. `Eurofurence 28`. |
| `year` | integer | The calendar year the convention takes place in. |
| `theme` | string \| null | The year's theme, if one has been announced. |
| `start_date` | string (`YYYY-MM-DD`) | First day of the convention. |
| `end_date` | string (`YYYY-MM-DD`) | Last day of the convention. |

There is no explicit `status` field. The **"current" convention** is derived at request time: it is the earliest convention whose `end_date` is today or in the future. In practice, that means while an event is running it is "current", and once it ends the next upcoming convention becomes "current". If no such convention exists, `/conventions/current` returns `404`.

The Convention resource is separate from attendance data. An app that needs to know whether a specific user is registered or attending should use the user/registration endpoints — the Conventions API only describes the events themselves.

## Listing Conventions

`GET /api/v2/conventions` returns every known convention, ordered by `year` ascending. The response is a bare JSON array (no `data` envelope), following the v2 convention for list endpoints. This list is small by nature (one entry per year) and is **not paginated** — no `Link` or `X-*` pagination headers are returned.

```bash
curl https://identity.eurofurence.org/api/v2/conventions
```

```json
[
  {
    "id": 27,
    "name": "Eurofurence 27",
    "year": 2023,
    "theme": "Fractures in Time",
    "start_date": "2023-09-20",
    "end_date": "2023-09-23"
  },
  {
    "id": 28,
    "name": "Eurofurence 28",
    "year": 2024,
    "theme": "Cyberpunk",
    "start_date": "2024-09-18",
    "end_date": "2024-09-21"
  }
]
```

## Fetching the Current Convention

`GET /api/v2/conventions/current` returns a single Convention object — the running or next upcoming edition. Because it returns a single resource, the body is a bare object (no `data` wrapping).

```bash
curl https://identity.eurofurence.org/api/v2/conventions/current
```

```json
{
  "id": 28,
  "name": "Eurofurence 28",
  "year": 2024,
  "theme": "Cyberpunk",
  "start_date": "2024-09-18",
  "end_date": "2024-09-21"
}
```

If every known convention is already in the past, this endpoint responds with `404 Not Found`. Clients should handle that case explicitly — it is the normal "between years, nothing announced yet" state, not an error condition.

## Scopes and Auth

Both `/api/v2/conventions` and `/api/v2/conventions/current` are **public**. They sit outside the `auth:api` middleware group and require no access token and no OAuth scopes — you may call them anonymously or with a bearer token; either works. If you are already passing a token for other requests, there is no harm in sending it here:

```bash
curl -H "Authorization: Bearer ACCESS_TOKEN" \
  https://identity.eurofurence.org/api/v2/conventions/current
```

This makes the Conventions API safe to call from unauthenticated landing pages, pre-login splash screens, and background jobs that have no user context.

## Reference

See the [API v2 reference](/identity/api/v2/conventions) for the full endpoint list.
