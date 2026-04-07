<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Utwórz tabelę najmu
        Schema::create('accommodation_leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('wynajmowany');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Migracja istniejących danych: przenieś dane leasingu z accommodations
        DB::table('accommodations')
            ->where('type', 'wynajmowany')
            ->whereNotNull('lease_start_date')
            ->orderBy('id')
            ->each(function ($acc) {
                DB::table('accommodation_leases')->insert([
                    'accommodation_id' => $acc->id,
                    'type' => 'wynajmowany',
                    'start_date' => $acc->lease_start_date,
                    'end_date' => $acc->lease_end_date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        // 3. Usuń stare kolumny najmu z accommodations
        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropColumn(['type', 'lease_start_date', 'lease_end_date']);
        });
    }

    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->string('type')->default('wynajmowany')->after('description');
            $table->date('lease_start_date')->nullable()->after('type');
            $table->date('lease_end_date')->nullable()->after('lease_start_date');
        });

        // Przywróć dane z najnowszego lease per accommodation
        DB::table('accommodation_leases')
            ->orderBy('accommodation_id')
            ->orderByDesc('start_date')
            ->get()
            ->unique('accommodation_id')
            ->each(function ($lease) {
                DB::table('accommodations')
                    ->where('id', $lease->accommodation_id)
                    ->update([
                        'type' => $lease->type,
                        'lease_start_date' => $lease->start_date,
                        'lease_end_date' => $lease->end_date,
                    ]);
            });

        // Dla tych bez leases – ustaw 'własny'
        DB::table('accommodations')
            ->whereNull('type')
            ->update(['type' => 'własny']);

        Schema::dropIfExists('accommodation_leases');
    }
};
