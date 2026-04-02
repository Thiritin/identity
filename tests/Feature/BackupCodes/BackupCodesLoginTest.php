<?php

use App\Models\TwoFactor;
use App\Models\User;
use App\Services\BackupCodeService;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('shows hasBackupCodes on 2FA challenge page', function () {
    TwoFactor::factory()->totp()->create(['user_id' => $this->user->id]);
    $service = new BackupCodeService();
    $codes = $service->generate();
    $service->storeForUser($this->user, $codes);

    $url = URL::signedRoute('auth.two-factor', [
        'login_challenge' => 'test-challenge',
        'user' => $this->user->hashid,
    ]);

    $this->get($url)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/TwoFactor')
            ->where('hasBackupCodes', true)
        );
});

it('does not show hasBackupCodes when none exist', function () {
    TwoFactor::factory()->totp()->create(['user_id' => $this->user->id]);

    $url = URL::signedRoute('auth.two-factor', [
        'login_challenge' => 'test-challenge',
        'user' => $this->user->hashid,
    ]);

    $this->get($url)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/TwoFactor')
            ->where('hasBackupCodes', false)
        );
});

it('accepts a valid backup code during login', function () {
    TwoFactor::factory()->totp()->create(['user_id' => $this->user->id]);
    $service = new BackupCodeService();
    $codes = $service->generate();
    $service->storeForUser($this->user, $codes);

    $submitUrl = URL::signedRoute('auth.two-factor.submit', [
        'login_challenge' => 'test-challenge',
        'user' => $this->user->hashid,
    ]);

    // This will fail at the Hydra acceptLogin call, but we can verify the
    // backup code was consumed (the validation passes before Hydra)
    $this->post($submitUrl, [
        'login_challenge' => 'test-challenge',
        'user' => $this->user->hashid,
        'code' => $codes[0],
        'method' => 'backup_code',
    ]);

    // Code should be consumed
    expect($service->remainingCount($this->user))->toBe(7);
});

it('rejects an invalid backup code during login', function () {
    TwoFactor::factory()->totp()->create(['user_id' => $this->user->id]);
    $service = new BackupCodeService();
    $codes = $service->generate();
    $service->storeForUser($this->user, $codes);

    $submitUrl = URL::signedRoute('auth.two-factor.submit', [
        'login_challenge' => 'test-challenge',
        'user' => $this->user->hashid,
    ]);

    $this->post($submitUrl, [
        'login_challenge' => 'test-challenge',
        'user' => $this->user->hashid,
        'code' => 'INVALID1',
        'method' => 'backup_code',
    ])->assertSessionHasErrors('code');
});

it('rejects a previously used backup code during login', function () {
    TwoFactor::factory()->totp()->create(['user_id' => $this->user->id]);
    $service = new BackupCodeService();
    $codes = $service->generate();
    $service->storeForUser($this->user, $codes);

    // Use the code via service first
    $service->verify($this->user, $codes[0]);

    $submitUrl = URL::signedRoute('auth.two-factor.submit', [
        'login_challenge' => 'test-challenge',
        'user' => $this->user->hashid,
    ]);

    $this->post($submitUrl, [
        'login_challenge' => 'test-challenge',
        'user' => $this->user->hashid,
        'code' => $codes[0],
        'method' => 'backup_code',
    ])->assertSessionHasErrors('code');
});

it('excludes backup_codes type from twoFactors list on challenge page', function () {
    TwoFactor::factory()->totp()->create(['user_id' => $this->user->id]);
    $service = new BackupCodeService();
    $codes = $service->generate();
    $service->storeForUser($this->user, $codes);

    $url = URL::signedRoute('auth.two-factor', [
        'login_challenge' => 'test-challenge',
        'user' => $this->user->hashid,
    ]);

    $this->get($url)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/TwoFactor')
            ->has('twoFactors', 1)
            ->where('twoFactors.0.type', 'totp')
        );
});
