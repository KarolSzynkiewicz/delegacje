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
        // Najpierw dodajemy nowe kolumny do project_demands
        Schema::table('project_demands', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('project_id')->constrained()->onDelete('cascade');
            $table->integer('required_count')->default(1)->after('role_id');
            $table->date('date_from')->nullable()->after('required_count');
            $table->date('date_to')->nullable()->after('date_from');
        });

        // Kopiujemy dane z start_date/end_date do date_from/date_to
        DB::table('project_demands')->update([
            'date_from' => DB::raw('start_date'),
            'date_to' => DB::raw('end_date'),
        ]);

        // Przenosimy dane z project_demand_roles do project_demands
        // Dla każdego project_demand_role tworzymy nowy project_demand
        if (Schema::hasTable('project_demand_roles')) {
            $demandRoles = DB::table('project_demand_roles')->get();
            
            foreach ($demandRoles as $demandRole) {
                $demand = DB::table('project_demands')->find($demandRole->project_demand_id);
                
                if ($demand) {
                    // Tworzymy nowy wiersz w project_demands z danymi z project_demand_roles
                    DB::table('project_demands')->insert([
                        'project_id' => $demand->project_id,
                        'role_id' => $demandRole->role_id,
                        'required_count' => $demandRole->required_count,
                        'date_from' => $demand->date_from ?? $demand->start_date,
                        'date_to' => $demand->date_to ?? $demand->end_date,
                        'notes' => $demand->notes,
                        'created_at' => $demand->created_at,
                        'updated_at' => now(),
                    ]);
                }
            }
            
            // Usuwamy stare wiersze z project_demands (te bez role_id)
            DB::table('project_demands')
                ->whereNull('role_id')
                ->delete();
        }

        // Usuwamy stare kolumny i ustawiamy role_id jako wymagane
        Schema::table('project_demands', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'required_workers_count']);
            // Ustawiamy role_id jako wymagane (nie nullable) po przeniesieniu danych
            $table->foreignId('role_id')->nullable(false)->change();
            $table->date('date_from')->nullable(false)->change();
        });

        // Usuwamy tabelę project_demand_roles
        Schema::dropIfExists('project_demand_roles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Przywracamy tabelę project_demand_roles
        Schema::create('project_demand_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_demand_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->integer('required_count')->default(1);
            $table->timestamps();
        });

        // Przywracamy kolumny w project_demands
        Schema::table('project_demands', function (Blueprint $table) {
            $table->date('start_date')->after('date_to');
            $table->date('end_date')->nullable()->after('start_date');
            $table->integer('required_workers_count')->default(0)->after('end_date');
            
            // Kopiujemy dane z date_from/date_to do start_date/end_date
            DB::table('project_demands')->update([
                'start_date' => DB::raw('date_from'),
                'end_date' => DB::raw('date_to'),
            ]);
            
            // SQLite doesn't support dropping foreign keys
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['role_id']);
            }
            $table->dropColumn(['role_id', 'required_count', 'date_from', 'date_to']);
        });
    }
};
