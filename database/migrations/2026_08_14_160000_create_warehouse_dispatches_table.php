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
        Schema::create('warehouse_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence');
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('issue_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'sequence']);
            $table->index('warehouse_id');
            $table->index('issue_date');
        });

        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->foreignId('warehouse_dispatch_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('warehouse_dispatches')
                ->restrictOnDelete();
        });

        $this->backfillDispatches();
    }

    public function down(): void
    {
        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_dispatch_id');
        });
        Schema::dropIfExists('warehouse_dispatches');
    }

    private function backfillDispatches(): void
    {
        $issues = DB::table('equipment_issues')->orderBy('id')->get();
        if ($issues->isEmpty()) {
            return;
        }

        $sequences = [];
        $groups = $issues->groupBy(fn ($issue) => $issue->batch_id ?: 'issue-'.$issue->id);

        foreach ($groups as $group) {
            $first = $group->first();
            $year = (int) Carbon::parse($first->issue_date)->year;
            $sequences[$year] = ($sequences[$year] ?? 0) + 1;
            $sequence = $sequences[$year];
            $dispatchId = DB::table('warehouse_dispatches')->insertGetId([
                'number' => sprintf('WZ-%d-%04d', $year, $sequence),
                'year' => $year,
                'sequence' => $sequence,
                'warehouse_id' => $first->warehouse_id,
                'issue_date' => $first->issue_date,
                'notes' => $first->notes,
                'created_by' => $first->issued_by,
                'created_at' => $first->created_at,
                'updated_at' => $first->updated_at,
            ]);

            DB::table('equipment_issues')
                ->whereIn('id', $group->pluck('id')->all())
                ->update(['warehouse_dispatch_id' => $dispatchId]);
        }
    }
};
