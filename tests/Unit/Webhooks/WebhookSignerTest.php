<?php

namespace Tests\Unit\Webhooks;

use App\Services\Webhooks\WebhookSigner;
use PHPUnit\Framework\TestCase;

class WebhookSignerTest extends TestCase
{
    public function test_sign_produces_expected_known_vector(): void
    {
        $signer = new WebhookSigner();

        $secret = 'supersecret';
        $timestamp = 1_700_000_000;
        $body = '{"event":"user.updated"}';

        $expected = 'v1,' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        $this->assertSame($expected, $signer->sign($secret, $timestamp, $body));
    }

    public function test_verify_accepts_valid_signature(): void
    {
        $signer = new WebhookSigner();
        $secret = 'supersecret';
        $timestamp = time();
        $body = '{"ok":true}';

        $signature = $signer->sign($secret, $timestamp, $body);

        $this->assertTrue($signer->verify($secret, $timestamp, $body, $signature, toleranceSeconds: 300));
    }

    public function test_verify_rejects_tampered_body(): void
    {
        $signer = new WebhookSigner();
        $secret = 'supersecret';
        $timestamp = time();
        $signature = $signer->sign($secret, $timestamp, '{"ok":true}');

        $this->assertFalse($signer->verify($secret, $timestamp, '{"ok":false}', $signature, toleranceSeconds: 300));
    }

    public function test_verify_rejects_stale_timestamp(): void
    {
        $signer = new WebhookSigner();
        $secret = 'supersecret';
        $staleTs = time() - 3600;
        $body = '{"ok":true}';
        $signature = $signer->sign($secret, $staleTs, $body);

        $this->assertFalse($signer->verify($secret, $staleTs, $body, $signature, toleranceSeconds: 300));
    }

    public function test_verify_rejects_wrong_prefix(): void
    {
        $signer = new WebhookSigner();
        $this->assertFalse(
            $signer->verify('s', time(), 'b', 'v2,deadbeef', toleranceSeconds: 300),
        );
    }
}
