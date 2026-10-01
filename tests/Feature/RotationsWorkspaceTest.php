<?php

namespace Tests\Feature;

use App\Livewire\RotationsWorkspace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RotationsWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_to_axis_and_switches_to_table(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(RotationsWorkspace::class)
            ->assertSet('view', 'axis')
            ->assertSee('Widok osi')
            ->assertSee('Widok tabeli')
            ->call('setView', 'table')
            ->assertSet('view', 'table');

        $this->assertSame('table', session('rotations_view'));
    }

    public function test_restores_view_from_session(): void
    {
        $user = User::factory()->create();
        session(['rotations_view' => 'table']);

        Livewire::actingAs($user)
            ->test(RotationsWorkspace::class)
            ->assertSet('view', 'table');
    }
}
