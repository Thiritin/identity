---
sidebar_position: 1
title: Understanding Tokens
---

# Understanding Tokens

After a successful login, Eurofurence Identity hands your application up to three different tokens. They look similar but serve very different purposes, and mixing them up is one of the most common sources of bugs and security issues. This page explains what each token is for, who is supposed to verify it, and how to use it correctly.

## The Three Tokens at a Glance

| Token | Format | For | Verified by | Inspectable? |
|-------|--------|-----|-------------|--------------|
| **ID Token** | Signed JWT | Proving who the user is | Your app (the client) | Yes, decode and verify |
| **Access Token** | Opaque string | Calling APIs on behalf of the user | The API (resource server) | **No**, treat as opaque |
| **Refresh Token** | Opaque string | Getting a new access token later | Eurofurence Identity | **No**, treat as opaque |

The golden rule: **the ID Token is for you, the access token is for the API, and the refresh token is a secret you trade in later.**

## ID Token

The ID Token is Eurofurence Identity's signed statement: *"I confirm that user X just successfully authenticated for your application."* It is a JWT containing claims about the user, signed with our private key.

### What's Inside

```json
{
  "iss": "https://identity.eurofurence.org/",
  "sub": "a1b2c3d4e5",
  "aud": "your-client-id",
  "exp": 1735689600,
  "iat": 1735686000,
  "sid": "session-id-for-backchannel-logout",
  "email": "user@example.com",
  "email_verified": true,
  "name": "BlueFox"
}
```

The exact claims you get depend on which scopes your app requested (see [Scopes](/identity/concepts/scopes)).

### What You Do With It

1. **Verify it.** This is required by the OpenID Connect spec. Any reputable OIDC library does this automatically, don't skip it.
   - Signature against our JWKS (`https://identity.eurofurence.org/.well-known/jwks.json`)
   - `iss` matches `https://identity.eurofurence.org/`
   - `aud` matches your `client_id`
   - `exp` is in the future
   - `nonce` matches the one you sent (if you used one)
2. **Trust the claims.** Once verified, you can use `sub`, `email`, `name`, etc. to create or look up the user in your database and render them in your UI.
3. **Store the `sid`.** You'll need it to match incoming [backchannel logout](/identity/integration/build-an-application#backchannel-logout) requests.

:::tip
The `sub` claim is your stable user identifier. Use it as a foreign key, never `email` or `name`, which can change.
:::

### What You Don't Do With It

- **Don't send the ID Token as a bearer token to APIs.** That's what the access token is for. ID Tokens are not meant to be used as API credentials, and the Eurofurence Identity API will reject them.
- **Don't forward it to other services.** The `aud` is your client_id; any other service would correctly reject it.

## Access Token

The access token is your app's credential for calling APIs on behalf of the user. In Eurofurence Identity it is an **opaque string**, a random handle, not a JWT. Your app should not try to decode it or look inside.

### How to Use It

Send it as a bearer token in the `Authorization` header:

```bash
curl https://identity.eurofurence.org/api/v2/userinfo \
  -H "Authorization: Bearer ACCESS_TOKEN"
```

That's it. The API validates the token on every request, checks scopes and audience, and decides whether to allow the call.

### Why It's Opaque (and Why That's Good)

Because access tokens carry no information the client can read, revocation is instant: the moment a user is banned, a consent is withdrawn, or a token is revoked, the very next API call fails. There is no cached JWT floating around that stays valid until it expires.

This is a deliberate design choice. It costs one server round-trip per API call, and in exchange, you get strong, immediate control.

### What You Don't Do With It

- **Don't try to parse or decode it.** It isn't a JWT; there's nothing inside you are meant to see.
- **Don't use it to check audience or identity yourself.** That information is for the API, not for you. If you need to know who the user is, use the ID Token or call `/userinfo`.
- **Don't log it, don't put it in URLs, don't store it longer than you need it.** Treat it like a password.

## Refresh Token

Refresh tokens let your app obtain a new access token without involving the user again. You only get one if you request the `offline_access` scope during login.

Exchange it at the token endpoint:

```bash
curl -X POST https://identity.eurofurence.org/oauth2/token \
  -d grant_type=refresh_token \
  -d refresh_token=YOUR_REFRESH_TOKEN \
  -d client_id=YOUR_CLIENT_ID \
  -d client_secret=YOUR_CLIENT_SECRET
```

You will receive a fresh access token (and possibly a new refresh token; if so, discard the old one and use the new one from now on).

Refresh tokens are long-lived and extremely sensitive. Store them server-side only, never in browsers or mobile clients without hardware-backed storage.

## How Do I Validate a Token?

This is where most integrations get confused. The right answer depends on *which* token and *what* you are trying to check.

### "Is the user who they claim to be?"

Verify the **ID Token** locally using JWKS. This is the ID Token's entire job. No network call needed after the initial JWKS fetch.

### "Is this access token still valid right now?"

Just use it. If it's expired or revoked, your API call will fail with `401`. You don't need a separate validation step; every call to our API already does full introspection server-side.

Calling `/api/v2/userinfo` is a perfectly reasonable liveness check: a `200` response proves the token is active, not revoked, and the user still exists.

### "Was this access token issued for me?"

If your app received the token through its own OIDC flow, the answer is *yes, by construction*. Eurofurence Identity issued it in response to your client's own request. There is nothing to check.

If your app received the token from somewhere else (forwarded by another service, passed through an untrusted context), then you do need to validate the audience. Use the [introspection endpoint](/identity/concepts/audiences#validating-tokens) and check that your audience identifier appears in the `aud` claim.

### "Do I need to call both `/userinfo` and `/introspect`?"

Almost never. Pick one based on what you actually need:

- Need user identity and claims → use the **ID Token** (locally) or `/userinfo` (remote).
- Need to know scopes, audience, expiry, client_id → use `/introspect`.
- Need both → you probably don't. Most of the data you'd fetch from introspection is either in the ID Token already, or irrelevant to your use case.

:::tip
If you find yourself calling both endpoints on every request, stop and ask: *what am I actually trying to protect against?* Usually the answer is in the ID Token you already verified at login.
:::

## What's Next?

- **[Build an Application](/identity/integration/build-an-application):** Full integration guide including backchannel logout
- **[Scopes](/identity/concepts/scopes):** Choose which claims and permissions your app needs
- **[Audiences](/identity/concepts/audiences):** When and how to validate the `aud` claim
