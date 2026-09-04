<?php

namespace App\Livewire\Concerns;

trait ScopesToEmployee
{
    public ?int $employeeId = null;

    public function isEmployeeScoped(): bool
    {
        return $this->employeeId !== null && $this->employeeId > 0;
    }

    /**
     * @param  array<string, mixed>  $indexQueryString
     * @return array<string, mixed>
     */
    protected function scopedQueryString(array $indexQueryString): array
    {
        return $this->isEmployeeScoped() ? [] : $indexQueryString;
    }
}
