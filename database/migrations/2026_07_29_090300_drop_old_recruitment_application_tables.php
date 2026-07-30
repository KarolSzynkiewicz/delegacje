<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Superseded by recruitment_candidates / recruitment_leads / recruitment_processes.
     * Dropped outright (not backfilled) — production has no candidatures worth preserving.
     * `employees` is never touched by this migration.
     */
    public function up(): void
    {
        Schema::dropIfExists('recruitment_application_role');
        Schema::dropIfExists('recruitment_applications');
    }

    public function down(): void
    {
        // Intentionally not reversible — the old schema is superseded, not renamed.
        // Restore from a backup if you need the pre-split schema back.
    }
};
