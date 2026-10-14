<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['auditable_type', 'auditable_id', 'id'], 'audit_logs_type_id_pk_index');
        });

        Schema::table('work_items', function (Blueprint $table) {
            $table->index(['status', 'type', 'created_at'], 'work_items_status_type_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_type_id_pk_index');
        });

        Schema::table('work_items', function (Blueprint $table) {
            $table->dropIndex('work_items_status_type_created_index');
        });
    }
};
