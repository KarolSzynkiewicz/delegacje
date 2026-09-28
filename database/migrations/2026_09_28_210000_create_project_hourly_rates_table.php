<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_hourly_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'start_date']);
        });

        $now = now();

        DB::table('projects')
            ->where('type', 'hourly')
            ->whereNotNull('hourly_rate')
            ->orderBy('id')
            ->select(['id', 'hourly_rate', 'currency', 'start_date', 'created_at'])
            ->chunkById(200, function ($projects) use ($now) {
                $rows = [];

                foreach ($projects as $project) {
                    $start = $project->start_date
                        ? Carbon::parse($project->start_date)->toDateString()
                        : Carbon::parse($project->created_at)->toDateString();

                    $rows[] = [
                        'project_id' => $project->id,
                        'amount' => $project->hourly_rate,
                        'currency' => $project->currency ?: 'EUR',
                        'start_date' => $start,
                        'end_date' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('project_hourly_rates')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_hourly_rates');
    }
};
