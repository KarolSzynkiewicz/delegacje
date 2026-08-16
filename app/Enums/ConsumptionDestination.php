<?php

namespace App\Enums;

use App\Models\Accommodation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

enum ConsumptionDestination: string
{
    case Employee = 'employee';
    case Project = 'project';
    case Accommodation = 'accommodation';
    case Vehicle = 'vehicle';

    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Osoba',
            self::Project => 'Projekt',
            self::Accommodation => 'Dom',
            self::Vehicle => 'Auto',
        };
    }

    public function placeholder(): string
    {
        return match ($this) {
            self::Employee => 'Szukaj pracownika…',
            self::Project => 'Szukaj projektu…',
            self::Accommodation => 'Szukaj domu…',
            self::Vehicle => 'Szukaj auta…',
        };
    }

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Employee => Employee::class,
            self::Project => Project::class,
            self::Accommodation => Accommodation::class,
            self::Vehicle => Vehicle::class,
        };
    }

    public function find(int $id): ?Model
    {
        return $this->modelClass()::query()->find($id);
    }

    public function labelFor(Model $model): string
    {
        return match ($this) {
            self::Employee => $model->full_name,
            self::Project => $model->name,
            self::Accommodation => collect([$model->name, $model->city])->filter()->implode(', '),
            self::Vehicle => collect([
                $model->registration_number,
                trim(($model->brand ?? '').' '.($model->model ?? '')),
            ])->filter()->implode(' — '),
        };
    }

    public function hrefFor(Model $model): ?string
    {
        return match ($this) {
            self::Employee => route('employees.show', $model),
            self::Project => route('projects.show', $model),
            self::Accommodation => route('accommodations.show', $model),
            self::Vehicle => route('vehicles.show', $model),
        };
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    public function search(string $term, int $limit = 12): Collection
    {
        $term = trim($term);
        if ($term === '') {
            return collect();
        }

        $like = '%'.addcslashes(mb_strtolower($term), '%_\\').'%';

        return match ($this) {
            self::Employee => $this->searchEmployees($like, $limit),
            self::Project => $this->searchProjects($like, $limit),
            self::Accommodation => $this->searchAccommodations($like, $limit),
            self::Vehicle => $this->searchVehicles($like, $limit),
        };
    }

    public static function tryFromModel(Model $model): ?self
    {
        $alias = $model->getMorphClass();

        return is_string($alias) ? self::tryFrom($alias) : null;
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    private function searchEmployees(string $like, int $limit): Collection
    {
        return Employee::query()
            ->whereNull('terminated_at')
            ->where(function ($employees) use ($like) {
                $employees->whereRaw('LOWER(COALESCE(first_name, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(last_name, \'\')) LIKE ?', [$like])
                    ->orWhereRaw(
                        'LOWER(TRIM(CONCAT(COALESCE(first_name, \'\'), \' \', COALESCE(last_name, \'\')))) LIKE ?',
                        [$like]
                    );
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit)
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'label' => $this->labelFor($employee),
            ]);
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    private function searchProjects(string $like, int $limit): Collection
    {
        return Project::query()
            ->where(function ($projects) use ($like) {
                $projects->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(client_name, \'\')) LIKE ?', [$like]);
            })
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [ProjectStatus::ACTIVE->value])
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'label' => $this->labelFor($project),
            ]);
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    private function searchAccommodations(string $like, int $limit): Collection
    {
        return Accommodation::query()
            ->where(function ($homes) use ($like) {
                $homes->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(city, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(address, \'\')) LIKE ?', [$like]);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Accommodation $home) => [
                'id' => $home->id,
                'label' => $this->labelFor($home),
            ]);
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    private function searchVehicles(string $like, int $limit): Collection
    {
        return Vehicle::query()
            ->where(function ($vehicles) use ($like) {
                $vehicles->whereRaw('LOWER(registration_number) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(brand, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(model, \'\')) LIKE ?', [$like]);
            })
            ->orderBy('registration_number')
            ->limit($limit)
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'label' => $this->labelFor($vehicle),
            ]);
    }
}
