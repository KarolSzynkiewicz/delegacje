<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    public function __construct(
        protected RoutePermissionService $routePermissionService
    ) {
    }

    /**
     * Pobiera przefiltrowane menu na podstawie uprawnień.
     * Cache'uje wynik per user na 1h.
     */
    public function getFilteredMenu(): array
    {
        $user = Auth::user();
        $userId = $user?->id ?? 0;
        
        return Cache::remember(
            "menu_user_{$userId}",
            now()->addHour(),
            function () use ($user) {
                $menuConfig = config('menu', []);
                $menuItems = config('menu_items', []);
                
                return $this->processMenuConfig($menuConfig, $menuItems, $user);
            }
        );
    }

    /**
     * Czyści cache menu dla wszystkich użytkowników.
     * Wywołaj po zmianie uprawnień lub ról.
     * 
     * Note: To clear all menu cache, we'd need to iterate through all users.
     * For simplicity, we clear all cache. In production, consider using
     * a cache tag system (Redis) or iterate through users.
     */
    public function clearMenuCache(): void
    {
        // For now, clear all cache. In production with Redis, you could use tags:
        // Cache::tags(['menu'])->flush();
        Cache::flush();
    }

    /**
     * Czyści cache menu dla konkretnego użytkownika.
     */
    public function clearMenuCacheForUser(int $userId): void
    {
        Cache::forget("menu_user_{$userId}");
    }

    /**
     * Przetwarza konfigurację menu i filtruje na podstawie uprawnień
     */
    protected function processMenuConfig(array $config, array $itemsMap, $user): array
    {
        $processed = [];

        foreach ($config as $item) {
            // String → link
            if (is_string($item)) {
                $itemData = $itemsMap[$item] ?? null;
                if (!$itemData) {
                    continue; // Skip jeśli nie ma mapowania
                }

                // Generate permission and routePattern from route
                $itemData = $this->enrichMenuItem($itemData);
                
                if ($this->hasAccess($itemData, $user)) {
                    $processed[] = [
                        'type' => 'link',
                        ...$itemData,
                    ];
                }
                continue;
            }

            // Tablica asocjacyjna → dropdown
            if (is_array($item) && isset($item['label']) && isset($item['items'])) {
                $dropdownItems = [];
                
                foreach ($item['items'] as $itemKey) {
                    if (!is_string($itemKey)) {
                        continue;
                    }
                    
                    $itemData = $itemsMap[$itemKey] ?? null;
                    if (!$itemData) {
                        continue;
                    }

                    // Generate permission and routePattern from route
                    $itemData = $this->enrichMenuItem($itemData);
                    
                    if ($this->hasAccess($itemData, $user)) {
                        $dropdownItems[] = [
                            'type' => 'item',
                            ...$itemData,
                        ];
                    }
                }

                // Renderuj dropdown tylko jeśli ma widoczne dzieci
                if (!empty($dropdownItems)) {
                    // Oblicz routePatterns dla dropdownu (suma wszystkich dzieci)
                    $routePatterns = [];
                    foreach ($dropdownItems as $dropdownItem) {
                        $patterns = $dropdownItem['routePattern'] ?? [];
                        if (is_string($patterns)) {
                            $routePatterns[] = $patterns;
                        } elseif (is_array($patterns)) {
                            $routePatterns = array_merge($routePatterns, $patterns);
                        }
                    }
                    $routePatterns = array_unique($routePatterns);

                    $processed[] = [
                        'type' => 'dropdown',
                        'label' => $item['label'],
                        'icon' => $item['icon'] ?? null,
                        'routePatterns' => $routePatterns,
                        'items' => $dropdownItems,
                    ];
                }
            }
        }

        return $processed;
    }

    /**
     * Wzbogaca item menu o permission i routePattern generowane z route.
     */
    protected function enrichMenuItem(array $itemData): array
    {
        $route = $itemData['route'] ?? null;
        
        if (!$route) {
            return $itemData;
        }
        
        // Generate permission from route using RoutePermissionService
        $permission = $this->routePermissionService->getPermissionForRoute($route);
        if ($permission) {
            $itemData['permission'] = $permission;
        }
        
        // Generate routePattern from route (remove last part, add .*)
        $routePattern = $this->generateRoutePattern($route);
        if ($routePattern) {
            $itemData['routePattern'] = $routePattern;
        }
        
        return $itemData;
    }

    /**
     * Generuje routePattern z route name.
     * Usuwa ostatnią część (action) i dodaje .*
     * 
     * Examples:
     * - time-logs.index -> time-logs.*
     * - projects.assignments.create -> projects.assignments.*
     * - dashboard -> dashboard.*
     */
    protected function generateRoutePattern(string $route): string
    {
        $parts = explode('.', $route);
        
        // If route has no dots, just add .*
        if (count($parts) === 1) {
            return "{$route}.*";
        }
        
        // Remove last part (action) and add .*
        array_pop($parts);
        $base = implode('.', $parts);
        
        return "{$base}.*";
    }

    /**
     * Sprawdza czy użytkownik ma dostęp do elementu
     */
    protected function hasAccess(array $itemData, $user): bool
    {
        // Jeśli nie ma ustawionego permission, zawsze dostępne
        if (!isset($itemData['permission'])) {
            return true;
        }

        // Jeśli użytkownik nie jest zalogowany
        if (!$user) {
            return false;
        }

        // Sprawdź uprawnienie
        return $user->hasPermission($itemData['permission']);
    }
}
