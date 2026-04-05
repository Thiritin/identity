<?php

namespace App\Http\Controllers\Api\v2;

use App\Enums\GroupTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\GroupStoreRequest;
use App\Http\Requests\GroupUpdateRequest;
use App\Http\Resources\V2\GroupResource;
use App\Models\Group;
use App\Services\DirectoryTreeBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    private function requireScope(string $scope): void
    {
        if (! in_array($scope, Auth::guard('api')->getScopes(), true)) {
            abort(403, 'Missing required scope: ' . $scope);
        }
    }

    public function index(Request $request)
    {
        $this->requireScope('groups.read');
        $this->authorize('viewAny', Group::class);

        $query = Group::query();

        if ($request->filled('type')) {
            $types = collect(explode(',', $request->input('type')))
                ->map(fn (string $type) => GroupTypeEnum::tryFrom(trim($type)))
                ->filter()
                ->all();

            if (! empty($types)) {
                $query->whereIn('type', $types);
            }
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if (! $request->user()->isStaff()) {
            $query->whereHas('users', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        $query->withCount('users');

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));

            if (in_array('children', $includes, true)) {
                $query->with(['children' => fn ($q) => $q->withCount('users')]);
            }

            if (in_array('members', $includes, true)) {
                $query->with('users');
            }

            if (in_array('parent', $includes, true)) {
                $query->with('parent');
            }
        }

        $paginator = $query->simplePaginate(25);

        return response()->json(
            GroupResource::collection($paginator)->toArray($request),
        );
    }

    public function show(Request $request, string $groupHashid)
    {
        $this->requireScope('groups.read');

        $group = Group::findByHashidOrFail($groupHashid);
        $this->authorize('view', [$group, $request->user()]);

        $group->loadCount('users');

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));

            if (in_array('children', $includes, true)) {
                $group->load(['children' => fn ($q) => $q->withCount('users')]);
            }

            if (in_array('members', $includes, true)) {
                $group->load('users');
            }

            if (in_array('parent', $includes, true)) {
                $group->load('parent');
            }
        }

        return new GroupResource($group);
    }

    public function tree(Request $request, DirectoryTreeBuilder $builder)
    {
        $this->requireScope('groups.read');

        $myGroupIds = $request->user()->groups()->pluck('groups.id')->all();
        $tree = $builder->build($myGroupIds);

        $depth = $request->integer('depth', 3);
        if ($depth < 3) {
            $tree = $this->limitDepth($tree, $depth);
        }

        return response()->json($tree);
    }

    public function store(GroupStoreRequest $request)
    {
        $this->requireScope('groups.write');
        $this->authorize('create', Group::class);

        $group = Group::create($request->validationData());

        return (new GroupResource($group))
            ->response()
            ->setStatusCode(201);
    }

    public function update(GroupUpdateRequest $request, string $groupHashid)
    {
        $this->requireScope('groups.write');

        $group = Group::findByHashidOrFail($groupHashid);
        $this->authorize('update', [$group, $request->user()]);

        $group->fill($request->validationData());
        $group->save();

        return new GroupResource($group);
    }

    public function destroy(Request $request, string $groupHashid)
    {
        $this->requireScope('groups.delete');

        $group = Group::findByHashidOrFail($groupHashid);
        $this->authorize('delete', [$group, $request->user()]);

        $group->delete();

        return response()->noContent();
    }

    private function limitDepth(array $tree, int $maxDepth, int $currentDepth = 1): array
    {
        return array_map(function (array $node) use ($maxDepth, $currentDepth) {
            if ($currentDepth >= $maxDepth) {
                $node['children'] = [];
            } else {
                $node['children'] = $this->limitDepth($node['children'], $maxDepth, $currentDepth + 1);
            }

            return $node;
        }, $tree);
    }
}
