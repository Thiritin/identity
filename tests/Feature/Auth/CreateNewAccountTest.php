<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\post;
use function PHPUnit\Framework\assertEquals;

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
    $response->assertRedirect(route('login.apps.redirect', ['app' => 'portal']));
    Event::assertDispatched(Registered::class);
});

test('Check logs dispatch - Register', function () {
    Mail::fake();
    $user = User::factory()->create();
    event(new Registered($user));
    assertEquals("registered", $user->actions->first()->description);
});
