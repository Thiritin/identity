<?php

use App\Providers\Socialite\SocialiteIdentityProvider;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.registration.attendee_service_url' => 'http://reg-service.test']);

    $provider = Mockery::mock(SocialiteIdentityProvider::class);
    $provider->shouldReceive('getToken')->andReturn('fake-user-token');
    Socialite::shouldReceive('driver')->with('idp-identity')->andReturn($provider);
});

it('returns false when user has no registrations', function () {
    Http::fake([
        '*/attendees' => Http::response(['ids' => []], 200),
    ]);

    $service = new RegistrationService();

    expect($service->hasActiveRegistration())->toBeFalse();
});

it('returns true when user has an active registration', function () {
    Http::fake([
        '*/attendees' => Http::response(['ids' => [42]], 200),
        '*/attendees/42/status' => Http::response(['status' => 'approved'], 200),
    ]);

    $service = new RegistrationService();

    expect($service->hasActiveRegistration())->toBeTrue();
});

it('returns false when all registrations are cancelled or deleted', function () {
    Http::fake([
        '*/attendees' => Http::response(['ids' => [42, 99]], 200),
        '*/attendees/42/status' => Http::response(['status' => 'cancelled'], 200),
        '*/attendees/99/status' => Http::response(['status' => 'deleted'], 200),
    ]);

    $service = new RegistrationService();

    expect($service->hasActiveRegistration())->toBeFalse();
});

it('returns true when at least one registration is active among inactive ones', function () {
    Http::fake([
        '*/attendees' => Http::response(['ids' => [42, 99]], 200),
        '*/attendees/42/status' => Http::response(['status' => 'cancelled'], 200),
        '*/attendees/99/status' => Http::response(['status' => 'paid'], 200),
    ]);

    $service = new RegistrationService();

    expect($service->hasActiveRegistration())->toBeTrue();
});

it('throws exception when listMyRegistrations fails', function () {
    Http::fake([
        '*/attendees' => Http::response('Unauthorized', 401),
    ]);

    $service = new RegistrationService();
    $service->hasActiveRegistration();
})->throws(RuntimeException::class);

it('throws exception when getStatusById fails', function () {
    Http::fake([
        '*/attendees' => Http::response(['ids' => [42]], 200),
        '*/attendees/42/status' => Http::response('Not Found', 404),
    ]);

    $service = new RegistrationService();
    $service->hasActiveRegistration();
})->throws(RuntimeException::class);

it('returns false when service URL is not configured', function () {
    config(['services.registration.attendee_service_url' => null]);

    $service = new RegistrationService();

    expect($service->hasActiveRegistration())->toBeFalse();
});
