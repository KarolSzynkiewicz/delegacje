<?php

namespace App\Pulse;

use App\Models\User;
use App\Pulse\Recorders\UserRoutes;
use Illuminate\Routing\Route as RouterRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class UserRouteMatrix
{
    public const SELF_LABEL = '(this page)';

    /**
     * @param  Collection<int, object{key: string, count?: int|string}>  $rows
     * @return Collection<int, object{user_id: string, method: string, path: string, count: int}>
     */
    public function decodeCounts(Collection $rows): Collection
    {
        return $rows
            ->map(function ($row) {
                $decoded = json_decode($row->key, true);

                if (! is_array($decoded) || count($decoded) < 3) {
                    return null;
                }

                [$userId, $method, $path] = $decoded;

                return (object) [
                    'user_id' => (string) $userId,
                    'method' => (string) $method,
                    'path' => (string) $path,
                    'count' => (int) $row->count,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, object{key: string, count?: int|string}>  $aggregateRows
     * @return array{users: Collection, tree: Collection, column_totals: array<string, int>}
     */
    public function buildForUser(Collection $aggregateRows, User $user): array
    {
        $users = collect([
            (object) [
                'id' => (string) $user->id,
                'name' => $user->name,
                'extra' => $user->email ?? '',
            ],
        ]);

        $counts = $this->decodeCounts($aggregateRows)
            ->filter(fn ($row) => $row->user_id === (string) $user->id && $row->count > 0)
            ->values();

        $matrix = $this->build($counts, $users, collect());
        $matrix['tree'] = $this->pruneUnused($matrix['tree']);
        $matrix['column_totals'] = [
            (string) $user->id => $matrix['tree']->sum(fn ($node) => $node->cells[(string) $user->id] ?? 0),
        ];

        return $matrix;
    }

    /**
     * @param  Collection<int, object{total: int, children: Collection}>  $tree
     * @return Collection<int, object>
     */
    public function pruneUnused(Collection $tree): Collection
    {
        return $tree
            ->map(function ($node) {
                $visible = clone $node;
                $visible->children = $this->pruneUnused($node->children);
                $visible->has_children = $visible->children->isNotEmpty();

                return $visible;
            })
            ->filter(fn ($node) => $node->total > 0)
            ->values();
    }

    /**
     * @param  Collection<int, object{user_id: string, method: string, path: string, count: int}>  $counts
     * @param  Collection<int, object{id: string, name: string, extra: string}>  $users
     * @param  Collection<int, object{method: string, path: string}>  $catalog
     * @return array{users: Collection, tree: Collection, column_totals: array<string, int>}
     */
    public function build(Collection $counts, Collection $users, Collection $catalog): array
    {
        $emptyCells = $this->emptyCells($users);
        $leaves = $this->leaves($counts, $catalog, $users);
        $tree = $this->toCollection(
            $this->rollup($this->insertLeaves([], $leaves, $emptyCells), $users),
            $users,
            depth: 0,
        );

        $columnTotals = [];

        foreach ($users as $user) {
            $columnTotals[$user->id] = $tree->sum(fn ($node) => $node->cells[$user->id]);
        }

        return [
            'users' => $users,
            'tree' => $tree,
            'column_totals' => $columnTotals,
        ];
    }

    /**
     * @param  Collection<int, object{id: string}>  $users
     * @return Collection<int, object>
     */
    public function flattenVisible(Collection $tree, array $expanded): Collection
    {
        $rows = collect();

        foreach ($tree as $node) {
            $isExpanded = in_array($node->key, $expanded, true);
            $visible = clone $node;
            $visible->expanded = $isExpanded;
            $rows->push($visible);

            if ($isExpanded && $node->children->isNotEmpty()) {
                $rows = $rows->concat($this->flattenVisible($node->children, $expanded));
            }
        }

        return $rows;
    }

    /**
     * @return Collection<int, object{method: string, path: string}>
     */
    public function catalogFromRouter(): Collection
    {
        return collect(Route::getRoutes())
            ->filter(fn (RouterRoute $route) => in_array('GET', $route->methods(), true))
            ->map(function (RouterRoute $route) {
                $path = $this->group(Str::start($route->uri(), '/'));

                return $this->shouldIgnore($path) ? null : (object) [
                    'method' => 'GET',
                    'path' => $path,
                ];
            })
            ->filter()
            ->unique(fn ($row) => $this->rowKey($row->method, $row->path))
            ->values();
    }

    public function rowKey(string $method, string $path): string
    {
        return strtoupper($method).' '.$path;
    }

    /**
     * @param  Collection<int, object{id: string}>  $users
     * @return array<string, int>
     */
    private function emptyCells(Collection $users): array
    {
        $cells = [];

        foreach ($users as $user) {
            $cells[$user->id] = 0;
        }

        return $cells;
    }

    /**
     * @param  Collection<int, object{user_id: string, method: string, path: string, count: int}>  $counts
     * @param  Collection<int, object{method: string, path: string}>  $catalog
     * @param  Collection<int, object{id: string}>  $users
     * @return Collection<int, object{method: string, path: string, cells: array<string, int>, total: int}>
     */
    private function leaves(Collection $counts, Collection $catalog, Collection $users): Collection
    {
        $cells = [];

        foreach ($counts as $count) {
            $rowKey = $this->rowKey($count->method, $count->path);
            $cells[$rowKey][$count->user_id] = ($cells[$rowKey][$count->user_id] ?? 0) + $count->count;
        }

        return $catalog
            ->concat($counts->map(fn ($count) => (object) [
                'method' => $count->method,
                'path' => $count->path,
            ]))
            ->unique(fn ($row) => $this->rowKey($row->method, $row->path))
            ->map(function ($row) use ($users, $cells) {
                $rowKey = $this->rowKey($row->method, $row->path);
                $values = [];
                $total = 0;

                foreach ($users as $user) {
                    $value = (int) ($cells[$rowKey][$user->id] ?? 0);
                    $values[$user->id] = $value;
                    $total += $value;
                }

                return (object) [
                    'method' => $row->method,
                    'path' => $row->path,
                    'cells' => $values,
                    'total' => $total,
                ];
            })
            ->values();
    }

    /**
     * @param  array<string, array<string, mixed>>  $level
     * @param  Collection<int, object{method: string, path: string, cells: array<string, int>}>  $leaves
     * @param  array<string, int>  $emptyCells
     * @return array<string, array<string, mixed>>
     */
    private function insertLeaves(array $level, Collection $leaves, array $emptyCells): array
    {
        foreach ($leaves as $leaf) {
            $segments = $this->segments($leaf->path);

            if ($segments === []) {
                continue;
            }

            $level = $this->insert($level, $segments, $leaf, $emptyCells);
        }

        return $level;
    }

    /**
     * @param  array<string, array<string, mixed>>  $level
     * @param  list<string>  $segments
     * @param  object{method: string, path: string, cells: array<string, int>}  $leaf
     * @param  array<string, int>  $emptyCells
     * @return array<string, array<string, mixed>>
     */
    private function insert(array $level, array $segments, object $leaf, array $emptyCells, string $prefix = ''): array
    {
        $head = array_shift($segments);
        $path = $prefix.'/'.$head;

        if (! isset($level[$head])) {
            $level[$head] = [
                'key' => $path,
                'label' => $head,
                'path' => $path,
                'own_cells' => $emptyCells,
                'children' => [],
                'is_exact' => false,
            ];
        }

        if ($segments === []) {
            foreach ($leaf->cells as $userId => $value) {
                $level[$head]['own_cells'][$userId] = ($level[$head]['own_cells'][$userId] ?? 0) + $value;
            }
            $level[$head]['is_exact'] = true;

            return $level;
        }

        $level[$head]['children'] = $this->insert($level[$head]['children'], $segments, $leaf, $emptyCells, $path);

        return $level;
    }

    /**
     * @param  array<string, array<string, mixed>>  $level
     * @param  Collection<int, object{id: string}>  $users
     * @return array<string, array<string, mixed>>
     */
    private function rollup(array $level, Collection $users): array
    {
        foreach ($level as &$node) {
            $node['children'] = $this->rollup($node['children'], $users);

            if ($node['children'] !== [] && ($node['is_exact'] ?? false)) {
                $node['children'] = [
                    '__self' => [
                        'key' => $node['key'].'/_self',
                        'label' => self::SELF_LABEL,
                        'path' => $node['path'],
                        'own_cells' => $node['own_cells'],
                        'cells' => $node['own_cells'],
                        'children' => [],
                        'is_self' => true,
                    ],
                ] + $node['children'];
                $node['own_cells'] = array_fill_keys(array_keys($node['own_cells']), 0);
            }

            $node['cells'] = $node['own_cells'];

            foreach ($node['children'] as $child) {
                foreach ($users as $user) {
                    $node['cells'][$user->id] = ($node['cells'][$user->id] ?? 0) + ($child['cells'][$user->id] ?? 0);
                }
            }
        }

        return $level;
    }

    /**
     * @param  array<string, array<string, mixed>>  $level
     * @param  Collection<int, object{id: string}>  $users
     */
    private function toCollection(array $level, Collection $users, int $depth, string $parentPath = ''): Collection
    {
        return collect($level)
            ->map(function (array $node) use ($users, $depth, $parentPath) {
                $cells = [];
                $total = 0;

                foreach ($users as $user) {
                    $value = (int) ($node['cells'][$user->id] ?? 0);
                    $cells[$user->id] = $value;
                    $total += $value;
                }

                $children = $this->toCollection($node['children'], $users, $depth + 1, $node['path'])
                    ->sortBy([
                        fn ($child) => $child->is_self ? 0 : 1,
                        fn ($child) => Str::lower($child->label),
                    ])
                    ->values();

                return (object) [
                    'key' => $node['key'],
                    'label' => $node['label'],
                    'path' => $node['path'],
                    'parent_path' => $parentPath,
                    'is_self' => (bool) ($node['is_self'] ?? false),
                    'depth' => $depth,
                    'has_children' => $children->isNotEmpty(),
                    'children' => $children,
                    'cells' => $cells,
                    'total' => $total,
                    'expanded' => false,
                ];
            })
            ->sortBy(fn ($node) => Str::lower($node->label))
            ->values();
    }

    private function segments(string $path): array
    {
        return array_values(array_filter(explode('/', $path), fn (string $segment) => $segment !== ''));
    }

    private function shouldIgnore(string $path): bool
    {
        return collect(Config::get('pulse.recorders.'.UserRoutes::class.'.ignore', []))
            ->contains(fn (string $pattern) => preg_match($pattern, $path) === 1);
    }

    private function group(string $path): string
    {
        foreach (Config::get('pulse.recorders.'.UserRoutes::class.'.groups', []) as $pattern => $replacement) {
            $grouped = preg_replace($pattern, $replacement, $path, count: $count);

            if ($count > 0 && $grouped !== null) {
                return $grouped;
            }
        }

        return $path;
    }
}
