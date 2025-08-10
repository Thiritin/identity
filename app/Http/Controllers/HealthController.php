<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $dbStatus = 'ok';
        } catch (\Exception $e) {
            $dbStatus = 'error';
        }

        $status = $dbStatus === 'ok' ? 'healthy' : 'unhealthy';
        $httpCode = $status === 'healthy' ? 200 : 503;

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'services' => [
                'database' => $dbStatus,
            ],
        ], $httpCode);
    }
}