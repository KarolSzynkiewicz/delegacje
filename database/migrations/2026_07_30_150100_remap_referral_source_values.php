<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $map = [
        'social_media'       => 'meta_business_suite',
        'recommendation'     => 'job_portal_other',
        'job_portal'         => 'job_portal_other',
        // 'employee_referral' stays the same — no mapping needed
    ];

    public function up(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table('recruitment_leads')
                ->where('referral_source', $old)
                ->update(['referral_source' => $new]);
        }
    }

    public function down(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table('recruitment_leads')
                ->where('referral_source', $new)
                ->update(['referral_source' => $old]);
        }
    }
};
