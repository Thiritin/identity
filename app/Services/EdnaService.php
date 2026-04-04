<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EdnaService
{
    public function check(string $nickname, string $email): EdnaCheckResult
    {
        $response = Http::asForm()->post(config('services.edna.url'), [
            'nickname' => $nickname,
            'email' => $email,
            'type' => 'en',
            'check' => '1',
            'sent' => '1',
        ]);

        if ($response->failed()) {
            Log::error('EDNA check request failed', [
                'nickname' => $nickname,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('EDNA NDA check failed.', $response->status());
        }

        $body = $response->body();

        $statusText = $this->extractStatusText($body);

        if ($statusText === null) {
            return new EdnaCheckResult(signed: false, rawStatus: null);
        }

        $signed = str_contains($statusText, 'COMPLETED')
            && ! str_contains($statusText, 'NOT COMPLETED');

        return new EdnaCheckResult(signed: $signed, rawStatus: $statusText);
    }

    public function send(string $nickname, string $email, string $language = 'en'): bool
    {
        $response = Http::asForm()->post(config('services.edna.url'), [
            'nickname' => $nickname,
            'email' => $email,
            'type' => $language,
            'check' => '0',
            'sent' => '1',
        ]);

        if ($response->failed()) {
            Log::error('EDNA send request failed', [
                'nickname' => $nickname,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('EDNA NDA send failed.', $response->status());
        }

        return true;
    }

    private function extractStatusText(string $html): ?string
    {
        $formEnd = strpos($html, '</form>');

        if ($formEnd === false) {
            return null;
        }

        $afterForm = substr($html, $formEnd + 7);
        $text = strip_tags($afterForm);
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        return $text;
    }
}
