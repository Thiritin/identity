<?php

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('If user is deleted, system should reject consent', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/consent/reject*' => Http::response([
            'redirect_to' => 'https://unverified',
        ]),
        '*/admin/oauth2/auth/requests/consent*' => Http::response([
            'subject' => 'I_do_not_exist',
            'challenge' => 'TEST123',
        ]),
    ]);
    $response = \Pest\Laravel\get(route('auth.consent', ['consent_challenge' => 'TEST123']));
    $response->assertRedirect('https://unverified');
});

test('If user exists and app has skip_consent, consent should be forwarded', function () {
    $user = User::factory()->create();
    App::withoutEvents(fn () => App::factory()->skipConsent()->create([
        'client_id' => 'test-client',
    ]));
    Http::fake([
        '*/admin/oauth2/auth/requests/consent/accept*' => Http::response([
            'redirect_to' => 'https://verified',
        ]),
        '*/admin/oauth2/auth/requests/consent*' => Http::response([
            'subject' => $user->hashid,
            'challenge' => 'TEST123',
            'requested_scope' => ['openid'],
            'requested_access_token_audience' => ['https://localhost/'],
            'client' => [
                'client_id' => 'test-client',
            ],
        ]),
    ]);
    $response = \Pest\Laravel\get(route('auth.consent', ['consent_challenge' => 'TEST123']));
    $response->assertRedirect('https://verified');
});
