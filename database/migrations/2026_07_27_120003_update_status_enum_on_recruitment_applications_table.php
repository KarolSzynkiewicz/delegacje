<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Widen the enum first so old and new values can coexist during backfill.
        DB::statement("ALTER TABLE recruitment_applications MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pending'");

        DB::table('recruitment_applications')->where('status', 'pending')->update(['status' => 'nowy']);
        DB::table('recruitment_applications')->where('status', 'reviewing')->update(['status' => 'w_trakcie_kontaktu']);
        DB::table('recruitment_applications')->where('status', 'accepted')->update(['status' => 'zaakceptowany']);
        DB::table('recruitment_applications')->where('status', 'rejected')->update(['status' => 'odrzucony']);
        DB::table('recruitment_applications')->where('status', 'converted')->update(['status' => 'zatrudniony']);

        DB::statement("ALTER TABLE recruitment_applications MODIFY status ENUM(
            'nowy',
            'w_trakcie_kontaktu',
            'zaakceptowany',
            'odrzucony',
            'onboarding',
            'zatrudniony',
            'byly_pracownik'
        ) NOT NULL DEFAULT 'nowy'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE recruitment_applications MODIFY status VARCHAR(30) NOT NULL DEFAULT 'nowy'");

        DB::table('recruitment_applications')->where('status', 'nowy')->update(['status' => 'pending']);
        DB::table('recruitment_applications')->where('status', 'w_trakcie_kontaktu')->update(['status' => 'reviewing']);
        DB::table('recruitment_applications')->where('status', 'zaakceptowany')->update(['status' => 'accepted']);
        DB::table('recruitment_applications')->where('status', 'odrzucony')->update(['status' => 'rejected']);
        DB::table('recruitment_applications')->whereIn('status', ['onboarding', 'zatrudniony', 'byly_pracownik'])->update(['status' => 'converted']);

        DB::statement("ALTER TABLE recruitment_applications MODIFY status ENUM(
            'pending',
            'reviewing',
            'accepted',
            'rejected',
            'converted'
        ) NOT NULL DEFAULT 'pending'");
    }
};
