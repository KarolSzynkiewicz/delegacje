<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->string('value')->nullable();
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('min_quantity')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('equipment_id');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->string('variant_label')->nullable()->after('category');
        });

        $now = now();
        $items = DB::table('equipment')->orderBy('id')->get();
        foreach ($items as $item) {
            DB::table('equipment_variants')->insert([
                'equipment_id' => $item->id,
                'value' => null,
                'quantity_in_stock' => $item->quantity_in_stock ?? 0,
                'min_quantity' => $item->min_quantity ?? 0,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->foreignId('equipment_variant_id')
                ->nullable()
                ->after('equipment_id')
                ->constrained('equipment_variants')
                ->restrictOnDelete();
            $table->uuid('batch_id')->nullable()->after('notes')->index();
        });

        $variants = DB::table('equipment_variants')->pluck('id', 'equipment_id');
        foreach (DB::table('equipment_issues')->orderBy('id')->get() as $issue) {
            $variantId = $variants[$issue->equipment_id] ?? null;
            if ($variantId) {
                DB::table('equipment_issues')->where('id', $issue->id)->update([
                    'equipment_variant_id' => $variantId,
                ]);
            }
        }

        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->unsignedBigInteger('equipment_variant_id')->nullable(false)->change();
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['quantity_in_stock', 'min_quantity']);
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('min_quantity')->default(0);
        });

        foreach (DB::table('equipment')->orderBy('id')->get() as $item) {
            $totals = DB::table('equipment_variants')
                ->where('equipment_id', $item->id)
                ->selectRaw('COALESCE(SUM(quantity_in_stock), 0) as stock, COALESCE(SUM(min_quantity), 0) as min_qty')
                ->first();

            DB::table('equipment')->where('id', $item->id)->update([
                'quantity_in_stock' => $totals->stock ?? 0,
                'min_quantity' => $totals->min_qty ?? 0,
            ]);
        }

        Schema::table('equipment_issues', function (Blueprint $table) {
            $table->dropForeign(['equipment_variant_id']);
            $table->dropColumn(['equipment_variant_id', 'batch_id']);
        });

        Schema::dropIfExists('equipment_variants');

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn('variant_label');
        });
    }
};
