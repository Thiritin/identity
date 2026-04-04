<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
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
    ): Response|JsonResponse|\Symfony\Component\HttpFoundation\Response|RedirectResponse {
        if ($e instanceof ThrottleRequestsException && $request->header('X-Inertia')) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

            return back()->withErrors([
                'throttle' => __('too_many_attempts', ['seconds' => $retryAfter]),
            ]);
        }

        $response = parent::render($request, $e);
        $status = $response->getStatusCode();

        // For API requests, always return the proper response (even in local/testing)
        if ($request->is('api/*') || $request->expectsJson()) {
            return $response;
        }

        if (App::isLocal()) {
            return $response;
        }

        // Filament (admin panel) uses Blade/Livewire, not Inertia
        if ($request->is('admin/*', 'admin', 'livewire/*')) {
            return $response;
        }

        if ($status === 419) {
            return back()->with([
                'message' => __('session_expired'),
            ]);
        }

        if (! in_array($status, [401, 402, 403, 404, 405, 429, 500, 503])) {
            return $response;
        }

        return inertia('Auth/Error', [
            'status' => $status,
            'homeUrl' => url('/'),
        ])
            ->toResponse($request)
            ->setStatusCode($status);
    }
}
