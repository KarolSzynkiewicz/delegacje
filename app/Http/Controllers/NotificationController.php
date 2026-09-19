<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->where('id', $notification)->firstOrFail();
        $item->markAsRead();

        $data = $item->data ?? [];
        $url = $data['resource_url'] ?? $data['task_url'] ?? $data['url'] ?? null;
        if (! is_string($url) || $url === '') {
            return redirect()->route('notifications.index');
        }

        $app = rtrim(url('/'), '/');
        if ($url !== $app && ! str_starts_with($url, $app.'/')) {
            return redirect()->route('notifications.index');
        }

        return redirect()->to($url);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->back();
    }
}
