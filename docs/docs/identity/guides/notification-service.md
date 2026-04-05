---
sidebar_position: 6
title: Notification Service
---

# Notification Service

The Eurofurence Identity service provides a centralized notification system that first-party OAuth apps can use to send notifications to users through email, Telegram, and the in-app notification bell.

## What it is

A single API endpoint (`POST /api/v2/notifications`) lets your app dispatch notifications to individual users. The IDP handles delivery across channels, respecting per-user preferences. Users can choose which types of notifications they receive and through which channels via their portal settings.

## Getting access

To use the notification service, your app must have `allow_notifications` enabled by an IDP administrator. Contact the identity team to request access.

Once enabled, a new **Notifications** tab appears in **My Apps → [your app]** where you can register notification types.

## Registering notification types

Every notification must reference a notification type you've registered for your app. Types have:

- **Key** — a short slug like `payment_reminder`. Immutable after creation. Unique per app.
- **Display name** — shown to users in their preferences
- **Description** — optional explanation shown to users
- **Category** — see below. Immutable after creation.
- **Default channels** — which channels this type goes to by default (email, telegram, or in-app)

## Choosing a category

| Category | User control | Use for |
|---|---|---|
| **Transactional** | Cannot be disabled. Always delivers via default channels. Must include email. | Password resets, payment confirmations, security alerts — anything the user must see |
| **Operational** | Users can pick channels but the type is on by default | Registration updates, role changes, booking confirmations |
| **Informational** | Users can pick channels; may be disabled | Schedule changes, announcements, reminders |
| **Promotional** | Off by default; users must opt in | Newsletters, surveys, product announcements |

## Sending a notification

Obtain a client credentials token with the `notifications.send` scope, then:

```bash
curl -X POST https://identity.eurofurence.org/api/v2/notifications \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "payment_reminder",
    "user_id": "1VJEQAYWW54TZ5VD",
    "subject": "Your registration payment is due",
    "body": "You have 7 days to complete payment.",
    "cta": {
      "label": "Pay now",
      "url": "https://reg.eurofurence.org/pay"
    }
  }'
```

A successful call returns `202 Accepted` with an empty body. Notifications are delivered asynchronously.

## Content format

- `subject` (required) — plain text, max 255 characters
- `body` (required) — plain text, max 10,000 characters. Used by Telegram, the in-app bell, and by email when `html` is not provided.
- `html` (optional) — HTML body used **only** by the email channel. When present, email renders this verbatim and ignores `body` for its output. Telegram and the bell still use `body`.
- `cta` (optional) — a call-to-action with `label` and `url`. Both fields required together, or omit the object entirely.

### Writing the `body`

`body` is plain text. No Markdown, no HTML — special characters are rendered literally.

**Paragraphs**: separate them with a blank line (two newlines `\n\n`). Each paragraph renders as its own block in email, gets a visible line break on Telegram, and wraps naturally in the bell feed.

**Single newlines within a paragraph** collapse to a space in email. Telegram preserves them. If you need guaranteed hard breaks in email, send `html` instead.

Example:

```json
{
  "body": "Your payment of €50 was received.\n\nYou can manage your registration at any time from your dashboard. Thanks for attending!"
}
```

This produces two paragraphs everywhere it's delivered.

If you need rich formatting (bold, lists, images, links) in email, use `html`:

```json
{
  "body": "Your payment of €50 was received. Manage your registration from your dashboard.",
  "html": "<p>Your payment of <strong>€50</strong> was received.</p><p>Manage your registration from your <a href=\"https://reg.eurofurence.org\">dashboard</a>.</p>"
}
```

Always include a meaningful `body` even when you provide `html` — Telegram and the bell feed always use `body`, never `html`.

## Channels and user preferences

The IDP resolves delivery channels per notification by:

1. Starting with the type's `default_channels`
2. Applying the user's per-type overrides (if any)
3. Applying the user's master channel switches
4. For **transactional** types, restoring the full default channel set (user preferences are ignored)

If the resolved list includes Telegram but the user hasn't linked a Telegram account, that channel is silently skipped. Other channels still deliver.

## Rate limits

Each app is limited to **60 notifications per minute**. When exceeded, the API responds with `429 Too Many Requests` and a `Retry-After` header indicating when to retry.

## Error handling

| Status | Meaning | Action |
|---|---|---|
| `403` | Missing `notifications.send` scope or `allow_notifications` not enabled on your app | Check scope + contact admin |
| `404` | Notification type key not found or user id invalid | Verify registration and the user id |
| `422` | Validation error (missing fields, bad CTA shape, etc.) | Fix the payload |
| `429` | Rate limited | Respect `Retry-After` and retry |
