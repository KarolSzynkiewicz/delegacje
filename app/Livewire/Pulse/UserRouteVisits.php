<?php

namespace App\Livewire\Pulse;

use App\Models\User;
use App\Pulse\Recorders\UserRoutes;
use App\Pulse\UserRouteMatrix;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class UserRouteVisits extends Card
{
    /**
     * @var list<string>
     */
    public array $expanded = [];

    public function toggleGroup(string $key): void
    {
        if (in_array($key, $this->expanded, true)) {
            $this->expanded = array_values(array_diff($this->expanded, [$key]));

            return;
        }

        $this->expanded[] = $key;
    }

    public function render(): Renderable
    {
        [$matrix, $time, $runAt] = $this->remember(function () {
            $counts = app(UserRouteMatrix::class)->decodeCounts(
                $this->aggregate('user_route', 'count', limit: 5000)
            );

            $userIds = $counts->pluck('user_id')
                ->merge(User::query()->pluck('id'))
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values();

            $users = User::query()
                ->whereIn('id', $userIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => (object) [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'extra' => $user->email ?? '',
                ]);

            $missingUserIds = $userIds->diff($users->pluck('id'));

            foreach ($missingUserIds as $userId) {
                $users->push((object) [
                    'id' => $userId,
                    'name' => 'ID: '.$userId,
                    'extra' => '',
                ]);
            }

            return app(UserRouteMatrix::class)->build(
                $counts,
                $users->values(),
                app(UserRouteMatrix::class)->catalogFromRouter(),
            );
        });

        $matrixRows = app(UserRouteMatrix::class)->flattenVisible($matrix['tree'], $this->expanded);

        return View::make('livewire.pulse.user-route-visits', [
            'time' => $time,
            'runAt' => $runAt,
            'matrixUsers' => $matrix['users'],
            'matrixRows' => $matrixRows,
            'columnTotals' => $matrix['column_totals'],
            'config' => Config::get('pulse.recorders.'.UserRoutes::class),
        ]);
    }
}
