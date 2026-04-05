---
title: OAuth2 & OpenID Connect Primer
---

# OAuth2 & OpenID Connect Primer

Read this first if terms like "access token", "authorization code", or "client credentials" don't mean anything to you yet. This page is a plain-language tour of the two protocols Eurofurence Identity speaks, and a pointer to the right integration guide for your use case.

## What Problem OAuth2 Solves

Imagine you're building a third-party dealer tool that wants to show each visitor their Eurofurence registration status before they check out. The tool needs to ask the Identity service a question on the user's behalf: *"Is this person registered, and have they paid?"*

Before OAuth2, the only way to answer that question was ugly: the user would have to type their Eurofurence username and password directly into your tool, which would then log in *as them* and read whatever it needed. That's a disaster for everyone. The user is trusting a random app with their main account credentials, and your app suddenly has to store and protect passwords it never wanted in the first place.

OAuth2 fixes this with one core idea: **don't share passwords, redirect instead.** Your app sends the user over to Eurofurence Identity, which is the only place that ever sees their password. The user logs in there, sees a consent screen explaining exactly what your app wants to read, and clicks approve. Identity then sends the user back to your app with a short, limited **access token** — a credential that lets your app make exactly the API calls it was approved for, and nothing more.

The result: your app never touches the user's password, the user can revoke access at any time, and Eurofurence Identity stays in control of who can do what. That's all OAuth2 is — a polite way for one app to get *limited, revocable* access to a user's data on another service.

## What OpenID Connect Adds

OAuth2 answers one question: *"Is this app allowed to access X?"* It deliberately says nothing about *who* the user actually is. For a lot of early use cases that was fine, but as soon as people started using OAuth2 to build "Sign in with…" buttons, an obvious gap appeared: you can prove an app has permission to read a mailbox, but you still don't have a trustworthy answer to *"who just logged in?"*

OpenID Connect (OIDC) is the thin identity layer built on top of OAuth2 to fill that gap. When your app uses OIDC, Identity returns an additional **ID token** alongside the access token. The ID token is a signed statement from Eurofurence Identity that says, in effect: *"I confirm user X just authenticated with your application, and here are some verified facts about them — their stable user id, their display name, their email."* Your app verifies the signature, reads the claims, and now it knows who the user is without having to ask any further API.

The short version: OAuth2 is about *access*, OIDC is about *identity*. Eurofurence Identity does both in the same request, so in practice you always get both an access token (for calling APIs) and an ID token (for knowing the user). For a full breakdown of what's inside each token and how to use them correctly, see [Understanding Tokens](/identity/concepts/tokens).

## The Flows We Support

OAuth2 defines several different ways ("flows") to actually obtain a token, each tuned for a different kind of application. Eurofurence Identity supports three of them. Pick the one that matches your scenario.

### Authorization Code + PKCE

This is the flow for any app where a real human logs in: web apps, single-page apps, mobile apps, desktop apps. The user is redirected to Eurofurence Identity, logs in there, approves the requested scopes, and is sent back to your app with a short-lived authorization code that your app exchanges for tokens. PKCE is a small extra step that protects the exchange against interception and is required for every client. If this is your situation, follow the [Build an Application](/identity/integration/build-an-application) guide.

### Client Credentials

This flow is for backend services that need to talk to other backend services without a user being involved — a scheduled job, a webhook handler, an internal tool syncing data between systems. Your service authenticates with its own `client_id` and `client_secret` and receives an access token that represents the service itself, not a user. See [App-to-App APIs](/identity/integration/app-to-app) for the end-to-end walkthrough.

### Refresh Token

Refresh tokens aren't a standalone flow so much as a follow-up to Authorization Code. If your app requested the `offline_access` scope during login, it also receives a long-lived refresh token it can trade in later for a fresh access token — no user interaction required. Use this to keep users signed in across sessions, or to keep a background worker calling APIs on their behalf after they've gone offline. The refresh endpoint is described in [Understanding Tokens](/identity/concepts/tokens#refresh-token).

### Which One Do I Want?

- A human logs into your app (web, mobile, SPA, desktop) → **Authorization Code + PKCE**
- Two backend services need to talk with no user involved → **Client Credentials**
- You already have an Authorization Code integration and need to stay signed in after the user leaves → add `offline_access` and use a **Refresh Token**
- Anything else — in particular, device code flow or the old implicit flow — is **not supported** by Eurofurence Identity

## Glossary

- **Access token** — The credential your app sends to an API to make calls on behalf of the user (or, for client credentials, on behalf of itself). At Eurofurence Identity these are opaque strings; don't try to decode them. See [Understanding Tokens](/identity/concepts/tokens#access-token).
- **ID token** — A signed JWT that tells *your app* who the user is. Verified locally, used at login, never sent to APIs. See [Understanding Tokens](/identity/concepts/tokens#id-token).
- **Refresh token** — A long-lived secret your app can exchange for a new access token without involving the user. Only issued if you request `offline_access`. See [Understanding Tokens](/identity/concepts/tokens#refresh-token).
- **Scope** — A label on the consent screen that controls *what* your app is allowed to ask for (`profile`, `email`, `registration.my.read`, etc.). Request only what you need. See [Scopes](/identity/concepts/scopes).
- **Audience** — An identifier on a token that says *which service* is allowed to accept it. Prevents a token meant for one backend from being replayed against another. See [Audiences](/identity/concepts/audiences).
- **Client** — Your application, as registered with Eurofurence Identity. Each client has a `client_id`, a set of allowed scopes and audiences, and (for confidential clients) a `client_secret`.
