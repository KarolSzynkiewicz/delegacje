<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('office_leave_adjustments')) {
            return;
        }

        Schema::create('office_leave_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_profile_id')->constrained('office_profiles')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->smallInteger('days'); // + dodaje, − odejmuje od puli
            $table->string('reason');
            $table->date('effective_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['office_profile_id', 'year']);
            $table->index(['year', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_leave_adjustments');
    }
};
