<?php

namespace Tests\Unit\Pulse;

use App\Models\User;
use App\Pulse\UserRouteMatrix;
use Tests\TestCase;

class UserRouteMatrixTest extends TestCase
{
    public function test_fills_missing_user_route_pairs_with_zero(): void
    {
        $matrix = (new UserRouteMatrix)->build(
            collect([
                (object) ['user_id' => '1', 'method' => 'GET', 'path' => '/projects/{project}', 'count' => 4],
            ]),
            collect([
                (object) ['id' => '1', 'name' => 'Karol', 'extra' => 'karol@example.com'],
                (object) ['id' => '2', 'name' => 'Anna', 'extra' => 'anna@example.com'],
            ]),
            collect([
                (object) ['method' => 'GET', 'path' => '/projects/{project}'],
                (object) ['method' => 'GET', 'path' => '/vehicles/{vehicle}'],
            ]),
        );

        $projects = $matrix['tree']->firstWhere('label', 'projects');
        $vehicles = $matrix['tree']->firstWhere('label', 'vehicles');

        $this->assertSame(4, $projects->cells['1']);
        $this->assertSame(0, $projects->cells['2']);
        $this->assertSame(0, $vehicles->cells['1']);
        $this->assertSame(0, $vehicles->cells['2']);
        $this->assertSame(4, $matrix['column_totals']['1']);
        $this->assertSame(0, $matrix['column_totals']['2']);
    }

    public function test_groups_routes_by_path_segments_and_sums_children(): void
    {
        $matrix = (new UserRouteMatrix)->build(
            collect([
                (object) ['user_id' => '1', 'method' => 'GET', 'path' => '/employee-documents/{employeeDocument}', 'count' => 2],
                (object) ['user_id' => '1', 'method' => 'GET', 'path' => '/employee-documents/{employeeDocument}/download', 'count' => 3],
                (object) ['user_id' => '1', 'method' => 'GET', 'path' => '/employee-documents/{employeeDocument}/edit', 'count' => 1],
            ]),
            collect([
                (object) ['id' => '1', 'name' => 'Karol', 'extra' => ''],
            ]),
            collect([
                (object) ['method' => 'GET', 'path' => '/employee-documents/{employeeDocument}'],
                (object) ['method' => 'GET', 'path' => '/employee-documents/{employeeDocument}/download'],
                (object) ['method' => 'GET', 'path' => '/employee-documents/{employeeDocument}/edit'],
                (object) ['method' => 'GET', 'path' => '/employee-documents/{employeeDocument}/preview'],
            ]),
        );

        $root = $matrix['tree']->first();
        $this->assertSame('employee-documents', $root->label);
        $this->assertSame(6, $root->cells['1']);
        $this->assertTrue($root->has_children);

        $param = $root->children->firstWhere('label', '{employeeDocument}');
        $this->assertNotNull($param);
        $this->assertSame(6, $param->cells['1']);
        $this->assertTrue($param->has_children);
        $this->assertSame(3, $param->children->firstWhere('label', 'download')->cells['1']);
        $this->assertSame(1, $param->children->firstWhere('label', 'edit')->cells['1']);
        $this->assertSame(0, $param->children->firstWhere('label', 'preview')->cells['1']);
        $this->assertSame(2, $param->children->firstWhere('label', UserRouteMatrix::SELF_LABEL)->cells['1']);
        $this->assertSame(UserRouteMatrix::SELF_LABEL, $param->children->first()->label);

        $collapsed = (new UserRouteMatrix)->flattenVisible($matrix['tree'], []);
        $this->assertCount(1, $collapsed);
        $this->assertSame('employee-documents', $collapsed->first()->label);

        $expanded = (new UserRouteMatrix)->flattenVisible($matrix['tree'], [
            '/employee-documents',
            '/employee-documents/{employeeDocument}',
        ]);
        $this->assertTrue($expanded->contains(fn ($row) => $row->label === 'download'));
        $this->assertTrue($expanded->contains(fn ($row) => $row->label === 'preview'));
        $this->assertSame('/employee-documents/{employeeDocument}', $expanded->firstWhere('label', 'download')->parent_path);
    }

