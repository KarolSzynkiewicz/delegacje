<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_work_days', function (Blueprint $table) {
            $table->time('starts_at')->nullable()->after('work_date');
            $table->time('ends_at')->nullable()->after('starts_at');
        });

        // Istniejące wpisy z samymi godzinami → 09:00 + długość dnia.
        $rows = DB::table('office_work_days')
            ->whereNull('starts_at')
            ->where('hours', '>', 0)
            ->get(['id', 'hours']);

        foreach ($rows as $row) {
            $minutes = (int) round((float) $row->hours * 60);
            $end = now()->startOfDay()->addMinutes(9 * 60 + $minutes);

            DB::table('office_work_days')->where('id', $row->id)->update([
                'starts_at' => '09:00:00',
                'ends_at' => $end->format('H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('office_work_days', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
