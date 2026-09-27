<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('vehicles')->where('retirement_reason', 'reserve')->update(['retirement_reason' => 'other']);
        DB::table('vehicle_lifecycle_events')->where('reason', 'reserve')->update(['reason' => 'other']);
    }

    public function down(): void
    {
        DB::table('vehicles')->where('retirement_reason', 'other')->update(['retirement_reason' => 'reserve']);
        DB::table('vehicle_lifecycle_events')->where('reason', 'other')->update(['reason' => 'reserve']);
    }
};
