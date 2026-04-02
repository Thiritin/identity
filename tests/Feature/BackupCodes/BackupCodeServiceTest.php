<?php

use App\Models\User;
use App\Services\BackupCodeService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->service = new BackupCodeService();
});

it('generates 8 codes', function () {
    $codes = $this->service->generate();

    expect($codes)->toHaveCount(8);
});

it('generates codes of correct length', function () {
    $codes = $this->service->generate();

    foreach ($codes as $code) {
        expect($code)->toHaveLength(8)
            ->and($code)->toMatch('/^[A-Z0-9]+$/');
    }
});

it('generates unique codes', function () {
    $codes = $this->service->generate();

    expect(array_unique($codes))->toHaveCount(8);
});

it('stores backup codes for a user', function () {
    $codes = $this->service->generate();
    $this->service->storeForUser($this->user, $codes);

    expect($this->service->hasBackupCodes($this->user))->toBeTrue()
        ->and($this->service->remainingCount($this->user))->toBe(8);
});

it('replaces existing backup codes on store', function () {
    $firstCodes = $this->service->generate();
    $this->service->storeForUser($this->user, $firstCodes);

    $secondCodes = $this->service->generate();
    $this->service->storeForUser($this->user, $secondCodes);

    expect($this->service->remainingCount($this->user))->toBe(8);

    // Old codes should not work
    expect($this->service->verify($this->user, $firstCodes[0]))->toBeFalse();

    // New codes should work
    expect($this->service->verify($this->user, $secondCodes[0]))->toBeTrue();
});

it('verifies a valid backup code', function () {
    $codes = $this->service->generate();
    $this->service->storeForUser($this->user, $codes);

    expect($this->service->verify($this->user, $codes[0]))->toBeTrue();
});

it('rejects an invalid backup code', function () {
    $codes = $this->service->generate();
    $this->service->storeForUser($this->user, $codes);

    expect($this->service->verify($this->user, 'INVALID1'))->toBeFalse();
});

it('consumes a code after use', function () {
    $codes = $this->service->generate();
    $this->service->storeForUser($this->user, $codes);

    $this->service->verify($this->user, $codes[0]);

    expect($this->service->remainingCount($this->user))->toBe(7);
});

it('rejects a previously used code', function () {
    $codes = $this->service->generate();
    $this->service->storeForUser($this->user, $codes);

    expect($this->service->verify($this->user, $codes[0]))->toBeTrue();
    expect($this->service->verify($this->user, $codes[0]))->toBeFalse();
});

it('handles dash-formatted input', function () {
    $codes = $this->service->generate();
    $this->service->storeForUser($this->user, $codes);

    $formatted = substr($codes[0], 0, 4) . '-' . substr($codes[0], 4);

    expect($this->service->verify($this->user, $formatted))->toBeTrue();
});

it('handles lowercase input', function () {
    $codes = $this->service->generate();
    $this->service->storeForUser($this->user, $codes);

    expect($this->service->verify($this->user, strtolower($codes[0])))->toBeTrue();
});

it('returns zero remaining when no codes exist', function () {
    expect($this->service->remainingCount($this->user))->toBe(0);
});

it('returns false for hasBackupCodes when none exist', function () {
    expect($this->service->hasBackupCodes($this->user))->toBeFalse();
});

it('formats code for display correctly', function () {
    expect(BackupCodeService::formatForDisplay('A1B2C3D4'))->toBe('A1B2-C3D4');
});
