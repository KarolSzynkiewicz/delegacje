<?php

namespace App\Livewire;

use App\Models\Equipment;
use App\Services\EquipmentService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class EquipmentStockTimeline extends Component
{
    use WithPagination;

    public int $equipmentId;

    public function mount(Equipment $equipment): void
    {
        $this->equipmentId = $equipment->id;
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render(EquipmentService $equipmentService)
    {
        $equipment = Equipment::query()->findOrFail($this->equipmentId);
        $entries = $equipmentService->stockTimeline($equipment);
        $perPage = 15;
        $page = $this->getPage('movementsPage');

        $paginator = new LengthAwarePaginator(
            $entries->forPage($page, $perPage)->values(),
            $entries->count(),
            $perPage,
            $page,
            ['pageName' => 'movementsPage']
        );

        return view('livewire.equipment-stock-timeline', [
            'entries' => $paginator,
        ]);
    }
}
