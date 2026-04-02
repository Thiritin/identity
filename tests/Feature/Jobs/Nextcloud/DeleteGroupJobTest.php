<?php

use App\Jobs\Nextcloud\DeleteGroupJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.nextcloud.baseUrl', 'https://cloud.example.com');
    config()->set('services.nextcloud.username', 'admin');
    config()->set('services.nextcloud.password', 'secret');
});

it('deletes nextcloud group', function () {
    Http::fake([
        'cloud.example.com/ocs/v1.php/cloud/groups/*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    (new DeleteGroupJob('abc123', 99))->handle();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'ocs/v1.php/cloud/groups/abc123')
            && $request->method() === 'DELETE';
    });
});
