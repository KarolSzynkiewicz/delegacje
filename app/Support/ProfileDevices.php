<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ProfileDevices
{
    /**
     * @return list<array{id: string|null, label: string, icon: string, ip: string, seen: string, current: bool}>
     */
    public static function sessions(User $user, bool $includeCurrentFallback = true): array
    {
        $isOwnProfile = auth()->id() === $user->id;
        $currentId = hash('sha256', (string) session()->getId());
        $rows = collect();

        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            $cutoff = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();
            $rows = DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('last_activity', '>=', $cutoff)
                ->orderByDesc('last_activity')
                ->get(['id', 'ip_address', 'user_agent', 'last_activity']);
        }

        $sessions = $rows->map(fn (object $row) => [
            'id' => (string) $row->id,
            'label' => UserAgentLabel::describe($row->user_agent),
            'icon' => UserAgentLabel::icon($row->user_agent),
            'ip' => $row->ip_address ?: '—',
            'seen' => Carbon::createFromTimestamp((int) $row->last_activity)->locale('pl')->diffForHumans(),
            'current' => $isOwnProfile && hash_equals($currentId, (string) $row->id),
        ])->all();

        if (
            $includeCurrentFallback
            && $isOwnProfile
            && ! collect($sessions)->contains(fn (array $session) => $session['current'])
        ) {
            array_unshift($sessions, [
                'id' => null,
                'label' => UserAgentLabel::describe(request()->userAgent()),
                'icon' => UserAgentLabel::icon(request()->userAgent()),
                'ip' => request()->ip() ?: '—',
                'seen' => 'teraz',
                'current' => true,
            ]);
        }

        return $sessions;
    }

    /**
     * @return list<array{id: int, label: string, icon: string, seen: string}>
     */
    public static function pushDevices(User $user): array
    {
        return $user->pushSubscriptions()
            ->latest()
            ->get()
            ->map(function ($device) {
                $agent = $device->user_agent ?? null;

                return [
                    'id' => (int) $device->id,
                    'label' => filled($agent)
                        ? UserAgentLabel::describe($agent)
                        : UserAgentLabel::pushHost($device->endpoint),
                    'icon' => UserAgentLabel::icon($agent),
                    'seen' => $device->updated_at?->locale('pl')->diffForHumans() ?? '',
                ];
            })
            ->all();
    }
}
