<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recreates recruitment_contact_attempts pointing at recruitment_processes instead of
     * the old recruitment_applications table. No data preserved on purpose (see plan notes:
     * production has no recruitment candidatures worth migrating).
     */
    public function up(): void
    {
        Schema::dropIfExists('recruitment_contact_attempts');

        Schema::create('recruitment_contact_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('outcome', 30);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['recruitment_process_id', 'created_at'], 'recruitment_contact_attempts_process_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_contact_attempts');
    }
};
