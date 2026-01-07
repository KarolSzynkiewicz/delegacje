<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('equipment_issues')) {
            Schema::create('equipment_issues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('equipment_id')->constrained('equipment')->onDelete('restrict');
                $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
                $table->foreignId('project_assignment_id')->nullable()->constrained('project_assignments')->onDelete('set null');
                $table->integer('quantity_issued')->default(1);
                $table->date('issue_date');
                $table->date('expected_return_date')->nullable();
                $table->date('actual_return_date')->nullable();
                $table->string('status')->default('issued'); // issued, returned, lost, damaged
                $table->text('notes')->nullable();
                $table->foreignId('issued_by')->constrained('users')->onDelete('restrict');
                $table->foreignId('returned_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();

                $table->index('employee_id');
                $table->index('project_assignment_id');
                $table->index('status');
                $table->index('issue_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_issues');
    }
};
