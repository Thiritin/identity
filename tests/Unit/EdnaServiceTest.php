<?php

use App\Services\EdnaCheckResult;
use App\Services\EdnaService;
use Illuminate\Support\Facades\Http;

it('parses a completed NDA response', function () {
    Http::fake([
        'edna.test/*' => Http::response(
            '<html><form id="searchform"></form>Eurofurence NDA EN sent (01.04.26 20:09 UTC) – COMPLETED<br></html>'
        ),
    ]);

    $service = new EdnaService();
    $result = $service->check('TestUser', 'test@example.com');

    expect($result)->toBeInstanceOf(EdnaCheckResult::class)
        ->and($result->signed)->toBeTrue()
        ->and($result->rawStatus)->toContain('COMPLETED');

    Http::assertSent(function ($request) {
        return $request['check'] === '1'
            && $request['nickname'] === 'TestUser'
            && $request['email'] === 'test@example.com';
    });
});

it('parses a not completed NDA response', function () {
    Http::fake([
        'edna.test/*' => Http::response(
            '<html><form id="searchform"></form>Eurofurence NDA DE sent (01.04.26 20:09 UTC) – <font color=\'red\'>NOT COMPLETED</font><br></html>'
        ),
    ]);

    $service = new EdnaService();
    $result = $service->check('TestUser', 'test@example.com');

    expect($result->signed)->toBeFalse()
        ->and($result->rawStatus)->toContain('NOT COMPLETED');
});

it('handles response with no status line', function () {
    Http::fake([
        'edna.test/*' => Http::response(
            '<html><form id="searchform"></form></html>'
        ),
    ]);

    $service = new EdnaService();
    $result = $service->check('TestUser', 'test@example.com');

    expect($result->signed)->toBeFalse()
        ->and($result->rawStatus)->toBeNull();
});

it('sends an NDA request', function () {
    Http::fake([
        'edna.test/*' => Http::response('<html>NDA sent</html>'),
    ]);

    $service = new EdnaService();
    $result = $service->send('TestUser', 'test@example.com', 'de');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['check'] === '0'
            && $request['type'] === 'de'
            && $request['nickname'] === 'TestUser'
            && $request['email'] === 'test@example.com';
    });
});

it('uses english as default language when sending', function () {
    Http::fake([
        'edna.test/*' => Http::response('<html>NDA sent</html>'),
    ]);

    $service = new EdnaService();
    $service->send('TestUser', 'test@example.com');

    Http::assertSent(fn ($request) => $request['type'] === 'en');
});

it('throws on HTTP failure when checking', function () {
    Http::fake([
        'edna.test/*' => Http::response('', 500),
    ]);

    $service = new EdnaService();
    $service->check('TestUser', 'test@example.com');
})->throws(RuntimeException::class);

it('throws on HTTP failure when sending', function () {
    Http::fake([
        'edna.test/*' => Http::response('', 500),
    ]);

    $service = new EdnaService();
    $service->send('TestUser', 'test@example.com');
})->throws(RuntimeException::class);
