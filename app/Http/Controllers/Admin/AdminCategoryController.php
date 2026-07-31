<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminCategoryController extends BaseAdminController
{
    protected string $permissionPrefix = 'category';

    /**
     * Display a listing of parent categories only, ordered by sort_order
     */
    public function index()
    {
        $this->checkPermission('index');

        // Get only parent categories (parent_id = null), ordered by sort_order
        $categories = Category::whereNull('parent_id')
            ->withCount('children')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(50);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Reorder categories via drag-and-drop (AJAX)
     */
    public function reorder(Request $request)
    {
        $this->checkPermission('edit');

        $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|integer|exists:categories,id',
            'order.*.position' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->order as $item) {
                Category::withoutEvents(function () use ($item) {
                    Category::where('id', $item['id'])
                        ->whereNull('parent_id')
                        ->update(['sort_order' => $item['position']]);
                });
            }

            DB::commit();

            // Audit log — single entry summarising the reorder
            try {
                $ids = implode(', ', array_column($request->order, 'id'));
                ActivityLogger::make()->useLog('category')->event('updated')
                    ->log('Category sort order updated for IDs: ' . $ids);
            } catch (\Throwable) {}

            // Clear caches
            \Cache::forget('categories');
            \Cache::forget('categories_hierarchical_tree');

            // Bump version so API cache refreshes too
            $ver = (int) \Cache::get('categories_cache_version', 1);
            \Cache::put('categories_cache_version', $ver + 1, now()->addDays(365));

            return response()->json([
                'success' => true,
                'message' => 'Category order updated successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Category reorder failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder categories: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get category details for editing (AJAX)
     */
    public function edit($id)
    {
        $this->checkPermission('edit');

        // Get all categories from cache
        $allCategories = \Cache::rememberForever('categories', function () {
            return Category::all();
        });

        // Find category from cache
        $category = $allCategories->firstWhere('id', $id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], 404);
        }

        // Count children from cache
        $childrenCount = $allCategories->where('parent_id', $id)->count();

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'commission_rate' => $category->commission_rate,
                'status' => $category->status,
                'children_count' => $childrenCount,
            ]
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $id)
    {
        $this->checkPermission('edit');

        // Increase execution time for large category updates
        set_time_limit(120); // 2 minutes should be more than enough
        ini_set('memory_limit', '512M');

        // Only allow commission_rate and status to be updated
        // Name and slug are NOT editable for security and data consistency
        $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:0,1',
        ]);

        // Ensure name and slug are not being updated (security)
        if ($request->has('name') || $request->has('slug')) {
            return response()->json([
                'success' => false,
                'message' => 'Category name and slug cannot be modified.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $commissionRate = $request->commission_rate;
            $status = $request->status;
            $now = now();

            // Get all categories from cache (single fetch)
            $allCategories = \Cache::rememberForever('categories', function () {
                return Category::all();
            });

            // Find parent category from cache
            $category = $allCategories->firstWhere('id', $id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found.'
                ], 404);
            }

            // Only allow updating parent categories
            if ($category->parent_id !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only parent categories can be edited. Please edit the parent category instead.'
                ], 403);
            }

            // Get all children IDs from cache
            $childrenIds = $allCategories->where('parent_id', $id)->pluck('id')->toArray();
            $childrenCount = count($childrenIds);

            // Prepare IDs for bulk update (parent + all children)
            $allIdsToUpdate = array_merge([$id], $childrenIds);

            // Disable observers and do single bulk update for all categories at once
            Category::withoutEvents(function () use ($allIdsToUpdate, $commissionRate, $status, $now) {
                // Single bulk update query for all categories (parent + children)
                Category::whereIn('id', $allIdsToUpdate)
                    ->update([
                        'commission_rate' => $commissionRate,
                        'status' => $status,
                        'updated_at' => $now,
                    ]);
            });

            DB::commit();

            // Audit log — directly after withoutEvents since observers are silenced
            try {
                $oldCommission = $category->commission_rate;
                $oldStatus = $category->status;
                $changes = [];
                if ((string)$oldCommission !== (string)$commissionRate) $changes['commission_rate'] = "{$oldCommission} → {$commissionRate}";
                if ((string)$oldStatus !== (string)$status) $changes['status'] = "{$oldStatus} → {$status}";
                $changeStr = !empty($changes) ? ' (' . implode(', ', array_map(fn($k, $v) => "{$k}: {$v}", array_keys($changes), $changes)) . ')' : '';
                ActivityLogger::make()->useLog('category')->event('updated')->on($category)
                    ->withChanges(
                        ['commission_rate' => $oldCommission, 'status' => $oldStatus],
                        ['commission_rate' => $commissionRate, 'status' => $status]
                    )
                    ->log("Category '{$category->name}' (#{$category->id}) updated{$changeStr} — also applied to {$childrenCount} children");
            } catch (\Throwable) {}

            // Clear cache once after all updates complete
            \Cache::forget('categories');
            \Cache::forget('categories_hierarchical_tree');

            // Queue cache refresh in background (non-blocking)
            // Use proper Job class instead of closure to avoid serialization issues
            \App\Jobs\RefreshCategoriesCache::dispatch()->afterCommit();

            return response()->json([
                'success' => true,
                'message' => "Category updated successfully! Commission rate set to {$commissionRate}% for parent and {$childrenCount} children categories.",
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'commission_rate' => $commissionRate,
                    'status' => $status,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Category update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage()
            ], 500);
        }
    }
}

