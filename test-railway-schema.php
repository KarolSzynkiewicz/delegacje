<?php
/**
 * Test script to check employee_documents table structure on Railway
 * Run this on Railway Dashboard: php test-railway-schema.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Railway Database Schema Check ===" . PHP_EOL;
echo "Environment: " . app()->environment() . PHP_EOL;
echo "Database: " . config('database.default') . PHP_EOL;
echo PHP_EOL;

try {
    // Sprawdź strukturę tabeli employee_documents
    $columns = DB::select('DESCRIBE employee_documents');
    
    echo "Table: employee_documents" . PHP_EOL;
    echo str_repeat('-', 80) . PHP_EOL;
    printf("%-20s | %-15s | %-8s | %-10s | %-10s\n", 'Field', 'Type', 'Null', 'Key', 'Default');
    echo str_repeat('-', 80) . PHP_EOL;
    
    foreach ($columns as $col) {
        printf("%-20s | %-15s | %-8s | %-10s | %-10s\n", 
            $col->Field, 
            $col->Type, 
            $col->Null, 
            $col->Key ?? '', 
            $col->Default ?? 'NULL'
        );
    }
    echo str_repeat('-', 80) . PHP_EOL;
    echo PHP_EOL;
    
    // Sprawdź czy kolumna type jest nullable
    $typeColumn = collect($columns)->firstWhere('Field', 'type');
    if ($typeColumn) {
        echo "Column 'type' details:" . PHP_EOL;
        echo "  Type: " . $typeColumn->Type . PHP_EOL;
        echo "  Nullable: " . $typeColumn->Null . PHP_EOL;
        echo "  Default: " . ($typeColumn->Default ?? 'NULL') . PHP_EOL;
        echo PHP_EOL;
        
        if ($typeColumn->Null === 'NO') {
            echo "⚠️  WARNING: Column 'type' is NOT NULL - this will cause 500 errors!" . PHP_EOL;
            echo "   Run migration: php artisan migrate to fix this." . PHP_EOL;
        } else {
            echo "✅ Column 'type' is nullable - OK!" . PHP_EOL;
        }
    } else {
        echo "Column 'type' not found in table." . PHP_EOL;
    }
    echo PHP_EOL;
    
    // Sprawdź ostatnie migracje
    echo "Last 5 migrations:" . PHP_EOL;
    $migrations = DB::table('migrations')->orderBy('id', 'desc')->limit(5)->get();
    foreach ($migrations as $migration) {
        echo "  - " . $migration->migration . " (batch: " . $migration->batch . ")" . PHP_EOL;
    }
    echo PHP_EOL;
    
    // Test insert
    echo "Testing insert with type=null..." . PHP_EOL;
    $employee = App\Models\Employee::first();
    $document = App\Models\Document::first();
    
    if ($employee && $document) {
        try {
            $testDoc = $employee->employeeDocuments()->create([
                'document_id' => $document->id,
                'kind' => 'okresowy',
                'valid_from' => now(),
                'valid_to' => now()->addDays(30),
                'notes' => 'Test insert from script',
                'type' => null,
            ]);
            echo "✅ SUCCESS! Created EmployeeDocument ID: " . $testDoc->id . PHP_EOL;
            
            // Usuń testowy rekord
            $testDoc->delete();
            echo "Test record deleted." . PHP_EOL;
        } catch (\Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
            echo "This is the error causing 500 on Railway!" . PHP_EOL;
        }
    } else {
        echo "No employee or document found for testing." . PHP_EOL;
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
