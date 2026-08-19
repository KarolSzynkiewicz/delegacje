<?php

namespace Tests\Unit;

use App\Enums\ProcedureSubjectType;
use App\Models\Vehicle;
use Tests\TestCase;

class ProcedureSubjectTypeTest extends TestCase
{
    public function test_labels_are_in_polish(): void
    {
        $this->assertSame('Samochód', ProcedureSubjectType::Vehicle->label());
        $this->assertSame('Zakwaterowanie', ProcedureSubjectType::Accommodation->label());
    }

    public function test_vehicle_label_includes_registration_and_model(): void
    {
        $vehicle = new Vehicle([
            'registration_number' => 'WA 12345',
            'brand' => 'Ford',
            'model' => 'Transit',
        ]);

        $this->assertSame(
            'WA 12345 Ford Transit',
            ProcedureSubjectType::Vehicle->labelFor($vehicle),
        );
    }

    public function test_employee_source_card_points_at_the_employee_page(): void
    {
        $employee = new \App\Models\Employee([
            'first_name' => 'Jan',
            'last_name' => 'Adamczyk',
        ]);
        $employee->id = 15;

        $card = ProcedureSubjectType::Employee->sourceCardFor($employee);

        $this->assertSame(route('employees.show', $employee), $card['url']);
        $this->assertSame('Pracownik: Jan Adamczyk', $card['label']);
        $this->assertSame('bi-person-badge', $card['icon']);
    }
}
