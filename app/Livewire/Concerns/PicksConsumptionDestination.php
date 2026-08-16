<?php

namespace App\Livewire\Concerns;

use App\Enums\ConsumptionDestination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

trait PicksConsumptionDestination
{
    public string $destinationType = '';

    public $destinationId = null;

    public string $destinationSearch = '';

    public function updatedDestinationType(): void
    {
        $this->destinationId = null;
        $this->destinationSearch = '';
        $this->resetValidation(['destinationType', 'destinationId']);
    }

    public function updatedDestinationSearch(): void
    {
        $selectedLabel = $this->selectedDestinationLabel();
        if ($selectedLabel !== null && $this->destinationSearch === $selectedLabel) {
            return;
        }

        $this->destinationId = null;
    }

    public function selectDestination(int $id): void
    {
        $type = ConsumptionDestination::tryFrom($this->destinationType);
        $model = $type?->find($id);
        if (! $model) {
            return;
        }

        $this->destinationId = $model->getKey();
        $this->destinationSearch = $type->labelFor($model);
    }

    public function clearDestination(): void
    {
        $this->destinationId = null;
        $this->destinationSearch = '';
    }

    protected function resetDestination(): void
    {
        $this->destinationType = '';
        $this->destinationId = null;
        $this->destinationSearch = '';
    }

    protected function resolveDestination(): ?Model
    {
        $type = ConsumptionDestination::tryFrom($this->destinationType);
        if (! $type || ! $this->destinationId) {
            return null;
        }

        return $type->find((int) $this->destinationId);
    }

    /**
     * @return array<string, mixed>
     */
    protected function destinationValidationRules(): array
    {
        return [
            'destinationType' => ['required', Rule::enum(ConsumptionDestination::class)],
            'destinationId' => ['required', 'integer'],
        ];
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    protected function destinationMatches(): Collection
    {
        if ($this->destinationId) {
            return collect();
        }

        $type = ConsumptionDestination::tryFrom($this->destinationType);
        if (! $type) {
            return collect();
        }

        return $type->search($this->destinationSearch);
    }

    protected function selectedDestinationLabel(): ?string
    {
        $destination = $this->resolveDestination();
        if (! $destination) {
            return null;
        }

        return ConsumptionDestination::tryFromModel($destination)?->labelFor($destination);
    }

    /**
     * @return array{destinationTypes: list<ConsumptionDestination>, destinationMatches: Collection<int, array{id: int, label: string}>, selectedDestinationLabel: ?string}
     */
    protected function destinationPickerViewData(): array
    {
        return [
            'destinationTypes' => ConsumptionDestination::cases(),
            'destinationMatches' => $this->destinationMatches(),
            'selectedDestinationLabel' => $this->selectedDestinationLabel(),
        ];
    }
}
