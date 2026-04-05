<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\ConventionResource;
use App\Models\Convention;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConventionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conventions = Convention::query()->orderBy('year')->get();

        return response()->json(
            ConventionResource::collection($conventions)->toArray($request),
        );
    }

    public function current(): ConventionResource
    {
        return new ConventionResource(
            Convention::current()->firstOrFail()
        );
    }
}
