<?php

namespace Tests\Unit;

use App\Enums\RecruitmentStatus;
use App\Models\ProcedureRun;
use App\Models\ProjectTask;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentProcess;
use App\Services\ProcedureSlotService;
use ReflectionMethod;
use Tests\TestCase;

class ProcedureSlotRecruitmentTaskContextTest extends TestCase
{
    public function test_recruitment_task_description_includes_candidate_context_and_card_url(): void
    {
        $candidate = new RecruitmentCandidate([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan.kowalski@example.com',
            'phone' => '48123456789',
        ]);

        $process = new RecruitmentProcess([
            'status' => RecruitmentStatus::Zaakceptowany,
        ]);
        $process->id = 5;
        $process->setRelation('candidate', $candidate);

        $method = new ReflectionMethod(ProcedureSlotService::class, 'recruitmentTaskDescription');
        $method->setAccessible(true);

        $description = $method->invoke(app(ProcedureSlotService::class), $process);

        $this->assertStringContainsString('Kandydat: Jan Kowalski', $description);
        $this->assertStringContainsString('Proces rekrutacji #5 — Weryfikacja', $description);
        $this->assertStringContainsString('Telefon: 48123456789', $description);
        $this->assertStringContainsString('E-mail: jan.kowalski@example.com', $description);
        $this->assertStringContainsString(
            route('recruitment-processes.show', 5),
            $description,
        );
    }

    public function test_task_context_from_recruitment_subject_sets_link_fields(): void
    {
        $candidate = new RecruitmentCandidate([
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna@example.com',
        ]);

        $process = new RecruitmentProcess([
            'status' => RecruitmentStatus::Onboarding,
            'assigned_recruiter_id' => 42,
        ]);
        $process->id = 9;
        $process->setRelation('candidate', $candidate);

        $method = new ReflectionMethod(ProcedureSlotService::class, 'taskContextFromSubject');
        $method->setAccessible(true);

        $context = $method->invoke(app(ProcedureSlotService::class), $process);

        $this->assertSame(9, $context['recruitment_process_id']);
        $this->assertSame('Rekrutacja', $context['category']);
        $this->assertSame(42, $context['assigned_to']);
        $this->assertStringContainsString('Kandydat: Anna Nowak', $context['description']);
    }

    public function test_project_task_recruitment_card_url_from_direct_fk(): void
    {
        $task = new ProjectTask(['recruitment_process_id' => 7]);

        $this->assertSame(7, $task->linkedRecruitmentProcessId());
        $this->assertSame(
            route('recruitment-processes.show', 7),
            $task->recruitmentCardUrl(),
        );
    }

    public function test_project_task_recruitment_card_url_from_procedure_run_subject(): void
    {
        $run = new ProcedureRun([
            'subject_type' => 'recruitment_process',
            'subject_id' => 12,
        ]);

        $task = new ProjectTask(['recruitment_process_id' => null]);
        $task->setRelation('procedureRun', $run);

        $this->assertSame(12, $task->linkedRecruitmentProcessId());
        $this->assertSame(
            route('recruitment-processes.show', 12),
            $task->recruitmentCardUrl(),
        );
    }
}
