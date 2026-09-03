<?php

namespace App\Enums;

use App\Models\Accommodation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentProcess;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

enum ProcedureSubjectType: string
{
    case Vehicle = 'vehicle';
    case Accommodation = 'accommodation';
    case Location = 'location';
    case Employee = 'employee';
    case Project = 'project';
    case RecruitmentCandidate = 'recruitment_candidate';
    case RecruitmentProcess = 'recruitment_process';

    public function label(): string
    {
        return match ($this) {
            self::Vehicle => 'Samochód',
            self::Accommodation => 'Zakwaterowanie',
            self::Location => 'Lokalizacja',
            self::Employee => 'Pracownik',
            self::Project => 'Projekt',
            self::RecruitmentCandidate => 'Kandydat',
            self::RecruitmentProcess => 'Proces rekrutacji',
        };
    }

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Vehicle => Vehicle::class,
            self::Accommodation => Accommodation::class,
            self::Location => Location::class,
            self::Employee => Employee::class,
            self::Project => Project::class,
            self::RecruitmentCandidate => RecruitmentCandidate::class,
            self::RecruitmentProcess => RecruitmentProcess::class,
        };
    }

    public function labelFor(Model $model): string
    {
        $fallback = '#'.$model->getKey();

        return match ($this) {
            self::Vehicle => trim(implode(' ', array_filter([
                $model->registration_number ?? null,
                trim(($model->brand ?? '').' '.($model->model ?? '')),
            ]))) ?: $fallback,
            self::Accommodation, self::Location, self::Project => (string) ($model->name ?: $fallback),
            self::Employee, self::RecruitmentCandidate => trim(($model->first_name ?? '').' '.($model->last_name ?? '')) ?: $fallback,
            self::RecruitmentProcess => trim(($model->candidate?->first_name ?? $model->first_name ?? '').' '.($model->candidate?->last_name ?? $model->last_name ?? '')) ?: $fallback,
        };
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function dropdownOptions(): array
    {
        return $this->query()
            ->get()
            ->map(fn (Model $model) => [
                'id' => (int) $model->getKey(),
                'label' => $this->labelFor($model),
            ])
            ->all();
    }

    public function query(): Builder
    {
        return match ($this) {
            self::Vehicle => Vehicle::query()->orderBy('registration_number'),
            self::Accommodation => Accommodation::query()->orderBy('name'),
            self::Location => Location::query()->without(['purposes'])->orderBy('name'),
            self::Employee => Employee::query()->whereNull('terminated_at')->orderBy('last_name')->orderBy('first_name'),
            self::Project => Project::query()->orderBy('name'),
            self::RecruitmentCandidate => RecruitmentCandidate::query()->orderBy('last_name')->orderBy('first_name'),
            self::RecruitmentProcess => RecruitmentProcess::query()->with('candidate')->latest('id'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Vehicle => 'bi-car-front',
            self::Accommodation => 'bi-house',
            self::Location => 'bi-geo-alt',
            self::Employee => 'bi-person-badge',
            self::Project => 'bi-folder',
            self::RecruitmentCandidate => 'bi-person-vcard',
            self::RecruitmentProcess => 'bi-person-lines-fill',
        };
    }

    public function urlFor(Model $model): ?string
    {
        return match ($this) {
            self::Vehicle => route('vehicles.show', $model),
            self::Accommodation => route('accommodations.show', $model),
            self::Location => route('locations.show', $model),
            self::Employee => route('employees.show', $model),
            self::Project => route('projects.show', $model),
            self::RecruitmentCandidate => $this->recruitmentCandidateUrl($model),
            self::RecruitmentProcess => route('recruitment-processes.show', $model),
        };
    }

    /**
     * @return array{url: string, label: string, icon: string}|null
     */
    public function sourceCardFor(Model $model): ?array
    {
        $url = $this->urlFor($model);
        if (! $url) {
            return null;
        }

        return [
            'url' => $url,
            'label' => $this->label().': '.$this->labelFor($model),
            'icon' => $this->icon(),
        ];
    }

    private function recruitmentCandidateUrl(Model $model): string
    {
        $processId = null;
        if ($model instanceof RecruitmentCandidate) {
            $processId = $model->latestProcess?->id
                ?? $model->processes()->latest('id')->value('id');
        }

        return $processId
            ? route('recruitment-processes.show', $processId)
            : route('recruitment-processes.index');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function formOptions(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
