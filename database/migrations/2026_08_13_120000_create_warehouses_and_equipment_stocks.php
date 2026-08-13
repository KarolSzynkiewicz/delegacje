<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')
                ->nullable()
                ->unique()
                ->constrained('locations')
                ->restrictOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('is_default');
        });

        $now = now();
        $baseLocation = DB::table('locations')->where('is_base', true)->orderBy('id')->first()
            ?? DB::table('locations')->orderBy('id')->first();

        $defaultWarehouseId = DB::table('warehouses')->insertGetId([
            'location_id' => $baseLocation->id ?? null,
            'name' => $baseLocation->name ?? 'Siedziba',
            'is_default' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::create('equipment_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('equipment_variant_id')->constrained('equipment_variants')->cascadeOnDelete();
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('min_quantity')->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'equipment_variant_id']);
            $table->index('equipment_variant_id');
        });

        $variantRows = DB::table('equipment_variants')->orderBy('id')->get();
        foreach ($variantRows as $variant) {
            DB::table('equipment_stocks')->insert([
                'warehouse_id' => $defaultWarehouseId,
                'equipment_variant_id' => $variant->id,
                'quantity_in_stock' => $variant->quantity_in_stock ?? 0,
                'min_quantity' => $variant->min_quantity ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('equipment', function (Blueprint $table) {
            $table->boolean('issuable')->default(true)->after('currency');
        });

        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('equipment_variant_id')
                ->constrained('warehouses')
                ->restrictOnDelete();
        });

        DB::table('equipment_issues')->update(['warehouse_id' => $defaultWarehouseId]);

        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->nullable(false)->change();
        });

        Schema::table('equipment_variants', function (Blueprint $table) {
            $table->dropColumn(['quantity_in_stock', 'min_quantity']);
        });
    }

    public function down(): void
    {
        Schema::table('equipment_variants', function (Blueprint $table) {
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('min_quantity')->default(0);
        });

        $defaultWarehouseId = DB::table('warehouses')->where('is_default', true)->value('id')
            ?? DB::table('warehouses')->min('id');

        if ($defaultWarehouseId) {
            foreach (DB::table('equipment_variants')->orderBy('id')->get() as $variant) {
                $stock = DB::table('equipment_stocks')
                    ->where('warehouse_id', $defaultWarehouseId)
                    ->where('equipment_variant_id', $variant->id)
                    ->first();

                DB::table('equipment_variants')->where('id', $variant->id)->update([
                    'quantity_in_stock' => $stock->quantity_in_stock ?? 0,
                    'min_quantity' => $stock->min_quantity ?? 0,
                ]);
            }
        }

        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn('issuable');
        });

        Schema::dropIfExists('equipment_stocks');
        Schema::dropIfExists('warehouses');
    }
};
