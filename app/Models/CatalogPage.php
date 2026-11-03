<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * An uploaded HTML file published at a fixed URL: {frontend}/catalog/{slug}.
 * Uploading a new file for the same page replaces the old one; the URL never changes.
 */
class CatalogPage extends Model
{
    protected $fillable = [
        'slug', 'title', 'file_path', 'original_name', 'size', 'version', 'is_active', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'size' => 'integer',
        'version' => 'integer',
        'hits_total' => 'integer',
        'bot_hits_total' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    /**
     * Scheme + host (+ port) of the public site. FRONTEND_URL is used elsewhere with a path
     * already on it (e.g. https://onestopstore.co.zw/en), so only the origin is taken here —
     * otherwise the link could come out as /en/en/catalog/….
     */
    public static function frontendOrigin(): string
    {
        $url = (string) config('app.frontend_url');
        $parts = parse_url($url);

        if (!empty($parts['scheme']) && !empty($parts['host'])) {
            return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        }

        return rtrim($url, '/');
    }

    /** The link to share — served by the storefront, which reads the file from this API. */
    public function publicUrl(): string
    {
        return self::frontendOrigin() . '/catalog/' . $this->slug;
    }

    public function fileExists(): bool
    {
        return $this->file_path && Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Crawlers, link-preview fetchers (WhatsApp, Facebook, Slack...), headless browsers and
     * monitors also open pages. They are counted separately so "clicks" means people.
     */
    public static function isBotUserAgent(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return true;
        }

        return (bool) preg_match(
            '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|discord|slack|skype|embedly|headless|lighthouse|pingdom|uptime|monitor|curl|wget|python-requests|axios|go-http|java\//i',
            $userAgent
        );
    }

    /**
     * Record one view. Counters are bumped in SQL (not read-modify-write) so concurrent
     * visitors never lose a count. Crawlers/link-preview bots go in their own counter.
     */
    public function recordHit(bool $isBot): void
    {
        $column = $isBot ? 'bot_hits_total' : 'hits_total';
        $dailyColumn = $isBot ? 'bot_hits' : 'hits';

        // Query builder, not Eloquent: a click must not touch updated_at, which means
        // "the file was changed" (shown in the admin and used for the file's cache headers).
        $counters = [$column => DB::raw("$column + 1")];
        if (!$isBot) {
            $counters['last_hit_at'] = now();
        }
        DB::table($this->getTable())->where($this->getKeyName(), $this->getKey())->update($counters);

        DB::table('catalog_page_daily_hits')->upsert(
            [[
                'catalog_page_id' => $this->getKey(),
                'date' => now()->toDateString(),
                'hits' => $isBot ? 0 : 1,
                'bot_hits' => $isBot ? 1 : 0,
            ]],
            ['catalog_page_id', 'date'],
            [$dailyColumn => DB::raw("catalog_page_daily_hits.$dailyColumn + 1")]
        );
    }
}
