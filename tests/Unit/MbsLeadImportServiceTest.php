<?php

namespace Tests\Unit;

use App\Services\MbsLeadImportService;
use Tests\TestCase;

class MbsLeadImportServiceTest extends TestCase
{
    private function service(): MbsLeadImportService
    {
        return new MbsLeadImportService();
    }

    private function parse(string $csv): array
    {
        return $this->service()->parseOnly($csv);
    }

    public function test_detects_english_headers(): void
    {
        $result = $this->parse("Created time,Full name,Phone number,Email\n2024/07/01 09:00:00 UTC,Jan Kowalski,600100200,jan@example.com");

        $this->assertSame('Full name', $result['detected_headers']['name']);
        $this->assertSame('Phone number', $result['detected_headers']['phone']);
        $this->assertSame('Email', $result['detected_headers']['email']);
        $this->assertSame('Created time', $result['detected_headers']['created_at']);
        $this->assertSame('2024/07/01 09:00:00 UTC', $result['rows'][0]['created_at']);
    }

    public function test_detects_polish_headers(): void
    {
        $result = $this->parse("Data i godzina;Imię i nazwisko;Numer telefonu;Adres e-mail\n01.07.2024 09:00;Jan Kowalski;600100200;jan@example.com");

        $this->assertSame('Data i godzina', $result['detected_headers']['created_at']);
        $this->assertSame('01.07.2024 09:00', $result['rows'][0]['created_at']);
    }

    public function test_detects_alternative_date_headers(): void
    {
        foreach ([
            'Czas wypełnienia',
            'Data przesłania',
            'Data wypełnienia',
            'Czas przesłania',
        ] as $header) {
            $result = $this->parse("{$header},Full name,Phone number\n2024/07/01 09:00:00,Jan Kowalski,600100200");
            $this->assertSame(
                $header,
                $result['detected_headers']['created_at'],
                "Failed for header: {$header}",
            );
        }
    }

    public function test_null_created_at_when_no_date_column(): void
    {
        $result = $this->parse("Full name,Phone number\nJan Kowalski,600100200");

        $this->assertNull($result['detected_headers']['created_at']);
        $this->assertNull($result['rows'][0]['created_at']);
    }

    public function test_splits_full_name_into_first_and_last(): void
    {
        $result = $this->parse("Full name,Phone number\nAnna Nowak,699111222");

        $this->assertSame('Anna', $result['rows'][0]['first_name']);
        $this->assertSame('Nowak', $result['rows'][0]['last_name']);
    }

    public function test_skips_row_with_empty_phone(): void
    {
        $result = $this->parse("Full name,Phone number\nBrak Telefonu,");

        $this->assertNull($result['rows'][0]['phone']);
    }

    public function test_semicolon_delimiter_detected(): void
    {
        $result = $this->parse("Imię i nazwisko;Numer telefonu\nMaria Wiśniewska;501999888");

        $this->assertSame('Numer telefonu', $result['detected_headers']['phone']);
        $this->assertNotNull($result['rows'][0]['phone']);
    }

    public function test_separate_first_last_name_columns(): void
    {
        $result = $this->parse("First name,Last name,Phone number\nPiotr,Wiśniewski,604555666");

        $this->assertSame('Piotr', $result['rows'][0]['first_name']);
        $this->assertSame('Wiśniewski', $result['rows'][0]['last_name']);
    }

    public function test_polish_full_name_column_is_not_duplicated_as_first_and_last(): void
    {
        // Partial match of "imię" + "nazwisko" against "Imię i nazwisko" must not
        // put the same full name into both first_name and last_name.
        $result = $this->parse("Imię i nazwisko,Numer telefonu\nJanusz Pawikowski,600100200");

        $this->assertSame('Imię i nazwisko', $result['detected_headers']['name']);
        $this->assertSame('Janusz', $result['rows'][0]['first_name']);
        $this->assertSame('Pawikowski', $result['rows'][0]['last_name']);
    }

    public function test_detects_utworzono_header(): void
    {
        $result = $this->parse("Utworzono,Imię i nazwisko,Numer telefonu\n07/31/2026 5:54am,Jan Kowalski,600100200");

        $this->assertSame('Utworzono', $result['detected_headers']['created_at']);
        $this->assertSame('07/31/2026 5:54am', $result['rows'][0]['created_at']);
    }

    public function test_parses_us_am_pm_date_format(): void
    {
        $service = $this->service();
        $result  = $this->parse("Utworzono,Full name,Phone number\n07/31/2026 5:54am,Test User,600100200\n07/31/2026 11:30pm,Test Two,600100201");

        $this->assertSame('07/31/2026 5:54am', $result['rows'][0]['created_at']);
        $this->assertSame('07/31/2026 11:30pm', $result['rows'][1]['created_at']);
    }
}
