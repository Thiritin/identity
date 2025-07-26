<?php

namespace App\Domains\Staff\Http\Controllers;

use App\Domains\Staff\Enums\GroupTypeEnum;
use App\Domains\Staff\Enums\GroupUserLevel;
use App\Domains\Staff\Models\Group;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationChartController extends Controller
{
    /**
     * Display the organizational chart
     */
    public function index(): Response
    {
        $organizationData = $this->buildOrganizationChart();

        return Inertia::render('Staff/OrganizationChart', [
            'organizationData' => $organizationData,
        ]);
    }

    /**
     * Get organization data for API endpoints
     */
    public function getData()
    {
        return response()->json($this->buildOrganizationChart());
    }

    /**
     * Build the organization chart data structure
     */
    private function buildOrganizationChart(): array
    {
        // Get BOD (root level)
        $bod = Group::where('type', GroupTypeEnum::BOD)->first();
        
        if (!$bod) {
            return [];
        }

        return $this->buildNodeData($bod, true);
    }

    /**
     * Build node data recursively
     */
    private function buildNodeData(Group $group, bool $includeChildren = true): array
    {
        $nodeData = [
            'key' => $group->id,
            'type' => $group->type->value,
            'label' => $group->name,
            'data' => [
                'name' => $group->name,
                'description' => $group->description,
                'type' => $group->type->getDisplayName(),
                'logo_url' => $group->logo_url,
                'hierarchy_path' => $group->getHierarchyPath(),
            ],
        ];

        // Add leadership information
        $leadership = $this->getLeadershipInfo($group);
        if (!empty($leadership)) {
            $nodeData['data']['leadership'] = $leadership;
        }

        // Add children if requested
        if ($includeChildren) {
            $children = $group->children()
                ->with(['users' => function ($query) {
                    $query->wherePivotIn('level', [
                        GroupUserLevel::Director->value,
                        GroupUserLevel::DivisionDirector->value,
                        GroupUserLevel::TeamLead->value,
                    ]);
                }])
                ->orderBy('type')
                ->orderBy('name')
                ->get();

            if ($children->isNotEmpty()) {
                $nodeData['children'] = $children->map(function ($child) {
                    return $this->buildNodeData($child, true);
                })->toArray();
            }
        }

        return $nodeData;
    }

    /**
     * Get leadership information for a group
     */
    private function getLeadershipInfo(Group $group): array
    {
        $leadership = [];

        // Get leadership based on group type
        $leaders = match ($group->type) {
            GroupTypeEnum::BOD => $group->leadership()->get(),
            GroupTypeEnum::Division => $group->users()
                ->wherePivot('level', GroupUserLevel::DivisionDirector->value)
                ->get(),
            GroupTypeEnum::Department => $group->users()
                ->wherePivot('level', GroupUserLevel::Director->value)
                ->get(),
            GroupTypeEnum::Team => $group->users()
                ->wherePivot('level', GroupUserLevel::TeamLead->value)
                ->get(),
            default => $group->leadership()->get(),
        };

        foreach ($leaders as $leader) {
            $leadership[] = [
                'id' => $leader->id,
                'name' => $leader->name,
                'email' => $leader->email,
                'avatar_url' => $leader->profile_photo_url ?? null,
                'role' => $leader->pivot->level->getDisplayName(),
                'title' => $leader->pivot->title ?? null,
                'can_manage_users' => $leader->pivot->canManageUsers(),
            ];
        }

        return $leadership;
    }

    /**
     * Get expanded view with teams
     */
    public function expanded()
    {
        $organizationData = $this->buildOrganizationChart();

        return Inertia::render('Staff/OrganizationChartExpanded', [
            'organizationData' => $organizationData,
        ]);
    }
}
