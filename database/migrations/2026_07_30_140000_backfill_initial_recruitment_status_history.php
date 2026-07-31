<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $processes = DB::table('recruitment_processes')->select('id', 'status', 'created_at')->get();

        foreach ($processes as $process) {
            $hasInitial = DB::table('recruitment_status_history')
                ->where('recruitment_process_id', $process->id)
                ->whereNull('from_status')
                ->exists();

            if ($hasInitial) {
                continue;
            }

            DB::table('recruitment_status_history')->insert([
                'recruitment_process_id' => $process->id,
                'from_status' => null,
                'to_status' => 'nowy',
                'changed_by' => null,
                'created_at' => $process->created_at,
                'updated_at' => $process->created_at,
            ]);
        }
    }

    public function down(): void
    {
        // Keep history — removing initial entries could hide audit trail after rollback.
    }
};
