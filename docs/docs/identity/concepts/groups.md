---
sidebar_position: 5
title: Groups
---

# Groups

Groups are how Eurofurence's identity service models **organisational structure** and **access control** beyond the individual user. Every staff member belongs to one or more groups, and those memberships drive everything from which Slack channels they end up in, to which internal tools they can use, to which API scopes their app tokens can assert.

This page explains how the group hierarchy is laid out, what the different group types mean, how per-group levels work, and — critically — how being added to a group can implicitly promote a user to staff.

## The hierarchy

Groups form a tree. Every group (except the root) has a `parent_id` pointing at its parent group. The depth of the tree is not fixed, but by convention Eurofurence uses four levels:

```
root  (Board of Directors)
└── division          e.g. "Operations"
    └── department    e.g. "Information Technology"
        └── team      e.g. "Identity Platform"
```

Each level has a type from the `GroupTypeEnum`:

| `type`        | Purpose                                                                                 |
|---------------|-----------------------------------------------------------------------------------------|
| `root`        | The top of the tree. There is exactly one root group (Board of Directors).              |
| `division`    | A top-level organisational division. Headed by a division director.                     |
| `department`  | A functional department inside a division. Headed by a director. **Implies staff status.** |
| `team`        | A working group inside a department. Led by a team lead. **Implies staff status.**      |
| `automated`   | A system-managed group (not manually editable). Used for function groups — see below.   |
| `none`        | A plain group with no organisational semantics. Rarely used.                            |

The `slug` field encodes the parent path for human-readable URLs — e.g. a team's slug might be `operations/information-technology/identity-platform`. It is a convention, not a source of truth; `parent_id` is.

## Function groups

Alongside the hierarchy groups, a handful of **function groups** exist with fixed `system_name` values. These are typed as `automated` and are managed by the platform — you should not create, rename, or delete them through the API.

| `system_name` | Meaning                                                                    |
|---------------|----------------------------------------------------------------------------|
| `staff`       | All staff members. Membership is derived from department membership (see below). |
| `directors`   | All users currently holding a `director` or `division_director` level anywhere. |
| `devops`      | Platform/devops access to sensitive systems.                               |

Function groups are still exposed through the normal `/groups` endpoints — they're just groups — but consumers should treat them as read-only derived state rather than things to mutate directly.

## Identifying groups

In the v2 API, every group is addressed by its **hashid** — an opaque, URL-safe string like `Y6K08PEKXG9Q7ZWJ`. This is the `id` field on the `Group` schema and what you pass in paths like `/groups/{group}/members`. Do not rely on the numeric primary key; it is not exposed through v2.

The same is true for users (`user_id` in `GroupMember` is a hashid) and most other resources in v2.

## Per-group member levels

A user is related to a group through a pivot row that carries a `level`, an optional `title`, and an optional `credit_as` override. **Levels are per-group, not global** — a user can be a `director` in their home department and a plain `member` in an ad-hoc working team.

The available levels are:

| `level`              | Typical use                                            | Can manage members? |
|----------------------|--------------------------------------------------------|---------------------|
| `member`             | Default. A regular participant in the group.           | No                  |
| `team_lead`          | Leads a team. Can manage members of their team.        | Yes                 |
| `director`           | Heads a department. Can manage its teams and members.  | Yes                 |
| `division_director`  | Heads a division. Can manage its departments downward. | Yes                 |

Levels form a delegation hierarchy for member management — a division director can assign directors, a director can assign team leads, a team lead can assign members. See `App\Enums\GroupUserLevel::assignableLevels()` for the exact mapping.

`title` is free-form text shown next to the member in the directory (e.g. "App Developer", "Registration Ops"). It does not grant any permissions.

## Staff and the auto-promotion rule

Being a member of the `staff` function group is what "being staff" means in the identity system. It gates access to the staff directory, the internal admin UI, and any scope or endpoint that requires staff status.

There is an important, implicit relationship between staff and the hierarchy:

> **Adding a user to a group of type `department` or `team` implies they are staff.**

Concretely:

- When a user is added to a `department`, a background listener (`SyncAutomatedSystemGroups`) adds them to the `staff` group automatically.
- When the user is removed from their **last** department, the same listener removes them from `staff` again.
- `team` additions do not trigger the listener, but the API still treats them as staff-implying — see below.

This means a bulk import that naively posts users into departments would **silently promote** everyone. To prevent accidents, the v2 `POST /groups/{group}/members` endpoint requires an explicit opt-in:

### The `allow_making_staff` flag

When adding a user to a `department` or `team`, the request body may include `allow_making_staff: true`. The rules are:

| User is already staff? | `allow_making_staff` | Result                                                                                 |
|------------------------|----------------------|----------------------------------------------------------------------------------------|
| Yes                    | (ignored)            | ✅ Added.                                                                              |
| No                     | `false` (default)    | ❌ **422** with error key `allow_making_staff`. Nothing is written.                    |
| No                     | `true`               | ✅ Added, and also added to the `staff` group (inline for teams, via listener for departments). |

This default-deny behaviour is deliberate: imports should not be able to grant staff privileges unless the operator explicitly says so. For a typical roster import where the intent is "these people are already staff, just put them in the right department," leave the flag off and **make sure the user is in staff first**. Only set `allow_making_staff: true` when you really do want the endpoint to grant staff as a side effect.

Every auto-promotion is logged to the activity log as `group-member-auto-promoted-to-staff` with the acting app, the target user, and the group.

## Identifying users when adding members

The `POST /groups/{group}/members` endpoint accepts **exactly one** of three identifiers in the request body:

| Field      | What it is                                     | Example                    |
|------------|------------------------------------------------|----------------------------|
| `email`    | The user's primary email address.              | `john@example.com`         |
| `username` | The IDP login name (stored as `users.name`).   | `jdoe`                     |
| `user_id`  | The user's hashid.                             | `1VJEQAYWW54TZ5VD`         |

Use whichever is most natural for your source data. For roster imports keyed by login names, `username` avoids an extra lookup round-trip. For internal integrations that already track hashids, `user_id` is the stable long-term reference.

## Visibility rules

Listing groups via `GET /groups` follows two rules:

1. **Staff** (members of the `staff` function group) can see every group in the hierarchy.
2. **Non-staff** users only see groups they're a member of.

This is why logging in as a non-staff app often returns an empty list — the caller simply has no hierarchy groups to see.

Group *members* visibility follows a finer-grained policy (based on the `staff_profile_visibility` setting of each individual user). Email addresses on `GroupMember` responses are only populated when the caller holds the `view_full_staff_details` scope.

## Related

- [Scopes](./scopes.md) — the `groups.read`, `groups.write`, and `groups.delete` scopes.
- `GET /groups/tree` — a pre-built nested tree view, capped by the `depth` parameter.
- `POST /groups/{group}/members` — add a member, with the `allow_making_staff` guard.
