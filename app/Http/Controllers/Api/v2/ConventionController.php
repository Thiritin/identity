<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\ConventionResource;
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
        return new ConventionResource(
            Convention::current()->firstOrFail()
        );
    }
}
