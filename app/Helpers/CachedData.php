<?php

use App\Models\Category;
use App\Models\Currency;
use App\Models\OrderStatus;
use App\Models\Setting;
use App\Models\ThemeOption;
use Illuminate\Support\Facades\Cache;


if (!function_exists('getCachedSettings')) {
    function getCachedSettings()
    {
        return Cache::rememberForever('setting', function () {
            return Setting::latest('created_at')->first();
        });
    }
}

if (!function_exists('getSettings')) {
    function getSettings()
    {
        return getCachedSettings()->values;
    }
}

if (!function_exists('productFeedCategoriesTreeRoot')) {
    function productFeedCategoriesTreeRoot()
    {
        return Cache::remember('product_feed_categories_tree_root', now()->addHours(6), function () {
            return Category::where('status', 1)
                ->whereNull('parent_id')
                ->with('subcategories')
                ->orderBy('name')
                ->get();
        });
    }
}

if (!function_exists('productFeedCurrenciesAll')) {
    function productFeedCurrenciesAll()
    {
        return Cache::remember('product_feed_currencies_all', now()->addHours(6), function () {
            return Currency::all();
        });
    }
}



if (!function_exists('reCacheSettings')) {
    function reCacheSettings()
    {
        Cache::forget('setting');
        return getCachedSettings();
    }
}

if (!function_exists('reCacheAttributes')) {
    function reCacheAttributes()
    {
        // Don't cache all 2M+ attributes - just clear and let lazy loading handle it
        Cache::forget('attributes');
        Cache::forget('attributes_all');
        Cache::forget('attributes_with_values');
        return true;
    }
}

if (!function_exists('getCachedLaybySettings')) {
    function getCachedLaybySettings()
    {
        return Cache::rememberForever('layby_settings', function () {
            return \App\Models\LaybySetting::pluck('value', 'key')->toArray();
        });
    }
}

if (!function_exists('reCacheLaybySettings')) {
    function reCacheLaybySettings()
    {
        Cache::forget('layby_settings');
        return getCachedLaybySettings();
    }
}

if (!function_exists('getLaybySetting')) {
    function getLaybySetting(string $key, $default = null)
    {
        $settings = getCachedLaybySettings();
        return $settings[$key] ?? $default;
    }
}

if (!function_exists('getCachedThemeOptions')) {
    function getCachedThemeOptions()
    {
        return Cache::rememberForever('theme-options', function () {
            return ThemeOption::latest('created_at')->first();
        });

    }
}

if(!function_exists('reGenerateThemeOptions')){
    function reGenerateThemeOptions(){
        Cache::forget('theme-options');
        return getCachedThemeOptions();
    }
}

if (!function_exists('getCachedCategories')) {
    function getCachedCategories($categories=null)
    {
        if(is_null($categories)){
            return Cache::rememberForever('categories', function () {
                return Category::whereNull('parent_id')->with('subcategories', 'parent')->orderBy('sort_order', 'asc')->paginate(30);
            });
        }

        return Cache::rememberForever('categories', function () use($categories) {
            return $categories;
        });

    }
}


//reCacheOrderStatuses
if (!function_exists('cacheOrderStatuses')) {
    function cacheOrderStatuses()
    {
        return Cache::rememberForever('order-statuses', function () {
            return OrderStatus::all();
        });
    }
}

if (!function_exists('reCacheOrderStatuses')) {
    function reCacheOrderStatuses()
    {
        Cache::forget('order-statuses');
        return cacheOrderStatuses();
    }
}

if (!function_exists('getCachedCurrencies')) {
    function getCachedCurrencies()
    {
        return Cache::rememberForever('currencies', function () {
            return Currency::all();
        });
    }
}

if (!function_exists('regenerateCachedCurrencies')) {
    function regenerateCachedCurrencies()
    {
        Cache::forget('currencies');
        return getCachedCurrencies();
    }
}

// ============================================
// ROLES & PERMISSIONS CACHING
// ============================================

if (!function_exists('getCachedRoles')) {
    function getCachedRoles()
    {
        return Cache::rememberForever('all_roles', function () {
            return \Spatie\Permission\Models\Role::with('permissions')->get();
        });
    }
}

if (!function_exists('regenerateCachedRoles')) {
    function regenerateCachedRoles()
    {
        Cache::forget('all_roles');
        Cache::forget('role_permissions_map');
        return getCachedRoles();
    }
}

if (!function_exists('getCachedPermissions')) {
    function getCachedPermissions()
    {
        return Cache::rememberForever('all_permissions', function () {
            return \Spatie\Permission\Models\Permission::all();
        });
    }
}

