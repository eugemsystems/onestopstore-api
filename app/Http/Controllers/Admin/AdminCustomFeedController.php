<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\CustomFeedDefinition;
use App\Services\CustomFeedGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminCustomFeedController extends BaseAdminController
{
    protected string $permissionPrefix = 'daily-feeds';

    /**
     * Parse a textarea of newline-separated identifiers into a clean,
     * deduplicated list — same tolerant convention used by the bulk
     * promotion / bulk price tools (trims, drops blank lines, tolerates a
     * trailing ",note" on a line by only taking the first comma segment).
     */
    private function parseIdentifierList(?string $raw): array
    {
        if (!$raw) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(function ($line) {
                return trim(explode(',', trim($line), 2)[0]);
            }, preg_split('/\r\n|\r|\n/', trim($raw)))
        )));
    }

    public function store(Request $request)
    {
        $this->checkPermission('custom-feed-create');

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'category_slugs' => ['nullable', 'string'],
            'skus' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'max:10'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'shard_count' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $categorySlugs = $this->parseIdentifierList($request->category_slugs);
        $skus = $this->parseIdentifierList($request->skus);

        if (empty($categorySlugs) && empty($skus)) {
            return response()->json([
                'success' => false,
                'message' => 'Enter at least one category slug or SKU.',
            ], 422);
        }

        // Warn (but don't block) on category slugs that don't exist, so a typo
        // doesn't silently produce an empty feed with no explanation.
        $unknownCategories = [];
        if (!empty($categorySlugs)) {
            $existingSlugs = Category::whereIn('slug', $categorySlugs)->pluck('slug')->toArray();
            $unknownCategories = array_values(array_diff($categorySlugs, $existingSlugs));
        }

        $slug = Str::slug($request->name) . '-' . Str::lower(Str::random(6));

        $feed = CustomFeedDefinition::create([
            'name' => $request->name,
            'slug' => $slug,
            'category_slugs' => $categorySlugs ?: null,
            'skus' => $skus ?: null,
            'currency' => $request->currency ?: 'USD',
            'min_price' => $request->filled('min_price') ? (float) $request->min_price : 0,
            'shard_count' => $request->filled('shard_count') ? (int) $request->shard_count : 1,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Custom feed link created. Click "Run Now" on it to generate the file.',
            'feed' => [
                'id' => $feed->id,
                'name' => $feed->name,
                'slug' => $feed->slug,
                'category_slugs' => $feed->category_slugs ?? [],
                'skus' => $feed->skus ?? [],
                'currency' => $feed->currency,
                'min_price' => $feed->min_price,
                'shard_count' => $feed->shard_count,
                'url' => route('custom-feeds.show', ['slug' => $feed->slug]),
                'created_at' => $feed->created_at->toDateTimeString(),
            ],
            'unknown_categories' => $unknownCategories,
        ], 201);
    }

    /**
     * Regenerate a single custom feed's static XML file on demand — the
     * only way these files ever get (re)written, matching the 4 fixed
     * region feeds' "Run Now" button but scoped to just this one feed.
     */
    public function runNow($id, CustomFeedGenerator $generator)
    {
        $this->checkPermission('custom-feed-run');

        $feed = CustomFeedDefinition::findOrFail($id);

        try {
            $run = $generator->generate($feed);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'run' => [
                'status' => $run->status,
                'records_written' => $run->records_written,
                'duration_seconds' => $run->duration_seconds,
                'finished_at' => optional($run->finished_at)->toDateTimeString(),
                'file_url' => $run->file_url,
            ],
        ]);
    }

    public function destroy($id, CustomFeedGenerator $generator)
    {
        $this->checkPermission('custom-feed-delete');

        $feed = CustomFeedDefinition::findOrFail($id);
        $generator->deleteFiles($feed);
        $feed->delete();

        return response()->json([
            'success' => true,
            'message' => 'Custom feed link deleted.',
        ]);
    }
}
