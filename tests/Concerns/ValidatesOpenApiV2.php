<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

/**
 * Validates a Laravel TestResponse against the v2 OpenAPI contract at
 * docs/static/contracts/identity/api/v2/identity.oas.2.0.yml.
 *
 * Usage inside a Pest test file:
 *
 *   uses(Tests\Concerns\ValidatesOpenApiV2::class);
 *
 *   $this->assertMatchesOpenApiV2($response, '/conventions');
 *   $this->assertMatchesOpenApiV2($response, '/conventions/current');
 *
 * The path must match the OAS paths (which are relative to the `servers` URL —
 * i.e. no `/api/v2` prefix).
 */
trait ValidatesOpenApiV2
{
    private static ?ResponseValidator $openApiV2ResponseValidator = null;

    private static ?PsrHttpFactory $openApiV2PsrHttpFactory = null;

    public function assertMatchesOpenApiV2(
        TestResponse $response,
        string $path,
        string $method = 'get',
    ): void {
        $validator = self::openApiV2ResponseValidator();
        $psr7Response = self::openApiV2PsrHttpFactory()->createResponse($response->baseResponse);
        $operation = new OperationAddress($path, strtolower($method));

        try {
            $validator->validate($operation, $psr7Response);
        } catch (\Throwable $e) {
            $this->fail(sprintf(
                "Response does not match OpenAPI v2 spec for %s %s:\n%s",
                strtoupper($method),
                $path,
                $e->getMessage(),
            ));
        }
    }

    private static function openApiV2ResponseValidator(): ResponseValidator
    {
        if (self::$openApiV2ResponseValidator === null) {
            self::$openApiV2ResponseValidator = (new ValidatorBuilder())
                ->fromYamlFile(base_path('docs/static/contracts/identity/api/v2/identity.oas.2.0.yml'))
                ->getResponseValidator();
        }

        return self::$openApiV2ResponseValidator;
    }

    private static function openApiV2PsrHttpFactory(): PsrHttpFactory
    {
        if (self::$openApiV2PsrHttpFactory === null) {
            $psr17 = new Psr17Factory;
            self::$openApiV2PsrHttpFactory = new PsrHttpFactory($psr17, $psr17, $psr17, $psr17);
        }

        return self::$openApiV2PsrHttpFactory;
    }
}
