<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Best-effort match of the legacy free-text `desired_role` against existing
     * employee roles, so old applications get a starting point in the new
     * multi-select roles relation. The original text column is left untouched
     * as a fallback for anything that doesn't match.
     *
     * Uses the query builder (not Eloquent models) on purpose: the recruitment
     * models were later split/renamed and this migration must still run cleanly
     * on a fresh install regardless of what the current model classes look like.
     */
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('recruitment_applications')) {
            return;
        }

        $roles = DB::table('roles')->get();

        DB::table('recruitment_applications')
            ->whereNotNull('desired_role')
            ->where('desired_role', '!=', '')
            ->get(['id', 'desired_role'])
            ->each(function ($application) use ($roles) {
                $needle = mb_strtolower(trim($application->desired_role));

                $match = $roles->first(function ($role) use ($needle) {
                    $name = mb_strtolower($role->name);

                    return $needle === $name
                        || str_contains($needle, $name)
                        || str_contains($name, $needle);
                });

                if ($match) {
                    DB::table('recruitment_application_role')->insertOrIgnore([
                        'recruitment_application_id' => $application->id,
                        'role_id' => $match->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Non-destructive backfill; nothing to reverse.
    }
};
