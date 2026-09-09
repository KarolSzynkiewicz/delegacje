<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Identity employment lifecycle lives on Employee. hired_at / terminated_at
     * are the current-state snapshot; employee_lifecycle_events is the
     * append-only history. Recruitment processes stay a hiring pipeline —
     * they are not rewritten and no longer receive fake hire/terminate rows.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->timestamp('hired_at')->nullable()->after('outside_base');
            $table->index('hired_at');
        });

        DB::table('employees')->whereNull('hired_at')->update([
            'hired_at' => DB::raw('created_at'),
        ]);

        Schema::create('employee_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->timestamp('occurred_at');
            $table->string('reason', 30)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recruitment_process_id')->nullable()->constrained('recruitment_processes')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'occurred_at']);
            $table->index(['type', 'occurred_at']);
        });

        $now = now();
        $rows = [];

        DB::table('employees')
            ->select(['id', 'hired_at', 'created_at', 'terminated_at', 'termination_reason', 'termination_note'])
            ->orderBy('id')
            ->each(function ($employee) use (&$rows, $now) {
                $hiredAt = $employee->hired_at ?? $employee->created_at;
                $rows[] = [
                    'employee_id' => $employee->id,
                    'type' => 'hired',
                    'occurred_at' => $hiredAt,
                    'reason' => null,
                    'note' => null,
                    'recruitment_process_id' => null,
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($employee->terminated_at !== null) {
                    $rows[] = [
                        'employee_id' => $employee->id,
                        'type' => 'terminated',
                        'occurred_at' => $employee->terminated_at,
                        'reason' => $employee->termination_reason,
                        'note' => $employee->termination_note,
                        'recruitment_process_id' => null,
                        'created_by' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            });

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('employee_lifecycle_events')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_lifecycle_events');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['hired_at']);
            $table->dropColumn('hired_at');
        });
    }
};
