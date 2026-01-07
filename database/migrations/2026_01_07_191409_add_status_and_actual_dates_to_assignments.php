<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Enums\AssignmentStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add status and actual dates to vehicle_assignments
        if (Schema::hasTable('vehicle_assignments')) {
            Schema::table('vehicle_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('vehicle_assignments', 'status')) {
                    $table->string('status')->default(AssignmentStatus::ACTIVE->value)->after('end_date');
                }
                if (!Schema::hasColumn('vehicle_assignments', 'actual_start_date')) {
                    $table->date('actual_start_date')->nullable()->after('status');
                }
                if (!Schema::hasColumn('vehicle_assignments', 'actual_end_date')) {
                    $table->date('actual_end_date')->nullable()->after('actual_start_date');
                }
            });

            // Set status to 'active' for existing active assignments
            DB::table('vehicle_assignments')
                ->where(function ($q) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
                })
                ->where('start_date', '<=', now())
                ->update(['status' => AssignmentStatus::ACTIVE->value]);

            // Set status to 'completed' for existing completed assignments
            DB::table('vehicle_assignments')
                ->whereNotNull('end_date')
                ->where('end_date', '<', now())
                ->update(['status' => AssignmentStatus::COMPLETED->value]);
        }

        // Add status and actual dates to accommodation_assignments
        if (Schema::hasTable('accommodation_assignments')) {
            Schema::table('accommodation_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('accommodation_assignments', 'status')) {
                    $table->string('status')->default(AssignmentStatus::ACTIVE->value)->after('end_date');
                }
                if (!Schema::hasColumn('accommodation_assignments', 'actual_start_date')) {
                    $table->date('actual_start_date')->nullable()->after('status');
                }
                if (!Schema::hasColumn('accommodation_assignments', 'actual_end_date')) {
                    $table->date('actual_end_date')->nullable()->after('actual_start_date');
                }
            });

            // Set status to 'active' for existing active assignments
            DB::table('accommodation_assignments')
                ->where(function ($q) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
                })
                ->where('start_date', '<=', now())
                ->update(['status' => AssignmentStatus::ACTIVE->value]);

            // Set status to 'completed' for existing completed assignments
            DB::table('accommodation_assignments')
                ->whereNotNull('end_date')
                ->where('end_date', '<', now())
                ->update(['status' => AssignmentStatus::COMPLETED->value]);
        }

        // Add actual dates to project_assignments (status already exists)
        if (Schema::hasTable('project_assignments')) {
            Schema::table('project_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('project_assignments', 'actual_start_date')) {
                    $table->date('actual_start_date')->nullable()->after('status');
                }
                if (!Schema::hasColumn('project_assignments', 'actual_end_date')) {
                    $table->date('actual_end_date')->nullable()->after('actual_start_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vehicle_assignments')) {
            Schema::table('vehicle_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('vehicle_assignments', 'actual_end_date')) {
                    $table->dropColumn('actual_end_date');
                }
                if (Schema::hasColumn('vehicle_assignments', 'actual_start_date')) {
                    $table->dropColumn('actual_start_date');
                }
                if (Schema::hasColumn('vehicle_assignments', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }

        if (Schema::hasTable('accommodation_assignments')) {
            Schema::table('accommodation_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('accommodation_assignments', 'actual_end_date')) {
                    $table->dropColumn('actual_end_date');
                }
                if (Schema::hasColumn('accommodation_assignments', 'actual_start_date')) {
                    $table->dropColumn('actual_start_date');
                }
                if (Schema::hasColumn('accommodation_assignments', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }

        if (Schema::hasTable('project_assignments')) {
            Schema::table('project_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('project_assignments', 'actual_end_date')) {
                    $table->dropColumn('actual_end_date');
                }
                if (Schema::hasColumn('project_assignments', 'actual_start_date')) {
                    $table->dropColumn('actual_start_date');
                }
            });
        }
    }
};
