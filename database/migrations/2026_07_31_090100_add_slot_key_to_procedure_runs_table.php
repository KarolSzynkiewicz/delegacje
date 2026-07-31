<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedure_runs', function (Blueprint $table) {
            $table->string('slot_key')->nullable()->after('subject_id');
            $table->index(['slot_key', 'subject_type', 'subject_id']);
        });

        // Generated column that is non-null only while a run is in_progress and tied
        // to a slot, so a unique index on it enforces "one in-progress run per
        // (slot, subject)" at the database level. Multiple NULLs are allowed in a
        // MySQL unique index, so runs without a slot, and finished/abandoned runs,
        // never collide.
        Schema::table('procedure_runs', function (Blueprint $table) {
            $table->string('slot_lock_key', 300)->nullable()->storedAs(
                "IF(status = 'in_progress' AND slot_key IS NOT NULL, CONCAT(slot_key, ':', subject_type, ':', subject_id), NULL)"
            )->after('slot_key');
        });

        Schema::table('procedure_runs', function (Blueprint $table) {
            $table->unique('slot_lock_key');
        });
    }

    public function down(): void
    {
        Schema::table('procedure_runs', function (Blueprint $table) {
            $table->dropUnique(['slot_lock_key']);
            $table->dropColumn('slot_lock_key');
            $table->dropIndex(['slot_key', 'subject_type', 'subject_id']);
            $table->dropColumn('slot_key');
        });
    }
};
