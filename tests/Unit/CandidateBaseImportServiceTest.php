<?php

namespace Tests\Unit;

use App\Enums\RecruitmentContactOutcome;
use App\Enums\RecruitmentReferralSource;
use App\Enums\RecruitmentStatus;
use App\Models\Employee;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Models\User;
use App\Services\CandidateBaseImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateBaseImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): CandidateBaseImportService
    {
        return new CandidateBaseImportService();
    }

    /** Builds a single-row CSV string; $overrides fill in CandidateBaseImportService::EXPECTED_HEADERS. */
    private function csvRow(array $overrides = []): string
    {
        $defaults = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone' => '600100200',
            'email' => '',
            'city' => '',
            'has_driving_license_b' => '',
            'roles_raw' => '',
            'expected_rate_eur' => '',
            'available_from_raw' => '',
            'legacy_status' => '',
            'referral_source' => '',
            'referral_source_detail' => '',
            'lead_created_at' => '',
            'contact_date' => '',
            'notes' => '',
        ];

        $row = array_merge($defaults, $overrides);
        $headers = CandidateBaseImportService::EXPECTED_HEADERS;

        $line = fn (array $values) => implode(',', array_map(
            fn ($v) => '"'.str_replace('"', '""', (string) $v).'"',
            $values
        ));

        return $line($headers)."\n".$line(array_map(fn ($h) => $row[$h], $headers));
    }

    private function actingAsRecruiter(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // parseOnly — pure parsing, no DB
    // ─────────────────────────────────────────────────────────────────────────

    public function test_parse_only_normalizes_phone_and_rejects_missing_headers(): void
    {
        $result = $this->service()->parseOnly($this->csvRow(['phone' => '+48 600 100 200']));

        $this->assertSame('48600100200', $result['rows'][0]['phone']);
    }

    public function test_parse_only_throws_when_headers_missing(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service()->parseOnly("first_name,last_name\nJan,Kowalski");
    }

    public function test_parse_only_handles_embedded_newlines_inside_quoted_notes_field(): void
    {
        $csv = $this->csvRow([
            'phone' => '600100298',
            'notes' => "Opis doświadczenia: rozmowa\nRekomendacja rekrutera: tak",
        ]);

        $rows = $this->service()->parseOnly($csv)['rows'];

        $this->assertCount(1, $rows);
        $this->assertSame("Opis doświadczenia: rozmowa\nRekomendacja rekrutera: tak", $rows[0]['notes']);
    }

    public function test_parse_only_matches_known_role_tokens_and_flags_unmatched(): void
    {
        $result = $this->service()->parseOnly($this->csvRow(['roles_raw' => 'Piaskarz/Spawacz']));

        $row = $result['rows'][0];
        $this->assertSame(['Piaskarz'], $row['matched_role_names']);
        $this->assertSame(['spawacz'], $row['unmatched_specialties']);
        $this->assertStringContainsString('spawacz', $row['warnings'][0]);
    }

    public function test_parse_only_matches_space_separated_role_combo(): void
    {
        $result = $this->service()->parseOnly($this->csvRow(['roles_raw' => 'piaskarz szlifierz']));

        $matched = $result['rows'][0]['matched_role_names'];
        $this->assertContains('Piaskarz', $matched);
        $this->assertContains('Szlifierz', $matched);
    }

    public function test_parse_only_drops_out_of_range_expected_rate_and_warns(): void
    {
        // Real-world case: an Excel date (serial ~46092) accidentally typed into the
        // rate column would otherwise overflow the decimal(6,2) column and abort the import.
        $result = $this->service()->parseOnly($this->csvRow(['expected_rate_eur' => '46092']));

        $row = $result['rows'][0];
        $this->assertNull($row['expected_rate_eur']);
        $this->assertStringContainsString('poza sensownym zakresem', $row['warnings'][0]);
    }

    public function test_parse_only_keeps_plausible_expected_rate(): void
    {
        $result = $this->service()->parseOnly($this->csvRow(['expected_rate_eur' => '18.50']));

        $this->assertSame(18.5, $result['rows'][0]['expected_rate_eur']);
        $this->assertSame([], $result['rows'][0]['warnings']);
    }

    public function test_import_resolves_available_from_zaraz_to_today(): void
    {
        $this->actingAsRecruiter();

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100299',
            'available_from_raw' => 'od zaraz',
        ]))['rows'];
        $this->service()->import($rows);

        $candidate = RecruitmentCandidate::where('phone', '48600100299')->first();
        $this->assertTrue($candidate->available_from->isToday());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // import — status/rating/rejection mapping for each legacy_status
    // ─────────────────────────────────────────────────────────────────────────

    public function test_import_creates_candidate_lead_and_process_for_new_phone(): void
    {
        $this->actingAsRecruiter();

        $csv = $this->csvRow([
            'phone' => '600100200',
            'email' => 'jan@example.com',
            'city' => 'Gdańsk',
            'has_driving_license_b' => 'tak',
            'legacy_status' => 'Kandydat',
            'referral_source' => 'meta_business_suite',
            'lead_created_at' => '2026-01-10 09:00:00',
            'notes' => 'Pierwszy kontakt, zainteresowany',
        ]);

        $rows = $this->service()->parseOnly($csv)['rows'];
        $result = $this->service()->import($rows);

        $this->assertSame(['created' => 1, 'enriched' => 0, 'skipped' => 0, 'warnings' => []], $result);

        $candidate = RecruitmentCandidate::where('phone', '48600100200')->first();
        $this->assertNotNull($candidate);
        $this->assertSame('jan@example.com', $candidate->email);
        $this->assertSame('Gdańsk', $candidate->city);
        $this->assertTrue($candidate->has_driving_license_b);

        $process = $candidate->processes()->first();
        $this->assertSame(RecruitmentStatus::WTrakcieKontaktu, $process->status);
        $this->assertSame('Pierwszy kontakt, zainteresowany', $process->admin_notes);
        $this->assertSame('2026-01-10', $process->lead->created_at->toDateString());
        $this->assertSame(RecruitmentReferralSource::MetaBusinessSuite, $process->lead->referral_source);
        $this->assertSame(1, $process->contactAttempts()->count());
    }

    public function test_import_maps_could_not_reach_to_rejected_with_brak_odpowiedzi_outcome(): void
    {
        $this->actingAsRecruiter();

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100201',
            'legacy_status' => 'Nie udało się skontaktować',
        ]))['rows'];
        $this->service()->import($rows);

        $process = RecruitmentCandidate::where('phone', '48600100201')->first()->processes()->first();
        $this->assertSame(RecruitmentStatus::Odrzucony, $process->status);
        $this->assertSame('Nie udało się skontaktować (import historyczny)', $process->rejection_reason_note);
        $this->assertSame(
            RecruitmentContactOutcome::BrakOdpowiedzi,
            $process->contactAttempts()->first()->outcome
        );
    }

    public function test_import_maps_nie_zainteresowany_to_rejected(): void
    {
        $this->actingAsRecruiter();

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100202',
            'legacy_status' => 'Nie zainteresowany',
            'notes' => 'Powiedział że nie jest zainteresowany',
        ]))['rows'];
        $this->service()->import($rows);

        $process = RecruitmentCandidate::where('phone', '48600100202')->first()->processes()->first();
        $this->assertSame(RecruitmentStatus::Odrzucony, $process->status);
        $this->assertSame('Powiedział że nie jest zainteresowany', $process->rejection_reason_note);
    }

    public function test_import_maps_czarna_lista_to_rejected_and_blacklist_flag(): void
    {
        $this->actingAsRecruiter();

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100203',
            'legacy_status' => 'Czarna lista',
        ]))['rows'];
        $this->service()->import($rows);

        $candidate = RecruitmentCandidate::where('phone', '48600100203')->first();
        $this->assertTrue($candidate->isBlacklisted());
        $this->assertSame(RecruitmentStatus::Odrzucony, $candidate->processes()->first()->status);
    }

    public function test_import_maps_wartosciowy_kandydat_to_flag_and_active_status(): void
    {
        $this->actingAsRecruiter();

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100204',
            'legacy_status' => 'Wartościowy kandydat',
        ]))['rows'];
        $this->service()->import($rows);

        $candidate = RecruitmentCandidate::where('phone', '48600100204')->first();
        $this->assertTrue($candidate->isStarred());
        $this->assertSame(RecruitmentStatus::WTrakcieKontaktu, $candidate->processes()->first()->status);
    }

    public function test_import_maps_rezerwa_to_zaakceptowany_with_note(): void
    {
        $this->actingAsRecruiter();

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100205',
            'legacy_status' => 'Rezerwa',
        ]))['rows'];
        $this->service()->import($rows);

        $process = RecruitmentCandidate::where('phone', '48600100205')->first()->processes()->first();
        $this->assertSame(RecruitmentStatus::Zaakceptowany, $process->status);
        $this->assertStringContainsString('Rezerwa (import historyczny)', $process->admin_notes);
    }

    public function test_import_blank_legacy_status_defaults_to_nowy(): void
    {
        $this->actingAsRecruiter();

        $rows = $this->service()->parseOnly($this->csvRow(['phone' => '600100206']))['rows'];
        $this->service()->import($rows);

        $process = RecruitmentCandidate::where('phone', '48600100206')->first()->processes()->first();
        $this->assertSame(RecruitmentStatus::Nowy, $process->status);
    }

    public function test_import_links_aktualny_pracownik_to_matching_employee_by_phone(): void
    {
        $this->actingAsRecruiter();

        $employee = Employee::factory()->create(['phone' => '+48 600 100 207']);

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100207',
            'legacy_status' => 'Aktualny pracownik',
        ]))['rows'];
        $result = $this->service()->import($rows);

        $this->assertSame([], $result['warnings']);

        $candidate = RecruitmentCandidate::where('phone', '48600100207')->first();
        $this->assertSame($employee->id, $candidate->employee_id);
        $this->assertSame(RecruitmentStatus::Zatrudniony, $candidate->processes()->first()->status);
        $this->assertSame($employee->id, $candidate->processes()->first()->employee_id);
    }

    public function test_import_flags_aktualny_pracownik_without_phone_match_for_manual_review(): void
    {
        $this->actingAsRecruiter();

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100208',
            'legacy_status' => 'Aktualny pracownik',
        ]))['rows'];
        $result = $this->service()->import($rows);

        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('brak dopasowania po telefonie', $result['warnings'][0]);

        $candidate = RecruitmentCandidate::where('phone', '48600100208')->first();
        $this->assertNull($candidate->employee_id);
        $this->assertSame(RecruitmentStatus::WTrakcieKontaktu, $candidate->processes()->first()->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Enrichment — never overwrite existing non-null candidate data
    // ─────────────────────────────────────────────────────────────────────────

    public function test_import_enriches_existing_candidate_without_overwriting_known_fields(): void
    {
        $this->actingAsRecruiter();

        $existing = RecruitmentCandidate::create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone' => '600100209',
            'email' => 'original@example.com',
            'city' => null,
            'has_driving_license_b' => false,
        ]);

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100209',
            'email' => 'from-import@example.com',
            'city' => 'Szczecin',
            'has_driving_license_b' => 'tak',
        ]))['rows'];
        $result = $this->service()->import($rows);

        $this->assertSame(1, $result['enriched']);
        $this->assertSame(0, $result['created']);

        $existing->refresh();
        $this->assertSame('original@example.com', $existing->email); // untouched
        $this->assertSame('Szczecin', $existing->city); // filled in, was null
        $this->assertTrue($existing->has_driving_license_b); // upgraded false → true
    }

    public function test_import_assigns_matched_roles_to_candidate(): void
    {
        $this->actingAsRecruiter();

        $piaskarz = Role::create(['name' => 'Piaskarz']);
        $szlifierz = Role::create(['name' => 'Szlifierz']);

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100213',
            'roles_raw' => 'Piaskarz/Szlifierz/Spawacz',
        ]))['rows'];
        $this->service()->import($rows);

        $candidate = RecruitmentCandidate::where('phone', '48600100213')->first();
        $this->assertEqualsCanonicalizing(
            [$piaskarz->id, $szlifierz->id],
            $candidate->roles()->pluck('roles.id')->all()
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reuse-or-create process — the key anti-duplication behaviour
    // ─────────────────────────────────────────────────────────────────────────

    public function test_import_reuses_bare_process_already_created_by_daily_mbs_import_on_same_day(): void
    {
        $this->actingAsRecruiter();

        $candidate = RecruitmentCandidate::create([
            'first_name' => 'Anna', 'last_name' => 'Nowak', 'phone' => '600100210',
        ]);
        $lead = RecruitmentLead::create([
            'candidate_id' => $candidate->id,
            'referral_source' => RecruitmentReferralSource::MetaBusinessSuite,
        ]);
        $lead->created_at = '2026-02-05 08:00:00';
        $lead->save();
        $bareProcess = RecruitmentProcess::create([
            'lead_id' => $lead->id,
            'candidate_id' => $candidate->id,
            'status' => RecruitmentStatus::Nowy,
        ]);

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100210',
            'legacy_status' => 'Nie zainteresowany',
            'lead_created_at' => '2026-02-05 08:00:00',
            'notes' => 'Rozmawiał, nie jest zainteresowany',
        ]))['rows'];
        $result = $this->service()->import($rows);

        $this->assertSame(1, $result['enriched']);
        $this->assertSame(1, RecruitmentProcess::where('candidate_id', $candidate->id)->count());

        $bareProcess->refresh();
        $this->assertSame(RecruitmentStatus::Odrzucony, $bareProcess->status);
        $this->assertSame('Rozmawiał, nie jest zainteresowany', $bareProcess->admin_notes);
    }

    public function test_import_creates_new_process_when_no_matching_lead_date_exists(): void
    {
        $this->actingAsRecruiter();

        $candidate = RecruitmentCandidate::create([
            'first_name' => 'Anna', 'last_name' => 'Nowak', 'phone' => '600100211',
        ]);
        $oldLead = RecruitmentLead::create(['candidate_id' => $candidate->id]);
        $oldLead->created_at = '2025-01-01 08:00:00';
        $oldLead->save();
        RecruitmentProcess::create([
            'lead_id' => $oldLead->id, 'candidate_id' => $candidate->id, 'status' => RecruitmentStatus::Odrzucony,
        ]);

        $rows = $this->service()->parseOnly($this->csvRow([
            'phone' => '600100211',
            'legacy_status' => 'Kandydat',
            'lead_created_at' => '2026-03-01 08:00:00',
        ]))['rows'];
        $this->service()->import($rows);

        $this->assertSame(2, RecruitmentProcess::where('candidate_id', $candidate->id)->count());
    }

    public function test_reimporting_same_csv_is_idempotent(): void
    {
        $this->actingAsRecruiter();

        $csv = $this->csvRow([
            'phone' => '600100212',
            'legacy_status' => 'Nie udało się skontaktować',
            'lead_created_at' => '2026-04-01 08:00:00',
        ]);

        $this->service()->import($this->service()->parseOnly($csv)['rows']);
        $this->service()->import($this->service()->parseOnly($csv)['rows']);

        $candidate = RecruitmentCandidate::where('phone', '48600100212')->first();
        $this->assertSame(1, RecruitmentCandidate::where('phone', '48600100212')->count());
        $this->assertSame(1, RecruitmentProcess::where('candidate_id', $candidate->id)->count());
    }

    public function test_rows_without_phone_are_skipped(): void
    {
        $this->actingAsRecruiter();

        $rows = $this->service()->parseOnly($this->csvRow(['phone' => '']))['rows'];
        $result = $this->service()->import($rows);

        $this->assertSame(['created' => 0, 'enriched' => 0, 'skipped' => 1, 'warnings' => []], $result);
        $this->assertSame(0, RecruitmentCandidate::count());
    }
}
