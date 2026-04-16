<?php

use App\Enums\LogisticsEventType;
use App\Models\LogisticsEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uzupełnia related_departure_id dla starych transferów utworzonych z wyjazdu (notatka „wyjazdu #ID”).
     * Tylko WHERE related_departure_id IS NULL; weryfikacja, że docelowy wyjazd istnieje.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('logistics_events', 'related_departure_id')) {
            return;
        }

        LogisticsEvent::query()
            ->where('type', LogisticsEventType::TRANSFER)
            ->whereNull('related_departure_id')
            ->whereNotNull('notes')
            ->where('notes', 'like', '%wyjazdu #%')
            ->orderBy('id')
            ->chunkById(200, function ($transfers): void {
                foreach ($transfers as $transfer) {
                    if (! preg_match('/wyjazdu\s*#(\d+)/u', (string) $transfer->notes, $m)) {
                        continue;
                    }
                    $departureId = (int) $m[1];
                    if ($departureId <= 0) {
                        continue;
                    }
                    $exists = LogisticsEvent::query()
                        ->whereKey($departureId)
                        ->where('type', LogisticsEventType::DEPARTURE)
                        ->exists();
                    if (! $exists) {
                        continue;
                    }
                    $transfer->forceFill(['related_departure_id' => $departureId])->saveQuietly();
                }
            });
    }

    /**
     * Nie cofamy automatycznie — nie ma pewnego rozróżnienia ręcznych poprawek od backfillu.
     */
    public function down(): void
    {
        //
    }
};
