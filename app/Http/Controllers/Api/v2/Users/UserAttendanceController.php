<?php

namespace App\Http\Controllers\Api\v2\Users;

use App\Http\Controllers\Api\v2\Concerns\ChecksScopes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v2\StoreAttendanceRequest;
use App\Http\Requests\Api\v2\UpdateAttendanceRequest;
use App\Http\Resources\V2\AttendanceResource;
use App\Models\ConventionAttendee;
use Illuminate\Http\Request;

class UserAttendanceController extends Controller
{
    use ChecksScopes;

    public function index(Request $request, string $user)
    {
        $this->requireScope('Attendances.Read');

        $target = $this->resolveUser($request, $user);

        if ($target->id !== $request->user()->id) {
            $this->requireScope('Attendances.Read.All');
        }

        $query = ConventionAttendee::where('user_id', $target->id);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            if (in_array('convention', $includes, true)) {
                $query->with('convention');
            }
        }

        $attendances = $query->get();

        return response()->json(
            AttendanceResource::collection($attendances)->toArray($request),
        );
    }

    public function store(StoreAttendanceRequest $request, string $user)
    {
        $this->requireScope('Attendances.ReadWrite');

        $target = $this->resolveUser($request, $user);

        if ($target->id !== $request->user()->id) {
            $this->requireScope('Attendances.ReadWrite.All');
        }

        $attendance = ConventionAttendee::create([
            'user_id' => $target->id,
            'convention_id' => $request->validated('convention_id'),
            'is_attended' => true,
            'is_staff' => false,
        ]);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            if (in_array('convention', $includes, true)) {
                $attendance->load('convention');
            }
        }

        return (new AttendanceResource($attendance))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAttendanceRequest $request, string $user, int $attendance)
    {
        $this->requireScope('Attendances.ReadWrite');

        $target = $this->resolveUser($request, $user);
        $isSelf = $target->id === $request->user()->id;

        if (! $isSelf) {
            $this->requireScope('Attendances.ReadWrite.All');
        }

        $record = ConventionAttendee::where('user_id', $target->id)
            ->where('id', $attendance)
            ->firstOrFail();

        $data = [];

        if ($request->has('is_attended')) {
            $data['is_attended'] = $request->boolean('is_attended');
        }

        if ($request->has('is_staff')) {
            if ($isSelf) {
                abort(403, 'You cannot change your own staff status.');
            }

            $this->requireScope('Attendances.ReadWrite.All');
            $this->authorizeManageUser($request->user(), $target);

            $data['is_staff'] = $request->boolean('is_staff');
        }

        $record->update($data);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            if (in_array('convention', $includes, true)) {
                $record->load('convention');
            }
        }

        return new AttendanceResource($record);
    }

    public function destroy(Request $request, string $user, int $attendance)
    {
        $this->requireScope('Attendances.ReadWrite');

        $target = $this->resolveUser($request, $user);

        if ($target->id !== $request->user()->id) {
            $this->requireScope('Attendances.ReadWrite.All');
        }

        $record = ConventionAttendee::where('user_id', $target->id)
            ->where('id', $attendance)
            ->firstOrFail();

        if ($record->is_staff) {
            abort(409, 'Cannot remove: staff assignment must be removed by a director first.');
        }

        $record->delete();

        return response()->noContent();
    }
}
