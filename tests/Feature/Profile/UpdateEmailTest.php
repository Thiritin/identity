<?php

namespace Tests\Feature\Profile;

use App\Notifications\UpdateEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UpdateEmailTest extends TestCase
{
    use RefreshDatabase;

    public function testCanUpdateEmail()
    {
        Notification::fake();
        Mail::fake();

        Notification::assertNothingSent();

        $user = $this->makeAuthSession();
        $response = $this->post(route('settings.update-profile.update'), [
            "name" => $user->name,
            "email" => "test2@email.de"
        ]);

        Notification::assertSentTo($user, UpdateEmailNotification::class);
    }

    public function testCanUseSignedRouteToUpdateEmail()
    {
        $user = $this->makeAuthSession();
        $oldMail = $user->email;

        $this->assertNotEquals($user->email, "test2@email.de");
        $response = $this->get(URL::signedRoute('settings.update-profile.email.update', ["newEmail" => "test2@email.de"]));

        $response->assertRedirect();
        $this->assertEquals($user->email, "test2@email.de");

    }

    public function testSignedUrlSafe()
    {
        $this->makeAuthSession();
        $response = $this->get(route('settings.update-profile.email.update'));

        $response->assertForbidden();
    }
}
