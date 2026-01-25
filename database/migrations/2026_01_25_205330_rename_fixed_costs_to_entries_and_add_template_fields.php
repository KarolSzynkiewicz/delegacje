<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Zmień nazwę tabeli fixed_costs na fixed_cost_entries (jeśli jeszcze nie zmieniona)
        if (Schema::hasTable('fixed_costs') && !Schema::hasTable('fixed_cost_entries')) {
            Schema::rename('fixed_costs', 'fixed_cost_entries');
        }
        
        // Najpierw dodaj nowe kolumny jako nullable (jeśli jeszcze nie istnieją)
        Schema::table('fixed_cost_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('fixed_cost_entries', 'period_start')) {
                $table->date('period_start')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('fixed_cost_entries', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }
            if (!Schema::hasColumn('fixed_cost_entries', 'accounting_date')) {
                $table->date('accounting_date')->nullable()->after('period_end')->comment('Data księgowania kosztu');
            }
            if (!Schema::hasColumn('fixed_cost_entries', 'template_id')) {
                $table->foreignId('template_id')->nullable()->after('accounting_date')
                    ->constrained('fixed_cost_templates')->onDelete('set null');
            }
        });
        
        // Wypełnij nowe kolumny danymi z istniejących rekordów (jeśli mają start_date/cost_date)
        if (Schema::hasColumn('fixed_cost_entries', 'start_date') || Schema::hasColumn('fixed_cost_entries', 'cost_date')) {
            DB::table('fixed_cost_entries')->get()->each(function ($entry) {
                $periodStart = null;
                $periodEnd = null;
                $accountingDate = null;
                
                if (isset($entry->start_date) && $entry->start_date) {
                    $periodStart = $entry->start_date;
                } elseif (isset($entry->cost_date) && $entry->cost_date) {
                    $periodStart = $entry->cost_date;
                }
                
                if (isset($entry->end_date) && $entry->end_date) {
                    $periodEnd = $entry->end_date;
                } elseif (isset($entry->cost_date) && $entry->cost_date) {
                    $periodEnd = $entry->cost_date;
                }
                
                if (isset($entry->cost_date) && $entry->cost_date) {
                    $accountingDate = $entry->cost_date;
                } elseif (isset($entry->start_date) && $entry->start_date) {
                    $accountingDate = $entry->start_date;
                }
                
                if ($periodStart && $periodEnd && $accountingDate) {
                    DB::table('fixed_cost_entries')
                        ->where('id', $entry->id)
                        ->update([
                            'period_start' => $periodStart,
                            'period_end' => $periodEnd,
                            'accounting_date' => $accountingDate,
                        ]);
                }
            });
        }
        
        // Teraz zmień kolumny na NOT NULL (jeśli są nullable)
        if (Schema::hasColumn('fixed_cost_entries', 'period_start')) {
            DB::statement('ALTER TABLE fixed_cost_entries MODIFY period_start DATE NOT NULL');
        }
        if (Schema::hasColumn('fixed_cost_entries', 'period_end')) {
            DB::statement('ALTER TABLE fixed_cost_entries MODIFY period_end DATE NOT NULL');
        }
        if (Schema::hasColumn('fixed_cost_entries', 'accounting_date')) {
            DB::statement('ALTER TABLE fixed_cost_entries MODIFY accounting_date DATE NOT NULL');
        }
        
        // Usuń stare pola
        Schema::table('fixed_cost_entries', function (Blueprint $table) {
            if (Schema::hasColumn('fixed_cost_entries', 'start_date')) {
                $table->dropColumn('start_date');
            }
            if (Schema::hasColumn('fixed_cost_entries', 'end_date')) {
                $table->dropColumn('end_date');
            }
            if (Schema::hasColumn('fixed_cost_entries', 'cost_date')) {
                $table->dropColumn('cost_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fixed_cost_entries', function (Blueprint $table) {
            if (Schema::hasColumn('fixed_cost_entries', 'template_id')) {
                $table->dropForeign(['template_id']);
                $table->dropColumn('template_id');
            }
            if (!Schema::hasColumn('fixed_cost_entries', 'start_date')) {
                $table->date('start_date')->after('currency');
            }
            if (!Schema::hasColumn('fixed_cost_entries', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('fixed_cost_entries', 'cost_date')) {
                $table->date('cost_date')->after('currency');
            }
        });
        
        // Wypełnij stare kolumny danymi z nowych
        DB::table('fixed_cost_entries')->get()->each(function ($entry) {
            DB::table('fixed_cost_entries')
                ->where('id', $entry->id)
                ->update([
                    'start_date' => $entry->period_start,
                    'end_date' => $entry->period_end,
                    'cost_date' => $entry->accounting_date,
                ]);
        });
        
        Schema::table('fixed_cost_entries', function (Blueprint $table) {
            if (Schema::hasColumn('fixed_cost_entries', 'accounting_date')) {
                $table->dropColumn('accounting_date');
            }
            if (Schema::hasColumn('fixed_cost_entries', 'period_end')) {
                $table->dropColumn('period_end');
            }
            if (Schema::hasColumn('fixed_cost_entries', 'period_start')) {
                $table->dropColumn('period_start');
            }
        });
        
        if (Schema::hasTable('fixed_cost_entries') && !Schema::hasTable('fixed_costs')) {
            Schema::rename('fixed_cost_entries', 'fixed_costs');
        }
    }
};
