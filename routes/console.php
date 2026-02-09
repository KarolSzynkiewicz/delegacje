<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Debug command for Railway
Artisan::command('debug:employee-document {employee_id} {document_id}', function ($employeeId, $documentId) {
    $this->info('=== Testing EmployeeDocument Creation ===');
    $this->info('Employee ID: ' . $employeeId);
    $this->info('Document ID: ' . $documentId);
    
    $employee = \App\Models\Employee::find($employeeId);
    if (!$employee) {
        $this->error('Employee not found!');
        $this->info('Available employees:');
        foreach (\App\Models\Employee::all() as $emp) {
            $this->line('  - ID ' . $emp->id . ': ' . $emp->full_name);
        }
        return;
    }
    
    $document = \App\Models\Document::find($documentId);
    if (!$document) {
        $this->error('Document not found!');
        $this->info('Available documents:');
        foreach (\App\Models\Document::all() as $doc) {
            $this->line('  - ID ' . $doc->id . ': ' . $doc->name);
        }
        return;
    }
    
    $this->info('Employee: ' . $employee->full_name);
    $this->info('Document: ' . $document->name);
    
    // Test validation
    $validator = \Validator::make([
        'employee_id' => $employeeId,
        'document_id' => $documentId,
        'valid_from' => now()->format('Y-m-d'),
        'valid_to' => now()->addDays(30)->format('Y-m-d'),
    ], [
        'employee_id' => ['required', 'exists:employees,id'],
        'document_id' => ['required', 'exists:documents,id'],
        'valid_from' => ['required', 'date'],
        'valid_to' => ['nullable', 'date'],
    ]);
    
    if ($validator->fails()) {
        $this->error('Validation failed:');
        foreach ($validator->errors()->all() as $error) {
            $this->error('  - ' . $error);
        }
        return;
    }
    
    $this->info('Validation passed!');
    
    // Try to create
    try {
        $doc = $employee->employeeDocuments()->create([
            'document_id' => $documentId,
            'kind' => 'okresowy',
            'valid_from' => now(),
            'valid_to' => now()->addDays(30),
            'notes' => 'Test from CLI',
            'type' => null,
        ]);
        
        $this->info('SUCCESS! Created EmployeeDocument ID: ' . $doc->id);
        $doc->delete();
        $this->info('Test record deleted.');
    } catch (\Exception $e) {
        $this->error('ERROR: ' . $e->getMessage());
        $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
    }
})->purpose('Debug employee document creation');
