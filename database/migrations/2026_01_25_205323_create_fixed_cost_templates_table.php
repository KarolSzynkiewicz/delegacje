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
        Schema::create('fixed_cost_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('PLN');
            $table->enum('interval_type', ['monthly', 'weekly', 'yearly']);
            $table->integer('interval_day')->comment('Dzień miesiąca (1-31) dla monthly, dzień tygodnia (1-7) dla weekly, dzień roku dla yearly');
            $table->date('start_date')->nullable()->comment('Data rozpoczęcia obowiązywania szablonu');
            $table->date('end_date')->nullable()->comment('Data zakończenia obowiązywania szablonu');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_cost_templates');
    }
};
