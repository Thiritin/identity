<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntrospectionRequest;
use App\Http\Resources\V1\TokenResource;
use App\Domains\Auth\Services\Client;

class IntrospectionController extends Controller
{
    public function __invoke(IntrospectionRequest $request, Client $client)
    {
        $data = $client->getToken($request->post('token'), explode(' ', $request->post('scope')));

        return new TokenResource($data);
    }
}
