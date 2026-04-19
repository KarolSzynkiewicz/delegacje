<?php

namespace Tests\Unit;

use App\Support\DepartureRoutePlan;
use App\Support\PublicDiskFileUrl;
use PHPUnit\Framework\TestCase;

class PublicDiskFileUrlTest extends TestCase
{
    public function test_returns_null_for_empty_path(): void
    {
        $this->assertNull(PublicDiskFileUrl::url(null));
        $this->assertNull(PublicDiskFileUrl::url(''));
        $this->assertNull(PublicDiskFileUrl::url('   '));
    }

    public function test_strips_public_prefix(): void
    {
        $url = PublicDiskFileUrl::url('public/transport_costs/x.pdf');
        $this->assertStringContainsString('storage/transport_costs/x.pdf', $url);
    }

    public function test_collect_public_leg_tickets_from_segments(): void
    {
        $segments = [
            [
                'mode' => 'own',
                'leg' => 'to_airport',
                'public_leg_ticket_costs_by_employee' => [
                    5 => [
                        'amount' => '120.50',
                        'currency' => 'pln',
                        'attachment_path' => 'transport_costs/a.pdf',
                    ],
                ],
            ],
        ];
        $rows = DepartureRoutePlan::collectPublicLegTicketRowsFromSegments($segments);
        $this->assertCount(1, $rows);
        $this->assertSame('Dojazd na lotnisko / dworzec', $rows[0]['leg_label']);
        $this->assertSame(5, $rows[0]['employee_id']);
        $this->assertSame(120.5, $rows[0]['amount']);
        $this->assertSame('PLN', $rows[0]['currency']);
        $this->assertSame('transport_costs/a.pdf', $rows[0]['attachment_path']);
    }
}
