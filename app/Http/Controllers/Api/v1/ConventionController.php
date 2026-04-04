<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ConventionResource;
use App\Models\Convention;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConventionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ConventionResource::collection(
            Convention::query()->orderBy('year')->get()
        );
    }

    public function current(): ConventionResource
    {
        $convention = Convention::query()
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->firstOrFail();

        return new ConventionResource($convention);
    }
}
