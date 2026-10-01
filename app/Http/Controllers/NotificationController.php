<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    public function read(
        Request $request,
        DatabaseNotification $notification
    ): RedirectResponse {
        $isOwner = $notification->notifiable_type === $request->user()::class
            && (string) $notification->notifiable_id === (string) $request->user()->getKey();

        abort_unless($isOwner, 403);

        $notification->markAsRead();

        return redirect()
            ->route('notifications.index')
            ->with('success', '通知を既読にしました。');
    }
}
