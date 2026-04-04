---
sidebar_position: 2
title: Scopes
---

# Scopes

Scopes control what data and APIs your application can access on behalf of a user. When a user authorizes your app, they see which scopes you're requesting and can decide whether to grant access.

## Principles

### Request only what you need

Every scope you request appears on the consent screen. Asking for more than necessary reduces user trust and consent rates. Start with the minimum scopes your app needs and add more later as features require them.

### Scopes are not permissions

Scopes gate API access at the OAuth level — they control what your app *can ask for*. Fine-grained permissions (e.g., who can edit a specific group) are enforced server-side regardless of scopes. Having `groups.write` doesn't mean your app can write to *any* group, only that it's allowed to call the write endpoints.

### Naming convention

Scopes follow a `resource.qualifier.action` pattern:

- **resource** — the data domain (`groups`, `staff`, `registration`)
- **qualifier** — optional scope narrowing (`my` = own data, `all` = all users' data)
- **action** — the operation (`read`, `write`, `delete`)

## Available Scopes

### Identity

Standard OpenID Connect scopes for basic user information.

| Scope | Description |
|-------|-------------|
| `openid` | **Required.** Enables OpenID Connect and returns a user identifier (`sub` claim) |
| `profile` | User's display name and avatar |
| `email` | User's email address and whether it's verified |
| `offline_access` | Issues a refresh token so your app can renew access without re-prompting the user |

:::tip
`openid` is always required. Most apps will also want `profile` and `email` for a basic user experience.
:::

### Groups

Access to Eurofurence's group system — departments, teams, and organizational units.

| Scope | Description |
|-------|-------------|
| `groups` | Includes the user's group memberships as a claim in the ID token. No API access. |
| `groups.read` | Read group details and memberships via the API |
| `groups.write` | Create and update groups via the API |
| `groups.delete` | Delete groups via the API |

**When to use what:**
- Need to know *which groups a user belongs to* at login? Use `groups` — the memberships are included directly in the ID token, no API call needed.
- Need to *browse, search, or display group information* in your app? Use `groups.read`.
- Building a group management tool? Add `groups.write` and/or `groups.delete`.

### Staff

Access to staff profile information for convention team members.

| Scope | Description |
|-------|-------------|
| `staff` | Includes the user's staff details (first name, last name, credit name) as claims in the ID token. No API access. |
| `staff.my.read` | Read the authenticated user's own staff profile via the API (name, phone, etc.) |
| `staff.all.read` | Read all staff members' profiles via the API. Respects each user's per-field visibility settings. |

**When to use what:**
- Need the user's real name at login without an API call? Use `staff` — the claims are included directly in the ID token.
- Building an app where staff can view or edit their own full profile? Use `staff.my.read`.
- Building a staff directory or org chart? Use `staff.all.read`.

### App Data

Per-app, per-user key-value storage managed by Eurofurence Identity. Useful for storing app-specific preferences or state that should persist across sessions.

| Scope | Description |
|-------|-------------|
| `appdata.read` | Read your app's stored data for the authenticated user |
| `appdata.write` | Write your app's data for the authenticated user |

Data is scoped to your OAuth client ID — you can only access data your app has written. Values are strings up to 64KB.

### Registration

Access to the Eurofurence registration system for convention attendance, room booking, and related workflows.

| Scope | Description |
|-------|-------------|
| `registration.my.read` | Read the authenticated user's own registration (status, packages, options, flags) |
| `registration.my.write` | Create or update the authenticated user's own registration |
| `registration.all.read` | Search and read any attendee's registration (privileged) |
| `registration.all.write` | Update any attendee's registration, change status, override due dates (privileged) |

**When to use what:**
- Building an app where users check their own registration status? Use `registration.my.read`.
- Building a registration form or self-service tool? Add `registration.my.write`.
- Building admin tooling or reports across all attendees? Use `registration.all.read` and/or `registration.all.write`.

:::caution
The `registration.all.*` scopes grant access to all attendee data and are restricted to first-party and approved applications.
:::

## Choosing Scopes for Your App

Here are some common app types and the scopes they typically need:

| App Type | Recommended Scopes |
|----------|--------------------|
| Simple login ("Sign in with Eurofurence") | `openid`, `profile`, `email` |
| Community app with group features | `openid`, `profile`, `email`, `groups`, `groups.read` |
| Staff tool (basic) | `openid`, `profile`, `staff` |
| Staff tool (full profile) | `openid`, `profile`, `staff.my.read` |
| Staff directory | `openid`, `profile`, `staff.all.read` |
| Registration self-service | `openid`, `profile`, `email`, `registration.my.read`, `registration.my.write` |
| App with persistent user preferences | `openid`, `profile`, `appdata.read`, `appdata.write` |
| Long-lived background service | Add `offline_access` to any of the above |

## Restricted Scopes

Some scopes are restricted and cannot be requested by third-party applications. These are reserved for first-party Eurofurence services:

- `registration.all.read`
- `registration.all.write`

If your app needs a restricted scope, contact [Thiritin on Telegram](https://t.me/thiritin) to discuss your use case.
