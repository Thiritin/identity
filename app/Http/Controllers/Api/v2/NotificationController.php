<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v2\SendNotificationRequest;
use App\Jobs\SendAppNotificationJob;
use App\Models\App;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function store(SendNotificationRequest $request): Response
    {
        $this->requireScope('notifications.send');

        $clientId = Auth::guard('api')->getClientId();
        $app = App::where('client_id', $clientId)->first();

        if (! $app) {
            abort(403, 'unknown client');
        }

        if (! $app->allow_notifications) {
            abort(403, 'notifications not enabled for this app');
        }

        $type = NotificationType::where('app_id', $app->id)
            ->where('key', $request->input('type'))
            ->where('disabled', false)
            ->first();

        if (! $type) {
            abort(404);
        }

        $user = User::findByHashid($request->input('user_id'));
        if (! $user) {
            abort(404);
        }

        SendAppNotificationJob::dispatch($type->id, $user->id, [
            'subject' => $request->input('subject'),
            'body' => $request->input('body'),
            'html' => $request->input('html'),
            'cta' => $request->input('cta'),
        ]);

        return response()->noContent(202);
    }

    private function requireScope(string $scope): void
    {
        if (! in_array($scope, Auth::guard('api')->getScopes(), true)) {
            abort(403, 'missing required scope: ' . $scope);
        }
    }
}
