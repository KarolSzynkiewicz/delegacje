<?php

namespace App\Models;

use App\Traits\HasComments;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanningWeek extends Model
{
    use HasComments, HasFactory;

    protected $fillable = [
        'week_start',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];

    public static function forDate(CarbonInterface $date): self
    {
        $monday = $date->copy()->startOfWeek()->toDateString();

        return static::query()->firstOrCreate(['week_start' => $monday]);
    }

    public function label(): string
    {
        $start = $this->week_start->copy()->startOfWeek();
        $end = $start->copy()->endOfWeek();

        return 'Tydzień '.$start->isoWeek().' ('.$start->format('d.m').'–'.$end->format('d.m.Y').')';
    }

    public function showUrl(): string
    {
        return route('weekly-overview.index', [
            'start_date' => $this->week_start->format('Y-m-d'),
        ]);
    }
}
