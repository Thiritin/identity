<?php

namespace Tests\Feature\Profile;

use App\Notifications\UpdateEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UpdateEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_email()
    {
        Notification::fake();
        Mail::fake();

        Notification::assertNothingSent();

        $user = $this->makeAuthSession();
        $response = $this->postJson(route('settings.update-profile.update'), [
            'name' => 'validname',
            'email' => 'test2@email.de',
        ]);
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        Notification::assertSentTimes(UpdateEmailNotification::class, 1);
    }

    public function test_can_use_signed_route_to_update_email()
    {
        $user = $this->makeAuthSession();
        $oldMail = $user->email;

        $this->assertNotEquals($user->email, 'test2@email.de');
        Cache::put('user:' . $user->hashid . ':newEmail', 'test2@email.de');
        $response = $this->get(URL::signedRoute('settings.update-profile.email.update',
            [
                'id' => $user->hashid,
                'newEmail' => sha1('test2@email.de'),
            ]));

        $user->refresh();
        $response->assertInertia(fn ($page) => $page->component('Auth/VerifyEmailSuccess'));
        $response->assertSuccessful();
        $this->assertEquals('test2@email.de', $user->email);

    }

    public function test_signed_url_safe()
    {
        $this->makeAuthSession();
        $response = $this->get(route('settings.update-profile.email.update'));

        $response->assertForbidden();
    }
}
