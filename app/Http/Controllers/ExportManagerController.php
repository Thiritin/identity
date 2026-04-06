<?php

namespace App\Http\Controllers;

use App\Enums\GroupTypeEnum;
use App\Models\GroupUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportManagerController extends Controller
{
    private const ALLOWED_FIELDS = [
        'department',
        'username',
        'first_name',
        'last_name',
        'role',
        'title',
        'credit_as',
        'pronouns',
    ];

    public function index(Request $request)
    {
        if (! $request->user()->is_hr && ! $request->user()->is_admin) {
            abort(403);
        }

        return Inertia::render('ExportManager');
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->user()->is_hr && ! $request->user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['string', 'in:' . implode(',', self::ALLOWED_FIELDS)],
        ]);

        $fields = $validated['fields'];

        $memberships = GroupUser::with(['user', 'group'])
            ->whereHas('group', fn ($q) => $q->whereIn('type', [
                GroupTypeEnum::Division->value,
                GroupTypeEnum::Department->value,
                GroupTypeEnum::Team->value,
            ]))
            ->cursor();

        $filename = 'staff-export-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($memberships, $fields) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $fields);

            foreach ($memberships as $membership) {
                $row = [];
                foreach ($fields as $field) {
                    $row[] = match ($field) {
                        'department' => $membership->group->name,
                        'username' => $membership->user->name,
                        'first_name' => $membership->user->firstname,
                        'last_name' => $membership->user->lastname,
                        'role' => $membership->level?->name ?? 'Member',
                        'title' => $membership->title,
                        'credit_as' => $membership->credit_as ?? $membership->user->credit_as,
                        'pronouns' => $membership->user->pronouns,
                    };
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
