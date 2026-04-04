---
slug: /
title: Introduction
---

# Eurofurence Identity

Eurofurence Identity is the central authentication and authorization service for the Eurofurence ecosystem. It allows users to sign in once and access all connected Eurofurence applications — from event registration to community tools — with a single account.

Under the hood, it implements **OpenID Connect (OIDC)** on top of **OAuth 2.0**, providing secure, standards-based authentication that any application can integrate with.

## What It Does

- **Single Sign-On (SSO)** — One account for all Eurofurence services. Log in once, access everything.
- **Third-Party Integration** — External developers can build apps that authenticate against Eurofurence Identity.
- **Group Management** — Organize users into divisions, departments, and teams with hierarchical group structures.
- **Scoped Permissions** — Applications only get access to the data they need, nothing more.
- **Backchannel Logout** — When a user logs out, all connected applications are notified automatically.

## Documentation

<div className="card-container" style={{display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '1rem', marginTop: '1rem'}}>

<div className="card" style={{border: '1px solid var(--ifm-color-emphasis-300)', borderRadius: '8px', padding: '1.5rem'}}>

### Getting Started

New to Eurofurence Identity? Learn the basics of how authentication works and how to register your application.

**[Read the guide &rarr;](/identity/guides/getting-started)**

</div>

<div className="card" style={{border: '1px solid var(--ifm-color-emphasis-300)', borderRadius: '8px', padding: '1.5rem'}}>

### API Reference

Interactive API documentation with request/response examples for all available endpoints.

**[Explore the API &rarr;](/identity/api/v1/eurofurence-identity)**

</div>

</div>
