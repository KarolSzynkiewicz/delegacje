<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'client_contact_id')) {
                $table->foreignId('client_contact_id')
                    ->nullable()
                    ->after('client_id')
                    ->constrained('client_contacts')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('projects', 'arrangements')) {
                $table->text('arrangements')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'client_contact_id')) {
                $table->dropConstrainedForeignId('client_contact_id');
            }
            if (Schema::hasColumn('projects', 'arrangements')) {
                $table->dropColumn('arrangements');
            }
        });
    }
};
