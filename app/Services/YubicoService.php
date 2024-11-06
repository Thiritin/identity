<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class YubicoService
{
    public string $identifier;

    public string $nonce;

    public function verify(string $otp): bool
    {
        $this->nonce = bin2hex(random_bytes(16));

        $requestParams = [
            'id' => config('services.yubikey.client_id'),
            'otp' => $otp,
            'nonce' => $this->nonce,
        ];
        $signature = $this->generateSignature($requestParams);
        $requestParams['h'] = $signature;
        // Validate Yubikey
        $response = \Illuminate\Support\Facades\Http::get('https://api.yubico.com/wsapi/2.0/verify', $requestParams);
        $body = $response->body();
        $responseData = $this->convertResponseToArray($body);

        $errorMessage = match ($responseData['status']) {
            'OK' => ($responseData['nonce'] === $this->nonce) ? null : 'Nonce mismatch.',
            'BAD_OTP' => 'The OTP is invalid format.',
            'REPLAYED_OTP' => 'The OTP has already been used.',
            'BAD_SIGNATURE' => 'The HMAC signature verification failed.',
            'MISSING_PARAMETER' => 'The request lacks a parameter.',
            'NO_SUCH_CLIENT' => 'The request id does not exist.',
            'OPERATION_NOT_ALLOWED' => 'The request id is not allowed to verify OTPs.',
            'BACKEND_ERROR' => 'Unexpected error in Yubico server.',
            'NOT_ENOUGH_ANSWERS' => 'Server could not get requested number of syncs during before timeout.',
            'REPLAYED_REQUEST' => 'Server has seen the OTP/Nonce combination before.',
            default => 'Unknown error.',
        };
        // Check if the signature is valid
        if (! $this->verifyResponseSignature($responseData)) {
            $errorMessage = 'Signature mismatch.';
        }
        if ($errorMessage) {
            throw ValidationException::withMessages(['code' => $errorMessage]);
        }
        // Get the first 12 characters of the OTP
        $this->identifier = substr($responseData['otp'], 0, 12);

        return true;
    }

    public function generateSignature(array $parameters): string
    {
        ksort($parameters);

        $signature = base64_encode(hash_hmac('sha1', http_build_query($parameters),
            base64_decode(config('services.yubikey.secret_key')), true));

        return preg_replace('/\+/', '%2B', $signature);
    }

    public function verifyResponseSignature(array $response): bool
    {
        $params = [
            'nonce',
            'otp',
            'sessioncounter',
            'sessionuse',
            'sl',
            'status',
            't',
            'timeout',
            'timestamp',
        ];

        sort($params);

        $check = '';

        foreach ($params as $param) {
            if (array_key_exists($param, $response)) {
                $check .= "&{$param}={$response[$param]}";
            }
        }

        $check = ltrim($check, '&');

        $checkSignature = base64_encode(hash_hmac('sha1', utf8_encode($check),
            base64_decode(config('services.yubikey.secret_key')),
            true));

        return hash_equals($response['h'], $checkSignature);
    }

    public function convertResponseToArray(string $response): array
    {
        $lines = explode("\n", $response);
        $arrayData = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            $parts = explode('=', $line, 2);
            $arrayData[$parts[0]] = $parts[1];
        }

        return $arrayData;
    }
}