    public function test_sorts_groups_alphabetically(): void
    {
        $matrix = (new UserRouteMatrix)->build(
            collect([
                (object) ['user_id' => '1', 'method' => 'GET', 'path' => '/tasks', 'count' => 99],
            ]),
            collect([
                (object) ['id' => '1', 'name' => 'Karol', 'extra' => ''],
            ]),
            collect([
                (object) ['method' => 'GET', 'path' => '/employee-rates'],
                (object) ['method' => 'GET', 'path' => '/employees'],
                (object) ['method' => 'GET', 'path' => '/tasks'],
            ]),
        );

        $this->assertSame(
            ['employee-rates', 'employees', 'tasks'],
            $matrix['tree']->pluck('label')->all()
        );
    }

    public function test_build_for_user_keeps_only_that_users_used_routes(): void
    {
        $user = new User;
        $user->id = 7;
        $user->name = 'Karol';
        $user->email = 'karol@example.com';

        $matrix = (new UserRouteMatrix)->buildForUser(
            collect([
                (object) ['key' => json_encode(['7', 'GET', '/projects/{project}']), 'count' => 4],
                (object) ['key' => json_encode(['7', 'GET', '/projects/{project}/edit']), 'count' => 1],
                (object) ['key' => json_encode(['8', 'GET', '/vehicles']), 'count' => 9],
            ]),
            $user,
        );

        $this->assertSame(['7'], $matrix['users']->pluck('id')->all());
        $this->assertSame(['projects'], $matrix['tree']->pluck('label')->all());
        $this->assertSame(5, $matrix['column_totals']['7']);
        $this->assertFalse($matrix['tree']->contains(fn ($row) => $row->label === 'vehicles'));
    }

    public function test_build_for_user_drops_zero_counts(): void
    {
        $user = new User;
        $user->id = 7;
        $user->name = 'Karol';
        $user->email = 'karol@example.com';

        $matrix = (new UserRouteMatrix)->buildForUser(
            collect([
                (object) ['key' => json_encode(['7', 'GET', '/projects']), 'count' => 2],
                (object) ['key' => json_encode(['7', 'GET', '/vehicles']), 'count' => 0],
            ]),
            $user,
        );

        $this->assertSame(['projects'], $matrix['tree']->pluck('label')->all());
        $this->assertFalse($matrix['tree']->contains(fn ($row) => $row->label === 'vehicles'));
    }

    public function test_prune_unused_drops_zero_branches(): void
    {
        $matrix = (new UserRouteMatrix)->build(
            collect([
                (object) ['user_id' => '1', 'method' => 'GET', 'path' => '/employee-documents/{employeeDocument}/download', 'count' => 3],
            ]),
            collect([
                (object) ['id' => '1', 'name' => 'Karol', 'extra' => ''],
            ]),
            collect([
                (object) ['method' => 'GET', 'path' => '/employee-documents/{employeeDocument}/download'],
                (object) ['method' => 'GET', 'path' => '/employee-documents/{employeeDocument}/preview'],
                (object) ['method' => 'GET', 'path' => '/vehicles'],
            ]),
        );

        $pruned = (new UserRouteMatrix)->pruneUnused($matrix['tree']);

        $this->assertSame(['employee-documents'], $pruned->pluck('label')->all());

        $expanded = (new UserRouteMatrix)->flattenVisible($pruned, [
            '/employee-documents',
            '/employee-documents/{employeeDocument}',
        ]);

        $this->assertTrue($expanded->contains(fn ($row) => $row->label === 'download'));
        $this->assertFalse($expanded->contains(fn ($row) => $row->label === 'preview'));
        $this->assertFalse($expanded->contains(fn ($row) => $row->label === 'vehicles'));
    }
}
