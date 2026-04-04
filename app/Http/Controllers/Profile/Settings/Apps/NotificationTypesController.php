<?php

namespace App\Http\Controllers\Profile\Settings\Apps;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\Apps\StoreNotificationTypeRequest;
use App\Http\Requests\Profile\Apps\UpdateNotificationTypeRequest;
use App\Models\App;
use App\Models\AppNotificationRecord;
use App\Models\NotificationType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationTypesController extends Controller
{
    public function index(App $app): Response
    {
        $this->authorizeApp($app);

        return Inertia::render('Settings/Apps/NotificationTypes', [
            'app' => $app->only(['id', 'name', 'client_id']),
            'types' => $app->notificationTypes()->orderBy('category')->orderBy('key')->get(),
        ]);
    }

    public function store(StoreNotificationTypeRequest $request, App $app): RedirectResponse
    {
        $this->authorizeApp($app);

        $app->notificationTypes()->create([
            'key' => $request->input('key'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'category' => $request->input('category'),
            'default_channels' => $request->input('default_channels'),
        ]);

        return back();
    }

    public function update(UpdateNotificationTypeRequest $request, App $app, NotificationType $type): RedirectResponse
    {
        $this->authorizeApp($app);
        abort_if($type->app_id !== $app->id, 404);

        $type->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'default_channels' => $request->input('default_channels'),
        ]);

        return back();
    }

    public function destroy(App $app, NotificationType $type): RedirectResponse
    {
        $this->authorizeApp($app);
        abort_if($type->app_id !== $app->id, 404);

        $inUse = AppNotificationRecord::where('notification_type_id', $type->id)->exists();
        if ($inUse) {
            return back()->withErrors(['type' => 'Cannot delete a type that has been used. Disable it instead.']);
        }

        $type->delete();

        return back();
    }

    public function disable(App $app, NotificationType $type): RedirectResponse
    {
        $this->authorizeApp($app);
        abort_if($type->app_id !== $app->id, 404);

        $type->update(['disabled' => true]);

        return back();
    }

    private function authorizeApp(App $app): void
    {
        abort_if($app->user_id !== auth()->id(), 403);
        abort_if(! $app->allow_notifications, 403);
    }
}
