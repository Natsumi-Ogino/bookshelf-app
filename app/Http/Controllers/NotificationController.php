<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * ログインユーザー自身の通知を新しい順に表示します。
     *
     * @param  Request  $request  認証ユーザーを含むリクエスト
     * @return View 通知一覧画面
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 所有者を確認したうえで、指定された通知を既読にします。
     *
     * @param  Request  $request  認証ユーザーを含むリクエスト
     * @param  DatabaseNotification  $notification  既読にする通知
     * @return RedirectResponse 通知一覧へのリダイレクト
     */
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
