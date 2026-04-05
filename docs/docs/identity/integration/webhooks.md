---
sidebar_position: 4
title: Webhooks
---

# Webhooks

## What are Webhooks

Identity can notify your application in real-time when user profile data changes by sending HTTP POST requests to a URL you specify. Webhooks are currently available for **first-party apps only** — that is, apps created by Eurofurence staff.

## Enabling Webhooks

Navigate to the developer portal, open your app, and go to the **Webhooks** section. Set your webhook URL, select which fields you want to subscribe to, and save. A signing secret is generated automatically on first save and is displayed once — store it securely.

## Events

Only one event type is currently supported:

| Event | Description |
|-------|-------------|
| `user.updated` | Triggered when a user changes their email address or username |

## Subscribing to Fields

You can subscribe to either or both of the following fields:

| Field | Description |
|-------|-------------|
| `email` | The user's email address |
| `username` | The user's username |

Your webhook will only fire when a field you have subscribed to actually changes. If a user updates a field you are not subscribed to, no delivery is made.

## Payload Schema

Each delivery sends a JSON body with the following structure:

```json
{
  "event": "user.updated",
  "id": "01HXJ...",
  "occurred_at": "2026-04-06T12:34:56Z",
  "subject": "42",
  "changed": {
    "email": {
      "old": "old@example.com",
      "new": "new@example.com"
    }
  }
}
```

| Field | Description |
|-------|-------------|
| `event` | The event name (`user.updated`) |
| `id` | Unique delivery ID (ULID) |
| `occurred_at` | ISO 8601 timestamp of when the change occurred |
| `subject` | The user's numeric ID |
| `changed` | Object containing only the subscribed fields that actually changed, each with `old` and `new` values |

## Headers

Every delivery includes the following HTTP headers:

| Header | Example | Description |
|--------|---------|-------------|
| `Content-Type` | `application/json` | Always JSON |
| `User-Agent` | `EF-Identity-Webhooks/1.0` | Identifies the sender |
| `X-EF-Event` | `user.updated` | The event type |
| `X-EF-Delivery` | `01HXJ...` | Unique delivery ID |
| `X-EF-Timestamp` | `1712412896` | Unix timestamp of dispatch |
| `X-EF-Signature` | `v1,abc123...` | HMAC signature for verification |

## Verifying Signatures

Each delivery is signed with HMAC-SHA-256. The signature is computed over `"<timestamp>.<raw_body>"` using your app's signing secret, and prefixed with `v1,`.

Always verify signatures before processing a delivery. Use the raw request body — not a parsed or re-serialized version — for the HMAC computation.

**PHP:**

```php
$timestamp = $request->header('X-EF-Timestamp');
$signature = $request->header('X-EF-Signature');
$body = $request->getContent();

$expected = 'v1,' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);

if (! hash_equals($expected, $signature)) {
    abort(401, 'Invalid signature');
}

if (abs(time() - (int) $timestamp) > 300) {
    abort(401, 'Timestamp too old — possible replay');
}
```

**Node.js:**

```js
const crypto = require('crypto');

function verifyWebhook(secret, timestamp, body, signature) {
  const expected = 'v1,' + crypto
    .createHmac('sha256', secret)
    .update(`${timestamp}.${body}`)
    .digest('hex');

  if (!crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(signature))) {
    throw new Error('Invalid signature');
  }

  if (Math.abs(Date.now() / 1000 - Number(timestamp)) > 300) {
    throw new Error('Timestamp too old');
  }
}
```

:::tip
The 300-second tolerance guards against replay attacks. Reject deliveries whose timestamp is more than 5 minutes old.
:::

## Retries

If your endpoint returns a non-2xx status or times out, Identity retries delivery with exponential backoff:

| Attempt | Delay |
|---------|-------|
| 1 | 10 seconds |
| 2 | 1 minute |
| 3 | 5 minutes |
| 4 | 30 minutes |
| 5 | 2 hours |
| 6 | 6 hours |

After 6 failed attempts, the delivery is marked as `failed`. Each attempt is recorded individually in your delivery history.

## Delivery History

All deliveries are stored for 7 days. In your app's Webhooks section you can view:

- The full request payload
- The HTTP response code returned by your endpoint
- Any error details
- Retry status

You can redeliver any past delivery directly from the UI.

## Testing

Click **Send test delivery** in the Webhooks section to dispatch a synthetic `user.updated` event using your own account as the subject. The test flows through the same signing and delivery pipeline and appears in your delivery history.

## Troubleshooting

- **4xx from your endpoint:** Check that your URL is publicly reachable and that the endpoint accepts POST requests.
- **Signature verification fails:** Ensure you are using the raw request body (not parsed JSON) as input to the HMAC computation.
- **Deliveries stop arriving:** Check that your app still has first-party status and that the webhook URL is saved.
- **Old deliveries have disappeared:** Delivery history has a 7-day retention window.
