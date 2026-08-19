<?php

namespace App\Livewire\Pulse;

use App\Models\User;
use App\Pulse\UserRouteMatrix;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Locked;

class UserRouteUsage extends Card
{
    /**
     * @var array<string, string>
     */
    public const PERIODS = [
        '1_hour' => '1h',
        '6_hours' => '6h',
        '24_hours' => '24h',
        '7_days' => '7d',
    ];

    #[Locked]
    public int $userId;

    /**
     * @var list<string>
     */
    public array $expanded = [];

    public function mount(int $userId): void
    {
        $this->ensureCanViewUsers();
        $this->userId = $userId;
    }

    public function setPeriod(string $period): void
    {
        if (! array_key_exists($period, self::PERIODS)) {
            return;
        }

        $this->period = $period;
    }

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
        $this->ensureCanViewUsers();

        $user = User::query()->findOrFail($this->userId);

        [$matrix] = $this->remember(function () use ($user) {
            return app(UserRouteMatrix::class)->buildForUser(
                $this->aggregate('user_route', 'count', limit: 5000),
                $user,
            );
        }, (string) $this->userId);

        $matrixRows = app(UserRouteMatrix::class)->flattenVisible($matrix['tree'], $this->expanded);

        return View::make('livewire.pulse.user-route-usage', [
            'usageUser' => $matrix['users']->first(),
            'matrixRows' => $matrixRows,
            'columnTotals' => $matrix['column_totals'],
            'periodLabel' => $this->periodLabel(),
        ]);
    }

    public function periodLabel(): string
    {
        return match ($this->period) {
            '6_hours' => '6 godzin',
            '24_hours' => '24 godziny',
            '7_days' => '7 dni',
            default => '1 godzinę',
        };
    }

    private function ensureCanViewUsers(): void
    {
        $viewer = auth()->user();

        abort_unless($viewer instanceof User && $viewer->hasPermission('users.view'), 403);
    }
}
