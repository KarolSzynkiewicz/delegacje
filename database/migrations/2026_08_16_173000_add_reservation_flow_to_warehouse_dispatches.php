<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_dispatches', function (Blueprint $table) {
            $table->string('status', 32)->default('issued')->after('notes');
            $table->timestamp('issued_at')->nullable()->after('status');
            $table->foreignId('issued_by')->nullable()->after('issued_at')->constrained('users')->nullOnDelete();
        });

        DB::table('warehouse_dispatches')->update([
            'status' => 'issued',
            'issued_at' => DB::raw('created_at'),
            'issued_by' => DB::raw('created_by'),
        ]);

        $this->moveIssuedQuantitiesOffTheShelf();
    }

    public function down(): void
    {
        Schema::table('warehouse_dispatches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issued_by');
            $table->dropColumn(['status', 'issued_at']);
        });
    }

    /**
     * Stary model: zwracalne wydania/uszkodzenia/zgubienia zostawały w quantity_in_stock.
     * Teraz półka = fizycznie w magazynie, więc zdejmujemy te sztuki ze stanu.
     */
    private function moveIssuedQuantitiesOffTheShelf(): void
    {
        $groups = DB::table('equipment_issues')
            ->whereIn('status', ['issued', 'damaged', 'lost'])
            ->select('warehouse_id', 'equipment_variant_id', DB::raw('SUM(quantity_issued) as qty'))
            ->groupBy('warehouse_id', 'equipment_variant_id')
            ->get();

        foreach ($groups as $group) {
            $stock = DB::table('equipment_stocks')
                ->where('warehouse_id', $group->warehouse_id)
                ->where('equipment_variant_id', $group->equipment_variant_id)
                ->first();

            if (! $stock) {
                continue;
            }

            DB::table('equipment_stocks')
                ->where('id', $stock->id)
                ->update([
                    'quantity_in_stock' => max(0, (int) $stock->quantity_in_stock - (int) $group->qty),
                    'updated_at' => now(),
                ]);
        }
    }
};
