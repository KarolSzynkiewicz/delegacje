<?php

namespace App\Enums;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Vehicle;
use App\Models\Accommodation;
use Illuminate\Database\Eloquent\Model;

enum CommentableType: string
{
    case PROJECT = 'project';
    case PROJECT_TASK = 'project_task';
    case VEHICLE = 'vehicle';
    case ACCOMMODATION = 'accommodation';

    public function modelClass(): string
    {
        return match($this) {
            self::PROJECT => Project::class,
            self::PROJECT_TASK => ProjectTask::class,
            self::VEHICLE => Vehicle::class,
            self::ACCOMMODATION => Accommodation::class,
        };
    }

    public static function fromModel(Model $model): self
    {
        return match($model::class) {
            Project::class => self::PROJECT,
            ProjectTask::class => self::PROJECT_TASK,
            Vehicle::class => self::VEHICLE,
            Accommodation::class => self::ACCOMMODATION,
            default => throw new \InvalidArgumentException("Model " . $model::class . " is not commentable"),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