if (!function_exists('regenerateCachedPermissions')) {
    function regenerateCachedPermissions()
    {
        Cache::forget('all_permissions');
        Cache::forget('role_permissions_map');
        return getCachedPermissions();
    }
}

if (!function_exists('getCachedRolePermissionsMap')) {
    function getCachedRolePermissionsMap()
    {
        return Cache::rememberForever('role_permissions_map', function () {
            $roles = \Spatie\Permission\Models\Role::with('permissions')->get();
            $map = [];

            foreach ($roles as $role) {
                $map[$role->id] = [
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray()
                ];
            }

            return $map;
        });
    }
}

if (!function_exists('regenerateCachedRolePermissionsMap')) {
    function regenerateCachedRolePermissionsMap()
    {
        Cache::forget('role_permissions_map');
        return getCachedRolePermissionsMap();
    }
}

if (!function_exists('getCachedUserPermissions')) {
    function getCachedUserPermissions($userId)
    {
        // Get current permissions cache version
        $version = Cache::get('permissions_cache_version', 1);

        return Cache::remember("user_{$userId}_permissions_v{$version}", now()->addHours(24), function () use ($userId) {
            try {
                // Use query builder to avoid potential model issues
                $user = \App\Models\User::select(['id', 'name', 'email'])
                    ->with([
                        'roles:id,name',
                        'roles.permissions:id,name',
                        'permissions:id,name'
                    ])
                    ->find($userId);

                if (!$user) {
                    return [];
                }

                // Get permissions from roles
                $rolePermissions = collect();
                if ($user->roles) {
                    $rolePermissions = $user->roles->flatMap(function ($role) {
                        return $role->permissions ?? collect();
                    });
                }

                // Get direct permissions
                $directPermissions = $user->permissions ?? collect();

                // Merge and get unique permissions
                $allPermissions = $rolePermissions->merge($directPermissions)->unique('id');

                return [
                    'permissions' => $allPermissions->pluck('name')->toArray(),
                    'roles' => $user->roles ? $user->roles->pluck('name')->toArray() : [],
                    'is_admin' => $user->roles ? $user->roles->contains('name', 'admin') : false,
                    'is_vendor' => $user->roles ? $user->roles->contains('name', 'vendor') : false,
                ];
            } catch (\Throwable $e) {
                // Log error but don't break the app
                \Log::error("Failed to cache user permissions for user {$userId}: " . $e->getMessage());

                // Return empty permissions on error
                return [
                    'permissions' => [],
                    'roles' => [],
                    'is_admin' => false,
                    'is_vendor' => false,
                ];
            }
        });
    }
}

if (!function_exists('regenerateCachedUserPermissions')) {
    function regenerateCachedUserPermissions($userId)
    {
        Cache::forget("user_{$userId}_permissions");
        return getCachedUserPermissions($userId);
    }
}

if (!function_exists('clearAllUserPermissionsCache')) {
    function clearAllUserPermissionsCache()
    {
        // Instead of clearing each user's cache individually (slow with many users),
        // we increment a version number. All user permission caches include this version
        // in their key, so incrementing it instantly invalidates all of them without
        // having to loop through thousands of users.

        try {
            $currentVersion = Cache::get('permissions_cache_version', 1);
            $newVersion = $currentVersion + 1;

            // Store the new version (forever cache since it's just a counter)
            Cache::forever('permissions_cache_version', $newVersion);
            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to clear all user permissions cache: ' . $e->getMessage());
            return false;
        }
    }
}

// ============================================
// PERMISSION CHECKING HELPERS
// ============================================

if (!function_exists('userCan')) {
    /**
     * Check if user has permission (from cache)
     * Can be used in blade: @if(userCan('product.create', $userId))
     * Can be used in controller: if (userCan('product.create', auth()->id()))
     */
    function userCan($permission, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return false;
        }

        $cachedPermissions = getCachedUserPermissions($userId);

        // Check if user is admin (has all permissions)
        if ($cachedPermissions['is_admin'] ?? false) {
            return true;
        }

        return in_array($permission, $cachedPermissions['permissions'] ?? []);
    }
}

if (!function_exists('userHasRole')) {
    /**
     * Check if user has role (from cache)
     * Can be used in blade: @if(userHasRole('admin', $userId))
     * Can be used in controller: if (userHasRole('admin', auth()->id()))
     */
    function userHasRole($role, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return false;
        }

        $cachedPermissions = getCachedUserPermissions($userId);

        return in_array($role, $cachedPermissions['roles'] ?? []);
    }
}

