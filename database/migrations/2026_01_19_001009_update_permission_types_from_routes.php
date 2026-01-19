<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Services\RoutePermissionService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all permissions from routes
        $routePermissionService = new RoutePermissionService();
        $routePermissions = $routePermissionService->getAllPermissionsFromRoutes();
        
        // Create a map of permission name -> type
        $permissionTypeMap = $routePermissions->pluck('type', 'name')->toArray();
        
        // Update type for existing permissions based on routes
        foreach ($permissionTypeMap as $permissionName => $type) {
            DB::table('permissions')
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->update([
                    'type' => $type,
                    'updated_at' => now(),
                ]);
        }
        
        // For permissions that exist in database but not in routes,
        // try to infer type from permission name
        $allDbPermissions = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereNull('type')
            ->orWhere('type', '')
            ->get();
        
        foreach ($allDbPermissions as $permission) {
            $parts = explode('.', $permission->name);
            
            if (count($parts) >= 2) {
                $action = end($parts);
                
                // Infer type from action
                $inferredType = 'resource'; // default
                
                // View-only resources
                $viewResources = ['dashboard', 'profitability', 'weekly-overview'];
                if (in_array($parts[0], $viewResources) && $action === 'view' && count($parts) === 2) {
                    $inferredType = 'view';
                }
                // Action routes (3 parts, last is 'update')
                elseif (count($parts) === 3 && $action === 'update') {
                    $inferredType = 'action';
                }
                
                DB::table('permissions')
                    ->where('id', $permission->id)
                    ->update([
                        'type' => $inferredType,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all types to null (we don't want to lose data, but migration can be reversed)
        DB::table('permissions')
            ->where('guard_name', 'web')
            ->update([
                'type' => null,
                'updated_at' => now(),
            ]);
    }
};
