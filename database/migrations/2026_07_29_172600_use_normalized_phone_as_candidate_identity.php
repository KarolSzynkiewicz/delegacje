<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $normalize = static function (?string $phone): ?string {
            $digits = preg_replace('/\D+/', '', (string) $phone);

            if ($digits === '') {
                return null;
            }

            if (str_starts_with($digits, '00')) {
                $digits = substr($digits, 2);
            }

            return strlen($digits) === 9 ? '48'.$digits : $digits;
        };

        $primaryByPhone = [];

        DB::table('recruitment_candidates')
            ->orderBy('id')
            ->get(['id', 'phone'])
            ->each(function ($candidate) use ($normalize, &$primaryByPhone) {
                $phone = $normalize($candidate->phone);

                if ($phone === null) {
                    DB::table('recruitment_candidates')->where('id', $candidate->id)->update(['phone' => null]);

                    return;
                }

                if (isset($primaryByPhone[$phone])) {
                    $primaryId = $primaryByPhone[$phone];

                    DB::table('recruitment_leads')->where('candidate_id', $candidate->id)->update(['candidate_id' => $primaryId]);
                    DB::table('recruitment_processes')->where('candidate_id', $candidate->id)->update(['candidate_id' => $primaryId]);
                    DB::table('recruitment_consents')->where('candidate_id', $candidate->id)->update(['candidate_id' => $primaryId]);
                    DB::table('recruitment_candidates')->where('id', $candidate->id)->delete();

                    return;
                }

                $primaryByPhone[$phone] = $candidate->id;
                DB::table('recruitment_candidates')->where('id', $candidate->id)->update(['phone' => $phone]);
            });

        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->dropUnique('recruitment_candidates_email_unique');
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_candidates', function (Blueprint $table) {
            $table->dropUnique('recruitment_candidates_phone_unique');
            $table->unique('email');
        });
    }
};
