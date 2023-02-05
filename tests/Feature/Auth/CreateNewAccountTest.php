<?php

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

test('Create a new Account', function () {
    Http::fake(); // Fake Validation on Have I been Pwned
    Event::fake();
    Notification::fake();
    $response = post(route('auth.register.store'), [
        "username" => "Test",
        "email" => "test@eurofurence.org",
        "password" => "OSANR&dbb^0GDp^19UiSxRlM3Wm",
        "password_confirmation" => "OSANR&dbb^0GDp^19UiSxRlM3Wm",
    ]);
    $response->assertRedirect(route('dashboard'));
    Event::assertDispatched(Registered::class);
});
