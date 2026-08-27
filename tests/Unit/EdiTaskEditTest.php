<?php

namespace Tests\Unit;

use App\Services\Llm\TasksFilterMutateService;
use App\Support\EdiTaskEdit;
use Tests\TestCase;

class EdiTaskEditTest extends TestCase
{
    public function test_kind_distinguishes_add_change_and_remove(): void
    {
        $this->assertSame('add', EdiTaskEdit::kind(null, 'Transport'));
        $this->assertSame('change', EdiTaskEdit::kind('Ogólne', 'Transport'));
        $this->assertSame('remove', EdiTaskEdit::kind('test test xxx', null));
        $this->assertNull(EdiTaskEdit::kind('Transport', 'Transport'));
        $this->assertNull(EdiTaskEdit::kind(null, null));
    }

    public function test_intent_limits_editable_fields(): void
    {
        $this->assertSame(['category'], EdiTaskEdit::fieldsForIntent('mutate-category'));
        $this->assertFalse(EdiTaskEdit::allows('status', 'mutate-refine'));
        $this->assertFalse(EdiTaskEdit::allows('assigned_to', 'mutate-refine'));
        $this->assertTrue(EdiTaskEdit::allows('name', 'mutate-refine'));
        $this->assertSame(EdiTaskEdit::EDITABLE, EdiTaskEdit::fieldsForIntent('mutate-json'));
        $this->assertTrue(EdiTaskEdit::allows('description', 'mutate-json'));
        $this->assertSame([], EdiTaskEdit::fieldsForIntent('mutate-assign'));
    }

    public function test_diffs_drop_unknown_ids_and_non_editable_fields(): void
    {
        $service = app(TasksFilterMutateService::class);

        $diffs = $service->diffsFromChanges(
            [['id' => 5, 'name' => 'X', 'category' => null]],
            [
                ['id' => 5, 'field' => 'category', 'value' => 'Transport'],
                ['id' => 5, 'field' => 'status', 'value' => 'completed'],
                ['id' => 99, 'field' => 'category', 'value' => 'Nope'],
            ],
            ['category'],
        );

        $this->assertCount(1, $diffs);
        $this->assertSame('add', $diffs[0]['kind']);
        $this->assertSame('brak', $diffs[0]['from_label']);
        $this->assertSame('Transport', $diffs[0]['to_label']);
    }

    public function test_parse_imported_json_matches_source_id_without_llm(): void
    {
        $service = app(TasksFilterMutateService::class);
        $records = [[
            'id' => 10,
            'source_id' => 77,
            'name' => 'Alpha',
            'category' => null,
        ]];

        $diffs = $service->parseImportedJson(
            json_encode(['changes' => [
                ['id' => 77, 'field' => 'category', 'value' => 'Transport'],
                ['id' => 10, 'field' => 'status', 'value' => 'completed'],
            ]]),
            $records,
            EdiTaskEdit::EDITABLE,
        );

        $this->assertCount(1, $diffs);
        $this->assertSame(10, $diffs[0]['row_id']);
        $this->assertSame('category', $diffs[0]['field']);
        $this->assertSame('add', $diffs[0]['kind']);
    }

    public function test_parse_imported_tasks_diffs_against_live_records(): void
    {
        $service = app(TasksFilterMutateService::class);
        $records = [[
            'id' => 10,
            'source_id' => 77,
            'name' => 'Alpha',
            'category' => 'Backlog',
            'priority' => 3,
        ]];

        $diffs = $service->parseImportedJson(
            json_encode(['tasks' => [[
                'id' => 10,
                'name' => 'Alpha v2',
                'category' => 'Backlog',
                'priority' => 3,
                'status' => 'completed',
            ]]]),
            $records,
            EdiTaskEdit::EDITABLE,
        );

        $this->assertCount(1, $diffs);
        $this->assertSame('name', $diffs[0]['field']);
        $this->assertSame('change', $diffs[0]['kind']);
        $this->assertSame('Alpha v2', $diffs[0]['to']);
    }

    public function test_export_payload_includes_instruction_and_revised_changes(): void
    {
        $service = app(TasksFilterMutateService::class);
        $payload = $service->exportPayload(
            [['id' => 10, 'source_id' => 77, 'name' => 'Alpha', 'category' => null]],
            EdiTaskEdit::EDITABLE,
            [['row_id' => 10, 'field' => 'category', 'to' => 'Logistyka']],
            ['Kategoria: Backlog'],
        );

        $this->assertSame('edi-task-edit', $payload['format']);
        $this->assertStringContainsString('Odpowiedz TYLKO JSON', $payload['instruction']);
        $this->assertSame('Logistyka', $payload['changes'][0]['value']);
        $this->assertSame('Logistyka', $payload['tasks'][0]['category']);
    }
}
