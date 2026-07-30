<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Splits what used to be a single `recruitment_applications` row into three concepts:
     * a persistent Candidate identity, a Lead (one inbound submission), and a Process
     * (the pipeline instance recruiters actually work).
     */
    public function up(): void
    {
        Schema::create('recruitment_candidates', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('city', 100)->nullable();

            $table->boolean('has_driving_license_b')->default(false);
            $table->boolean('speaks_english')->default(false);
            $table->boolean('speaks_french')->default(false);
            $table->boolean('speaks_german')->default(false);

            $table->string('photo_path')->nullable();

            // Wartościowy kandydat / czarna lista — dotyczy osoby, nie pojedynczego zgłoszenia.
            $table->string('rating', 30)->nullable();
            $table->text('rating_note')->nullable();

            $table->timestamps();
        });

        Schema::create('recruitment_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('recruitment_candidates')->cascadeOnDelete();

            $table->string('referral_source', 30)->nullable();
            $table->text('cover_letter')->nullable();

            $table->timestamps();

            $table->index('candidate_id');
        });

        Schema::create('recruitment_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('recruitment_leads')->cascadeOnDelete();
            // Denormalized on purpose: the admin pipeline table filters/sorts/searches by
            // candidate name, email and phone together with status constantly, so this
            // avoids turning every list query into a multi-join.
            $table->foreignId('candidate_id')->constrained('recruitment_candidates')->cascadeOnDelete();

            $table->string('status', 30)->default('nowy')->index();
            $table->foreignId('assigned_recruiter_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('expected_rate_eur', 6, 2)->nullable();
            $table->string('shipyard_experience', 30)->nullable();
            $table->date('available_from')->nullable();
            $table->text('admin_notes')->nullable();

            $table->string('rejection_reason', 40)->nullable();
            $table->text('rejection_reason_note')->nullable();

            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('recruitment_process_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['recruitment_process_id', 'role_id'], 'recruitment_process_role_unique');
        });

        Schema::create('recruitment_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_process_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['recruitment_process_id', 'created_at'], 'recruitment_status_history_process_created_idx');
        });

        Schema::create('recruitment_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('recruitment_candidates')->cascadeOnDelete();
            $table->foreignId('recruitment_lead_id')->nullable()->constrained('recruitment_leads')->nullOnDelete();

            $table->string('type', 30);
            $table->timestamp('given_at');
            $table->timestamp('withdrawn_at')->nullable();

            $table->timestamps();

            $table->index(['candidate_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_consents');
        Schema::dropIfExists('recruitment_status_history');
        Schema::dropIfExists('recruitment_process_role');
        Schema::dropIfExists('recruitment_processes');
        Schema::dropIfExists('recruitment_leads');
        Schema::dropIfExists('recruitment_candidates');
    }
};
