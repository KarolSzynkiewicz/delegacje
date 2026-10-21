<?php

use App\Services\CostRegistryService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rejestr kosztów — cienka otoczka nad istniejącymi tabelami kosztów
 * (fixed_cost_entries, project_variable_costs, transport_costs, vehicle_repairs).
 *
 * Kwota, waluta i data zostają w tabeli źródłowej. Otoczka trzyma tylko to,
 * czego źródła nie mają: spółkę, miesiąc rozliczeniowy, nazwę rejestrową,
 * komentarz i znacznik „nieistotny finansowo”.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_entries', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 64);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->boolean('financially_irrelevant')->default(false);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index(['period_year', 'period_month']);
            $table->index('financially_irrelevant');
        });

        // Istniejące koszty od razu trafiają do rejestru — bez tego tablica
        // startuje pusta, a stare pozycje pojawiałyby się dopiero po edycji źródła.
        app(CostRegistryService::class)->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_entries');
    }
};
