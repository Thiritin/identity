<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\App;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the messages for the status codes
     *
     * @var string[]
     */

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function report(Throwable $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    public function render(
        $request,
        Throwable $e
    ): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response|\Illuminate\Http\RedirectResponse {
        $response = parent::render($request, $e);
        $status = $response->getStatusCode();

        // For API requests, always return the proper response (even in local/testing)
        if ($request->is('api/*') || $request->expectsJson()) {
            return $response;
        }

        if (App::isLocal()) {
            return $response;
        }

        if (! in_array($status, [401, 402, 403, 404, 405, 500, 503])) {
            return $response;
        }

        return inertia('Auth/Error', [
            'title' => "Error $status",
            'description' => $response->exception?->getMessage(),
        ])
            ->toResponse($request)
            ->setStatusCode($status);
    }
}
