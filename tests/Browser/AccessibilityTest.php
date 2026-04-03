<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Auth Pages (unauthenticated) ───────────────────────────────────────────

it('auth error page has no accessibility issues', function () {
    visit('/auth/error')->assertNoAccessibilityIssues();
});

it('forgot password page has no accessibility issues', function () {
    visit('/auth/forgot-password')->assertNoAccessibilityIssues();
});

it('logged out page has no accessibility issues', function () {
    visit('/auth/logged-out')->assertNoAccessibilityIssues();
});

// ─── Dashboard & Profile (authenticated) ────────────────────────────────────

it('dashboard has no accessibility issues', function () {
    $this->actingAs(User::factory()->create());

    visit('/dashboard')->assertNoAccessibilityIssues();
});

it('profile page has no accessibility issues', function () {
    $this->actingAs(User::factory()->create());

    visit('/settings/profile')->assertNoAccessibilityIssues();
});

// ─── Security Settings (authenticated) ──────────────────────────────────────

it('security overview has no accessibility issues', function () {
    $this->actingAs(User::factory()->create());

    visit('/settings/security')->assertNoAccessibilityIssues();
});

it('security email page has no accessibility issues', function () {
    $this->actingAs(User::factory()->create());

    visit('/settings/security/email')->assertNoAccessibilityIssues();
});

it('security password page has no accessibility issues', function () {
    $this->actingAs(User::factory()->create());

    visit('/settings/security/password')->assertNoAccessibilityIssues();
});

it('security passkeys page has no accessibility issues', function () {
    $this->actingAs(User::factory()->create());

    visit('/settings/security/passkeys')->assertNoAccessibilityIssues();
});

it('security keys page has no accessibility issues', function () {
    $this->actingAs(User::factory()->create());

    visit('/settings/security/security-keys')->assertNoAccessibilityIssues();
});

it('security totp page has no accessibility issues', function () {
    $this->actingAs(User::factory()->create());

    visit('/settings/security/totp')->assertNoAccessibilityIssues();
});

it('security yubikey page has no accessibility issues', function () {
    $this->actingAs(User::factory()->create());

    visit('/settings/security/yubikey')->assertNoAccessibilityIssues();
});

it('security backup codes page has no accessibility issues', function () {
    $this->actingAs(User::factory()->create());

    visit('/settings/security/backup-codes')->assertNoAccessibilityIssues();
});

it('security sessions page has no accessibility issues', function () {
    $this->actingAs(User::factory()->create());

    visit('/settings/security/sessions')->assertNoAccessibilityIssues();
});

// ─── Developer Apps (authenticated) ─────────────────────────────────────────

it('apps index has no accessibility issues', function () {
    $this->actingAs(User::factory()->developer()->create());

    visit('/settings/apps')->assertNoAccessibilityIssues();
});

it('app create page has no accessibility issues', function () {
    $this->actingAs(User::factory()->developer()->create());

    visit('/settings/apps/create')->assertNoAccessibilityIssues();
});

// ─── Staff Pages (admin) ────────────────────────────────────────────────────

it('staff dashboard has no accessibility issues', function () {
    $this->actingAs(User::factory()->admin()->create());

    visit('/staff/dashboard')->assertNoAccessibilityIssues();
});

it('staff groups page has no accessibility issues', function () {
    $this->actingAs(User::factory()->admin()->create());

    visit('/staff/groups')->assertNoAccessibilityIssues();
});