if (!function_exists('userHasAnyRole')) {
    /**
     * Check if user has any of the given roles (from cache)
     * Can be used: userHasAnyRole(['admin', 'vendor'], $userId)
     */
    function userHasAnyRole(array $roles, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return false;
        }

        $cachedPermissions = getCachedUserPermissions($userId);
        $userRoles = $cachedPermissions['roles'] ?? [];

        return count(array_intersect($roles, $userRoles)) > 0;
    }
}

if (!function_exists('userIsAdmin')) {
    /**
     * Check if user is admin (from cache)
     */
    function userIsAdmin($userId = null)
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return false;
        }

        $cachedPermissions = getCachedUserPermissions($userId);

        return $cachedPermissions['is_admin'] ?? false;
    }
}

if (!function_exists('userIsVendor')) {
    /**
     * Check if user is vendor (from cache)
     */
    function userIsVendor($userId = null)
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return false;
        }

        $cachedPermissions = getCachedUserPermissions($userId);

        return $cachedPermissions['is_vendor'] ?? false;
    }
}

// ============================================
// HIERARCHICAL CATEGORIES CACHING
// ============================================

if (!function_exists('getCachedCategoriesHierarchical')) {
    function getCachedCategoriesHierarchical()
    {
        return Cache::remember('categories_hierarchical_tree', now()->addHours(24), function () {
            try {
                $categories = Category::where('status', 1)
                    ->select(['id', 'name', 'parent_id', 'slug'])
                    ->orderBy('parent_id')
                    ->orderBy('name')
                    ->get();

                if ($categories->isEmpty()) {
                    return [];
                }

                return buildCategoryTree($categories);
            } catch (\Throwable $e) {
                \Log::error("Failed to build category tree: " . $e->getMessage());
                return [];
            }
        });
    }
}

if (!function_exists('regenerateCachedCategoriesHierarchical')) {
    function regenerateCachedCategoriesHierarchical()
    {
        Cache::forget('categories_hierarchical_tree');
        return getCachedCategoriesHierarchical();
    }
}

