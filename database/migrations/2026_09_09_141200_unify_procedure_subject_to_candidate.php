<?php

use App\Enums\ProcedureSubjectType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('procedure_templates')
            ->where('subject_type', ProcedureSubjectType::RecruitmentProcess->value)
            ->update(['subject_type' => ProcedureSubjectType::RecruitmentCandidate->value]);
    }

    public function down(): void
    {
        // Irreversible: candidate and process were the same subject in the editor.
    }
};
