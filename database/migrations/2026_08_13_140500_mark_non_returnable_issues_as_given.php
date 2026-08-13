<?php

use App\Models\EquipmentIssue;
use App\Models\EquipmentStock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $issues = DB::table('equipment_issues')
            ->join('equipment', 'equipment.id', '=', 'equipment_issues.equipment_id')
            ->where('equipment_issues.status', EquipmentIssue::STATUS_ISSUED)
            ->where('equipment.returnable', false)
            ->select('equipment_issues.id', 'equipment_issues.warehouse_id', 'equipment_issues.equipment_variant_id', 'equipment_issues.quantity_issued')
            ->get();

        foreach ($issues as $issue) {
            $stock = EquipmentStock::query()
                ->where('warehouse_id', $issue->warehouse_id)
                ->where('equipment_variant_id', $issue->equipment_variant_id)
                ->first();

            if ($stock) {
                $stock->quantity_in_stock = max(0, (int) $stock->quantity_in_stock - (int) $issue->quantity_issued);
                $stock->save();
            }

            DB::table('equipment_issues')
                ->where('id', $issue->id)
                ->update(['status' => EquipmentIssue::STATUS_GIVEN]);
        }
    }

    public function down(): void
    {
        // Status given is the canonical record of a permanent issue; do not revert.
    }
};
