<?php

use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('returns false when no active registration found', function () {
    Http::fake([
        '*/attendees/find' => Http::response(['attendees' => []], 200),
    ]);

    $user = User::factory()->create();
    $service = new RegistrationService();
    $result = $service->hasActiveRegistration($user);

    expect($result)->toBeFalse();
});

it('returns true when active registration found', function () {
    config(['services.registration.attendee_service_url' => 'http://reg-service.test']);

    Http::fake([
        '*/attendees/find' => Http::response([
            'attendees' => [
                ['id' => 42, 'status' => 'approved'],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $service = new RegistrationService();
    $result = $service->hasActiveRegistration($user);

    expect($result)->toBeTrue();
});

it('throws exception on service error', function () {
    config(['services.registration.attendee_service_url' => 'http://reg-service.test']);

    Http::fake([
        '*/attendees/find' => Http::response('Unauthorized', 401),
    ]);

    $user = User::factory()->create();
    $service = new RegistrationService();
    $service->hasActiveRegistration($user);
})->throws(RuntimeException::class);

it('returns false when service URL is not configured', function () {
    config(['services.registration.attendee_service_url' => null]);

    $user = User::factory()->create();
    $service = new RegistrationService();
    $result = $service->hasActiveRegistration($user);

    expect($result)->toBeFalse();
});
