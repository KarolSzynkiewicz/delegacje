<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $locationIds = DB::table('warehouses')
            ->whereNotNull('location_id')
            ->pluck('location_id')
            ->unique();

        foreach ($locationIds as $locationId) {
            $exists = DB::table('location_purposes')
                ->where('location_id', $locationId)
                ->where('purpose', 'warehouse')
                ->exists();

            if (! $exists) {
                DB::table('location_purposes')->insert([
                    'location_id' => $locationId,
                    'purpose' => 'warehouse',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Schema::create('equipment_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->restrictOnDelete();
            $table->foreignId('equipment_variant_id')->constrained('equipment_variants')->restrictOnDelete();
            $table->string('type', 32);
            $table->unsignedInteger('quantity');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->uuid('batch_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'type']);
            $table->index('equipment_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_stock_movements');

        DB::table('location_purposes')->where('purpose', 'warehouse')->delete();
    }
};
