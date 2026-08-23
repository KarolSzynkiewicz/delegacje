<?php

namespace App\Livewire\Concerns;

trait InteractsWithSortableTable
{
    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->sortableFields(), true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    protected function applySortToQuery($query, ?array $allowed = null): void
    {
        $allowed = $allowed ?? $this->sortableFields();
        $field = in_array($this->sortField, $allowed, true) ? $this->sortField : ($allowed[0] ?? 'id');
        $dir = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $query->orderBy($field, $dir);
    }

    /**
     * @return list<string>
     */
    protected function sortableFields(): array
    {
        return ['name'];
    }
}
