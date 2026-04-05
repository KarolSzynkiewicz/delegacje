<?php

namespace App\Livewire;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function getUnreadCountProperty(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function getNotificationsProperty()
    {
        return auth()->user()
            ->notifications()
            ->latest()
            ->limit(5)
            ->get();
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;

        // Oznacz wszystkie jako przeczytane przy otwarciu
        if ($this->open) {
            auth()->user()->unreadNotifications->markAsRead();
        }
    }

    public function markRead(string $id): void
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        $notification?->markAsRead();
    }

    public function render()
    {
        return view('livewire.notification-bell', [
            'unreadCount' => $this->unreadCount,
            'notifications' => $this->notifications,
        ]);
    }
}
