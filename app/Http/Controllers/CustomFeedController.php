<?php

namespace App\Http\Controllers;

use App\Models\CustomFeedDefinition;
use Illuminate\Support\Facades\Storage;

class CustomFeedController extends Controller
{
    /**
     * Serve the Google Merchant XML feed for a custom category-slugs +
     * SKU-list link created on /admin/daily-feeds. Pure static-file
     * passthrough — same as /dailyfeeds/{path} — the file is only ever
     * (re)written by CustomFeedGenerator, triggered from the admin panel
     * (on creation, and via the per-feed "Run Now" button), never here.
     */
    public function show(string $slug)
    {
        $feed = CustomFeedDefinition::where('slug', $slug)->firstOrFail();
        $filePath = "custom-feeds/{$feed->slug}.xml";

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'This feed has not been generated yet. Click "Run Now" on it in the admin panel.');
        }

        return Storage::disk('public')->response($filePath, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Serve one shard of a feed that was too big for a single file
     * (shard_count > 1 on the feed). Same static passthrough as show().
     */
    public function showShard(string $slug, string $shard)
    {
        $feed = CustomFeedDefinition::where('slug', $slug)->firstOrFail();
        $shardCount = max(1, (int) ($feed->shard_count ?: 1));
        $shardNumber = (int) $shard;

        if ($shardCount <= 1 || $shardNumber < 1 || $shardNumber > $shardCount) {
            abort(404);
        }

        $filePath = "custom-feeds/{$feed->slug}-{$shardNumber}.xml";

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'This feed has not been generated yet. Click "Run Now" on it in the admin panel.');
        }

        return Storage::disk('public')->response($filePath, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
