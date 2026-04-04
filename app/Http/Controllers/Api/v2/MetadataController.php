<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v2\UpsertMetadataRequest;
use App\Http\Resources\V2\MetadataResource;
use App\Models\UserAppMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MetadataController extends Controller
{
    private function clientId(): string
    {
        return Auth::guard('api')->getClientId();
    }

    private function requireScope(string $scope): void
    {
        if (! in_array($scope, Auth::guard('api')->getScopes(), true)) {
            abort(403, 'Missing required scope: ' . $scope);
        }
    }

    public function index(Request $request)
    {
        $this->requireScope('metadata.read');

        $metadata = UserAppMetadata::where('user_id', $request->user()->id)
            ->where('client_id', $this->clientId())
            ->get();

        return MetadataResource::collection($metadata);
    }

    public function show(Request $request, string $key)
    {
        $this->requireScope('metadata.read');

        $metadata = UserAppMetadata::where('user_id', $request->user()->id)
            ->where('client_id', $this->clientId())
            ->where('key', $key)
            ->firstOrFail();

        return new MetadataResource($metadata);
    }

    public function upsert(UpsertMetadataRequest $request, string $key)
    {
        $this->requireScope('metadata.write');

        $metadata = UserAppMetadata::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'client_id' => $this->clientId(),
                'key' => $key,
            ],
            [
                'value' => $request->validated('value'),
                'expires_at' => $request->validated('expires_at'),
            ]
        );

        return (new MetadataResource($metadata))
            ->response()
            ->setStatusCode($metadata->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, string $key)
    {
        $this->requireScope('metadata.write');

        $metadata = UserAppMetadata::where('user_id', $request->user()->id)
            ->where('client_id', $this->clientId())
            ->where('key', $key)
            ->firstOrFail();

        $metadata->delete();

        return response()->noContent();
    }
}
