<?php

namespace App\Console\Commands;

use App\Services\RoutePermissionService;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class SyncPermissionsFromRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize permissions from routes to database';

    /**
     * Execute the console command.
     */
    public function handle(RoutePermissionService $routePermissionService)
    {
        $this->info('Synchronizing permissions from routes...');
        
        $routePermissions = $routePermissionService->getAllPermissionsFromRoutes();
        $created = 0;
        $updated = 0;
        
        $bar = $this->output->createProgressBar($routePermissions->count());
        $bar->start();
        
        foreach ($routePermissions as $perm) {
            $existing = Permission::where('name', $perm['name'])
                ->where('guard_name', 'web')
                ->first();
            
            if (!$existing) {
                Permission::create([
                    'name' => $perm['name'],
                    'guard_name' => 'web',
                    'type' => $perm['type']
                ]);
                $created++;
            } elseif ($existing->type !== $perm['type']) {
                $existing->update(['type' => $perm['type']]);
                $updated++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Synchronization complete!");
        $this->info("Created: {$created} permissions");
        $this->info("Updated: {$updated} permissions");
        $this->info("Total route permissions: {$routePermissions->count()}");
        
        return Command::SUCCESS;
    }
}
