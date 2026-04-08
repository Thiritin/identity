<?php

namespace App\Http\Controllers\Api\v2\Users;

use App\Http\Controllers\Api\v2\Concerns\ChecksScopes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v2\UpsertMetadataRequest;
use App\Http\Resources\V2\MetadataResource;
use App\Models\UserAppMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserMetadataController extends Controller
{
    use ChecksScopes;

    private function clientId(): string
    {
        return Auth::guard('api')->getClientId();
    }

    public function index(Request $request, string $user)
    {
        $this->requireScope('Metadata.Read');

        $target = $this->resolveUser($request, $user);

        if ($target->id !== $request->user()->id) {
            abort(403, 'Metadata is only accessible by the owning user.');
        }

        $metadata = UserAppMetadata::where('user_id', $target->id)
            ->where('client_id', $this->clientId())
            ->get();

        return response()->json(
            MetadataResource::collection($metadata)->toArray($request),
        );
    }

    public function show(Request $request, string $user, string $key)
    {
        $this->requireScope('Metadata.Read');

        $target = $this->resolveUser($request, $user);

        if ($target->id !== $request->user()->id) {
            abort(403, 'Metadata is only accessible by the owning user.');
        }

        $metadata = UserAppMetadata::where('user_id', $target->id)
            ->where('client_id', $this->clientId())
            ->where('key', $key)
            ->firstOrFail();

        return new MetadataResource($metadata);
    }

    public function upsert(UpsertMetadataRequest $request, string $user, string $key)
    {
        $this->requireScope('Metadata.ReadWrite');

        $target = $this->resolveUser($request, $user);

        if ($target->id !== $request->user()->id) {
            abort(403, 'Metadata is only accessible by the owning user.');
        }

        $metadata = UserAppMetadata::updateOrCreate(
            [
                'user_id' => $target->id,
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

    public function destroy(Request $request, string $user, string $key)
    {
        $this->requireScope('Metadata.ReadWrite');

        $target = $this->resolveUser($request, $user);

        if ($target->id !== $request->user()->id) {
            abort(403, 'Metadata is only accessible by the owning user.');
        }

        $metadata = UserAppMetadata::where('user_id', $target->id)
            ->where('client_id', $this->clientId())
            ->where('key', $key)
            ->firstOrFail();

        $metadata->delete();

        return response()->noContent();
    }
}