if (!function_exists('buildCategoryTree')) {
    function buildCategoryTree($categories, $parentId = null, $prefix = '', &$result = null, $depth = 0)
    {
        // Prevent infinite recursion - max 10 levels deep
        if ($depth > 10) {
            \Log::warning("Category tree depth exceeded 10 levels - stopping recursion");
            return $result ?? [];
        }

        if ($result === null) {
            $result = [];
        }

        try {
            foreach ($categories as $category) {
                if ($category->parent_id == $parentId) {
                    $path = $prefix ? $prefix . ' > ' . $category->name : $category->name;

                    $result[] = [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'path' => $path,
                        'level' => substr_count($path, '>'),
                    ];

                    // Recursive call with depth tracking
                    buildCategoryTree($categories, $category->id, $path, $result, $depth + 1);
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Error in buildCategoryTree at depth {$depth}: " . $e->getMessage());
        }

        return $result;
    }
}

// ============================================
// ACTIVE CURRENCIES LIST CACHING
// ============================================

if (!function_exists('getCachedActiveCurrencies')) {
    function getCachedActiveCurrencies()
    {
        $raw = Cache::remember('active_currencies_list', now()->addHours(24), function () {
            return Currency::where('status', 1)
                ->orderBy('code')
                ->get(['id', 'code', 'symbol', 'exchange_rate']);
        });

        // Normalise: if the cached value deserialized as plain arrays (stale cache),
        // convert each item to stdClass so Blade can safely use $curr->code etc.
        if (is_array($raw) || ($raw instanceof \Traversable && !($raw instanceof \Illuminate\Database\Eloquent\Collection))) {
            return collect($raw)->map(function ($item) {
                if (is_array($item)) {
                    // Ensure exchange_rate exists in array
                    if (!isset($item['exchange_rate'])) {
                        $item['exchange_rate'] = 1.0;
                    }
                    return (object) $item;
                }
                // If it's already an object, ensure exchange_rate property exists
                if (is_object($item) && !property_exists($item, 'exchange_rate')) {
                    $item->exchange_rate = 1.0;
                }
                return $item;
            });
        }

        // Eloquent Collection – also normalise any array items just in case
        return collect($raw)->map(function ($item) {
            if (is_array($item)) {
                // Ensure exchange_rate exists in array
                if (!isset($item['exchange_rate'])) {
                    $item['exchange_rate'] = 1.0;
                }
                return (object) $item;
            }
            // If it's already an object, ensure exchange_rate property exists
            if (is_object($item) && !property_exists($item, 'exchange_rate')) {
                $item->exchange_rate = 1.0;
            }
            return $item;
        });
    }
}

if (!function_exists('regenerateCachedActiveCurrencies')) {
    function regenerateCachedActiveCurrencies()
    {
        Cache::forget('active_currencies_list');
        return getCachedActiveCurrencies();
    }
}


// ============================================
// COUNTRIES & STATES CACHING
// ============================================

if (!function_exists('getCachedCountries')) {
    function getCachedCountries()
    {
        return Cache::remember('all_countries', now()->addHours(24), function () {
            return \App\Models\Country::orderBy('name')->get(['id', 'name', 'iso_3166_2', 'iso_3166_3', 'calling_code']);
        });
    }
}

if (!function_exists('regenerateCachedCountries')) {
    function regenerateCachedCountries()
    {
        Cache::forget('all_countries');
        return getCachedCountries();
    }
}

if (!function_exists('getCachedStates')) {
    function getCachedStates()
    {
        return Cache::remember('all_states', now()->addHours(24), function () {
            return \App\Models\State::orderBy('name')->get(['id', 'name', 'country_id']);
        });
    }
}

if (!function_exists('getCachedStatesByCountry')) {
    function getCachedStatesByCountry($countryId)
    {
        return Cache::remember("states_country_{$countryId}", now()->addHours(24), function () use ($countryId) {
            return \App\Models\State::where('country_id', $countryId)
                ->orderBy('name')
                ->get(['id', 'name', 'country_id']);
        });
    }
}

if (!function_exists('regenerateCachedStates')) {
    function regenerateCachedStates()
    {
        Cache::forget('all_states');
        // Clear all country-specific state caches
        $countries = getCachedCountries();
        foreach ($countries as $country) {
            Cache::forget("states_country_{$country->id}");
        }
        return getCachedStates();
    }
}

// ============================================
// PRODUCT EDIT PAGE CACHING (CATEGORIES, TAGS, BRANDS)
// ============================================

if (!function_exists('getCachedCategoriesForProductEdit')) {
    /**
     * Get active categories for product edit page with proper eager loading
     * This loads only parent categories with their direct subcategories (2 levels deep max)
     * to prevent massive N+1 queries while still supporting the nested UI
     */
    function getCachedCategoriesForProductEdit()
    {
        return Cache::remember('categories_nested_optimized_v3', now()->addHours(24), function () {
            // Load only root categories with 2 levels of subcategories
            // This is much more efficient than loading the entire tree
            return Category::whereNull('parent_id')
                ->where('status', 1)
                ->with([
                    'subcategories' => function($query) {
                        $query->where('status', 1)
                              ->select('id', 'name', 'slug', 'status', 'parent_id')
                              ->orderBy('name');
                    },
                    'subcategories.subcategories' => function($query) {
                        $query->where('status', 1)
                              ->select('id', 'name', 'slug', 'status', 'parent_id')
                              ->orderBy('name');
                    }
                ])
                ->select('id', 'name', 'slug', 'status', 'parent_id')
                ->orderBy('name')
                ->get();
        });
    }
}

if (!function_exists('regenerateCachedCategoriesForProductEdit')) {
    function regenerateCachedCategoriesForProductEdit()
    {
        Cache::forget('categories_nested_optimized_v3');
        Cache::forget('categories_flat_active_v2'); // Clear old cache key
        Cache::forget('categories_with_subcategories_active'); // Clear old cache key
        return getCachedCategoriesForProductEdit();
    }
}

if (!function_exists('getCachedActiveTags')) {
    /**
     * Get active tags for product edit page
     */
    function getCachedActiveTags()
    {
        return Cache::remember('active_tags', now()->addHours(24), function () {
            return \App\Models\Tag::where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'status']);
        });
    }
}

if (!function_exists('regenerateCachedActiveTags')) {
    function regenerateCachedActiveTags()
    {
        Cache::forget('active_tags');
        return getCachedActiveTags();
    }
}

if (!function_exists('getCachedActiveBrands')) {
    /**
     * Get active brands for product edit page
     */
    function getCachedActiveBrands()
    {
        return Cache::remember('active_brands', now()->addHours(24), function () {
            return \App\Models\Brand::where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'status']);
        });
    }
}

if (!function_exists('regenerateCachedActiveBrands')) {
    function regenerateCachedActiveBrands()
    {
        Cache::forget('active_brands');
        return getCachedActiveBrands();
    }
}

if (!function_exists('getCachedActiveTaxes')) {
    /**
     * Get active taxes for product edit page
     */
    function getCachedActiveTaxes()
    {
        return Cache::remember('active_taxes', now()->addHours(24), function () {
            return \App\Models\Tax::where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'rate', 'status']);
        });
    }
}

if (!function_exists('regenerateCachedActiveTaxes')) {
    function regenerateCachedActiveTaxes()
    {
        Cache::forget('active_taxes');
        return getCachedActiveTaxes();
    }
}


