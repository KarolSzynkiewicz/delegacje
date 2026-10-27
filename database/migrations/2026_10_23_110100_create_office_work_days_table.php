<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_work_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_profile_id')->constrained('office_profiles')->cascadeOnDelete();
            $table->date('work_date');
            $table->decimal('hours', 4, 2)->default(0);
            $table->string('location', 16)->default('office'); // office | remote
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['office_profile_id', 'work_date']);
            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_work_days');
    }
};
