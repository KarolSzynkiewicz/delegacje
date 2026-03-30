<?php

use App\Enums\LocationPurposeType;
use App\Models\Location;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Location::query()->where('is_base', true)->cursor() as $location) {
            $location->addPurposes([LocationPurposeType::BASE]);
        }
    }

    public function down(): void
    {
        // Nie cofamy — dane mogły być już edytowane ręcznie
    }
};
