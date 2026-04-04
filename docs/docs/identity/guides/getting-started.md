---
sidebar_position: 0
title: Getting Started
---

# Getting Started

This guide walks you through the basics of integrating with Eurofurence Identity — from understanding how authentication works to making your first API call.

## Prerequisites

Before you begin, you'll need:

- A registered OAuth2 client (see [Registering Your Application](/identity/guides/build-an-application#registering-your-application))
- Your **Client ID** and **Client Secret**
- A **Redirect URI** configured for your application

## Quick Overview

Eurofurence Identity uses **OpenID Connect (OIDC)**, an industry standard built on OAuth 2.0. The flow works like this:

1. Your app redirects the user to Eurofurence Identity
2. The user logs in and approves your app's requested permissions
3. We redirect back to your app with an authorization code
4. Your app exchanges the code for tokens (server-side)

That's it. Most OIDC libraries handle steps 1-4 automatically.

## Discovery Endpoint

Point your OIDC library at the discovery URL and it will auto-configure all endpoints:

```
https://identity.eurofurence.org/.well-known/openid-configuration
```

If your library needs manual configuration:

| Endpoint | URL |
|----------|-----|
| Authorization | `https://identity.eurofurence.org/oauth2/auth` |
| Token | `https://identity.eurofurence.org/oauth2/token` |
| Userinfo | `https://identity.eurofurence.org/api/v1/userinfo` |

## Minimal Example

Here's the simplest possible integration — request the user's identity and email:

**1. Redirect the user to login:**

```
https://identity.eurofurence.org/oauth2/auth?
  response_type=code&
  client_id=YOUR_CLIENT_ID&
  redirect_uri=https://yourapp.com/callback&
  scope=openid email profile&
  state=random_state_value
```

**2. Exchange the authorization code for tokens:**

```bash
curl -X POST https://identity.eurofurence.org/oauth2/token \
  -d grant_type=authorization_code \
  -d code=AUTHORIZATION_CODE \
  -d redirect_uri=https://yourapp.com/callback \
  -d client_id=YOUR_CLIENT_ID \
  -d client_secret=YOUR_CLIENT_SECRET
```

**3. Use the access token to fetch user info:**

```bash
curl https://identity.eurofurence.org/api/v1/userinfo \
  -H "Authorization: Bearer ACCESS_TOKEN"
```

## What's Next?

- **[Build an Application](/identity/guides/build-an-application)** — Detailed guide covering scopes, tokens, backchannel logout, metadata API, and privacy policy requirements
- **[API Reference](/identity/api/v1/eurofurence-identity)** — Full interactive API documentation
