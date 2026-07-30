<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Moves expected_rate_eur, shipyard_experience, available_from and the role
     * skill-set from recruitment_processes to recruitment_candidates.
     *
     * Rationale: these are properties of the person, not of a single recruitment
     * attempt. When a candidate changes their rate expectation or availability a
     * recruiter previously had to update every active process separately.
     *
     * Existing data is migrated: per-candidate the most-recently-updated non-null
     * value from any process is used.  Distinct role pairs from all processes are
     * merged into the new recruitment_candidate_role pivot.
     */
    public function up(): void
    {
        // ── 1. Add skill columns to candidates ───────────────────────────
        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->decimal('expected_rate_eur', 6, 2)->nullable()->after('photo_path');
            $table->string('shipyard_experience', 30)->nullable()->after('expected_rate_eur');
            $table->date('available_from')->nullable()->after('shipyard_experience');
        });

        // ── 2. Create candidate ↔ role pivot ─────────────────────────────
        Schema::create('recruitment_candidate_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_candidate_id')
                ->constrained('recruitment_candidates')
                ->cascadeOnDelete();
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['recruitment_candidate_id', 'role_id'], 'recruitment_candidate_role_unique');
        });

        // ── 3. Migrate data ───────────────────────────────────────────────
        // Copy the most-recent non-null rate / experience / available_from per candidate.
        DB::statement("
            UPDATE recruitment_candidates rc
            LEFT JOIN (
                SELECT candidate_id,
                       MAX(CASE WHEN expected_rate_eur IS NOT NULL THEN expected_rate_eur END) AS rate
                FROM recruitment_processes
                GROUP BY candidate_id
            ) agg ON agg.candidate_id = rc.id
            SET rc.expected_rate_eur = agg.rate
            WHERE agg.rate IS NOT NULL
        ");

        DB::statement("
            UPDATE recruitment_candidates rc
            LEFT JOIN (
                SELECT candidate_id,
                       SUBSTRING_INDEX(GROUP_CONCAT(shipyard_experience ORDER BY updated_at DESC), ',', 1) AS exp
                FROM recruitment_processes
                WHERE shipyard_experience IS NOT NULL
                GROUP BY candidate_id
            ) agg ON agg.candidate_id = rc.id
            SET rc.shipyard_experience = agg.exp
            WHERE agg.exp IS NOT NULL
        ");

        DB::statement("
            UPDATE recruitment_candidates rc
            LEFT JOIN (
                SELECT candidate_id,
                       available_from
                FROM recruitment_processes
                WHERE available_from IS NOT NULL
                ORDER BY updated_at DESC
                LIMIT 1
            ) agg ON agg.candidate_id = rc.id
            SET rc.available_from = agg.available_from
            WHERE agg.available_from IS NOT NULL
        ");

        // Copy distinct role associations from process pivot to candidate pivot.
        DB::statement("
            INSERT IGNORE INTO recruitment_candidate_role (recruitment_candidate_id, role_id, created_at, updated_at)
            SELECT DISTINCT rp.candidate_id, rpr.role_id, NOW(), NOW()
            FROM recruitment_process_role rpr
            JOIN recruitment_processes rp ON rp.id = rpr.recruitment_process_id
        ");

        // ── 4. Drop old columns and pivot from processes ──────────────────
        Schema::dropIfExists('recruitment_process_role');

        Schema::table('recruitment_processes', function (Blueprint $table) {
            $table->dropColumn(['expected_rate_eur', 'shipyard_experience', 'available_from']);
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_processes', function (Blueprint $table) {
            $table->decimal('expected_rate_eur', 6, 2)->nullable();
            $table->string('shipyard_experience', 30)->nullable();
            $table->date('available_from')->nullable();
        });

        Schema::create('recruitment_process_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['recruitment_process_id', 'role_id'], 'recruitment_process_role_unique');
        });

        Schema::dropIfExists('recruitment_candidate_role');

        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->dropColumn(['expected_rate_eur', 'shipyard_experience', 'available_from']);
        });
    }
};
