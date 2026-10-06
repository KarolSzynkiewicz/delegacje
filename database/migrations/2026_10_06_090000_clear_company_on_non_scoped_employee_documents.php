<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $openTypes = DB::table('documents')
            ->where('is_company_scoped', false)
            ->pluck('id');

        if ($openTypes->isEmpty()) {
            return;
        }

        DB::table('employee_documents')
            ->whereIn('document_id', $openTypes)
            ->whereNotNull('company_id')
            ->update(['company_id' => null]);
    }

    public function down(): void
    {
        //
    }
};
