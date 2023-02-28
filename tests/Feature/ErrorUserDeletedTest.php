<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('If user is deleted, system should reject consent', function () {
    Http::fake([
        config('services.hydra.admin')."/admin/oauth2/auth/requests/consent?challenge=*" => Http::response([
            "subject" => "I_do_not_exist",
            "challenge" => "TEST123"
        ]),
        config('services.hydra.admin')."/admin/oauth2/auth/requests/consent/reject?challenge=*" => Http::response([
            "redirect_to" => "https://unverified"
        ]),
    ]);
    $response = \Pest\Laravel\get(route('auth.consent',["consent_challenge" => "TEST123"]));
    $response->assertRedirect("https://unverified");
});


test('If user exists, consent should be forwarded', function () {
    $user = \App\Models\User::factory()->create();
    Http::fake([
        config('services.hydra.admin')."/admin/oauth2/auth/requests/consent?challenge=*" => Http::response([
            "subject" => $user->hashid(),
            "challenge" => "TEST123",
            "requested_scope" => ["openid"],
            "requested_access_token_audience" => ["https://localhost/"],
        ]),
        config('services.hydra.admin')."/admin/oauth2/auth/requests/consent/accept?challenge=*" => Http::response([
            "redirect_to" => "https://verified"
        ]),
    ]);
    $response = \Pest\Laravel\get(route('auth.consent',["consent_challenge" => "TEST123"]));
    $response->assertRedirect("https://verified");
});
