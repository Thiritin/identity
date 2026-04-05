<?php

namespace App\Services\Webhooks;

class WebhookSigner
{
    public const PREFIX = 'v1,';

    public function sign(string $secret, int $timestamp, string $body): string
    {
        return self::PREFIX . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    public function verify(
        string $secret,
        int $timestamp,
        string $body,
        string $signature,
        int $toleranceSeconds,
    ): bool {
        if (! str_starts_with($signature, self::PREFIX)) {
            return false;
        }

        if (abs(time() - $timestamp) > $toleranceSeconds) {
            return false;
        }

        $expected = $this->sign($secret, $timestamp, $body);

        return hash_equals($expected, $signature);
    }
}
