<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class ApiCacheRefresher
{
    protected string $base;
    protected Client $http;

    public function __construct()
    {
        // Base host your Nginx serves (can override via .env)
        $this->base = rtrim(env('API_CACHE_REFRESH_BASE', 'https://api.raines.africa'), '/');

        $this->http = new Client([
            'timeout'         => 5.0,
            'connect_timeout' => 2.0,
            'http_errors'     => false,
            'verify'          => false, // if your internal curl to 127.0.0.1 needs skipping cert checks
        ]);
    }

    /**
     * Re-cache a list of absolute or relative URLs.
     * Use relative paths like /api/settings, /api/product?..., etc.
     *
     * @param  array<string>  $paths
     * @return void
     */
    public function recache(array $paths): void
    {
        $paths = array_values(array_unique(array_filter($paths)));
        if (!$paths) return;

        $promises = [];
        foreach ($paths as $p) {
            $url = str_starts_with($p, 'http') ? $p : $this->base.$p;

            $promises[] = $this->http->getAsync($url, [
                'headers' => [
                    // Nginx sees this and does: bypass lookup BUT STORE fresh
                    // (we already added: fastcgi_cache_bypass $http_x_recache;)
                    'X-Recache'        => '1',
                    'Accept'           => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Origin'           => 'https://raines.africa',
                ],
            ])->otherwise(function () {
                // swallow errors; refresh should never block writes
            });
        }

        Promise\Utils::settle($promises)->wait();
    }

    // Static convenience methods for common recache scenarios
    public static function refresh(array $urls): void
    {
        (new self())->recache($urls);
    }

    public static function refreshThemeOptions(): void
    {
        self::refresh([
            '/api/themeOptionsFront',
        ]);
    }

    public static function refreshSettings(): void
    {
        self::refresh([
            '/api/settingsFront',
        ]);
    }

    public static function refreshCategories(): void
    {
        self::refresh([
            '/api/categoryFront',
        ]);
    }

    public static function refreshCurrencies(): void
    {
        self::refresh([
            '/api/currencyFront',
        ]);
    }

    public static function refreshQnaForProduct(int $productId): void
    {
        if ($productId > 0) {
            self::refresh([
                "/api/question-and-answer?product_id={$productId}",
            ]);
        }
    }

    // Globals
    public static function refreshGlobals(): void
    {
        self::refresh([
            '/api/settings',
            '/api/themeOptions',
            '/api/currency',
            '/api/category?status=1',
        ]);
    }

    // Convenience helpers
    public static function refreshProductByData(int $id, ?string $slug, array $categorySlugs, ?string $oldSlug = null): void
    {
        $urls = [];

        // product pages
        if ($slug) {
            $urls[] = '/api/product/slug/'.rawurlencode($slug);
        }
        if ($oldSlug && $oldSlug !== $slug) {
            // make old slug miss/hit immediately (often becomes 404 cached briefly)
            $urls[] = '/api/product/slug/'.rawurlencode($oldSlug);
        }

        // Q&A for this product
        $urls[] = "/api/question-and-answer?product_id={$id}";

        // hot listing pages
        $urls[] = '/api/product?status=1&trending=1&page=1';
        $urls[] = '/api/product?status=1&page=1';

        // category listing first page(s)
        foreach (array_slice($categorySlugs, 0, 8) as $catSlug) {
            $urls[] = "/api/product?category={$catSlug}&page=1";
        }

        self::refresh($urls);
    }
}
