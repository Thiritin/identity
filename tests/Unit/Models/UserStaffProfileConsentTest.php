<?php

use App\Models\User;
use App\Support\StaffProfile\ConsentNotice;

it('returns false from hasStaffProfileConsent when consent_at is null', function () {
    $user = new User();
    $user->staff_profile_consent_at = null;
    expect($user->hasStaffProfileConsent())->toBeFalse();
});

it('returns true from hasStaffProfileConsent when consent_at is set', function () {
    $user = new User();
    $user->staff_profile_consent_at = now();
    expect($user->hasStaffProfileConsent())->toBeTrue();
});

it('returns true from hasCurrentStaffProfileConsent when version matches CURRENT_VERSION', function () {
    $user = new User();
    $user->staff_profile_consent_version = ConsentNotice::CURRENT_VERSION;
    expect($user->hasCurrentStaffProfileConsent())->toBeTrue();
});

it('returns false from hasCurrentStaffProfileConsent when version is older', function () {
    $user = new User();
    $user->staff_profile_consent_version = ConsentNotice::CURRENT_VERSION - 1;
    expect($user->hasCurrentStaffProfileConsent())->toBeFalse();
});

it('returns false from hasCurrentStaffProfileConsent when version is null', function () {
    $user = new User();
    $user->staff_profile_consent_version = null;
    expect($user->hasCurrentStaffProfileConsent())->toBeFalse();
});
