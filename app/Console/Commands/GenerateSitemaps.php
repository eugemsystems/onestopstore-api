<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Pre-generates all sitemap XML files as static files.
 * Google fetches these instantly instead of waiting for slow DB queries.
 *
 * Files are written to: storage/app/public/sitemaps/
 * Served via: {APP_URL}/storage/sitemaps/sitemap-products-0.xml
 *
 * Schedule: runs daily at 5 AM (after merchant feed at 4 AM)
 */
class GenerateSitemaps extends Command
{
    protected $signature = 'sitemaps:generate
                            {--products-per-file=30000 : Products per sitemap file}
                            {--dry-run : Count products without generating files}';

    protected $description = 'Pre-generate all sitemap XML files as static files for instant serving to Google';

    private string $baseUrl;
    private string $zmBaseUrl;
    private string $dir = 'sitemaps';
    private string $prefix = 'v5-';
    // Bumping the prefix (v4 -> v5) forces a clean break from any stale files
    // generated before the status+is_approved+deleted_at visibility fix above.
    // Any file in the sitemaps dir NOT matching the current prefix is purged
    // on each run (see purgeStaleSitemapFiles()) — files here have accumulated
    // across at least 3 different naming schemes over time (no prefix at all,
    // "v4-", ...) and nothing was ever cleaning the old ones up, so old/deleted
    // products kept being servable indefinitely via /api/sitemaps/{file}.

    public function handle(): void
    {
        // Normalise: page content URLs always need the /en locale prefix regardless
        // of how FRONTEND_URL is configured on the server (with or without /en).
        $rawFrontend = rtrim(env('FRONTEND_URL', 'https://raines.africa'), '/');
        $frontendRoot = preg_replace('#/en$#', '', $rawFrontend);
        $this->baseUrl = $frontendRoot . '/en';
        // Zambia market variant — same products, ZMW pricing baked in at render time
        // (see src/app/zm/product/[productSlug]/page.js on the frontend).
        $this->zmBaseUrl = $frontendRoot . '/zm';

        $perFile = (int) $this->option('products-per-file');
        $dryRun = $this->option('dry-run');

        // Safety guard: refuse to generate sitemaps with localhost/dev URLs
        if (str_contains($this->baseUrl, 'localhost') || str_contains($this->baseUrl, '127.0.0.1')) {
            $this->error("FRONTEND_URL is set to a local address: {$this->baseUrl}");
            $this->error('Refusing to generate sitemaps — this would poison production with localhost URLs.');
            $this->error('Set FRONTEND_URL=https://raines.africa/en in your .env and retry.');
            return;
        }

        // Ensure directory exists
        if (!Storage::disk('public')->exists($this->dir)) {
            Storage::disk('public')->makeDirectory($this->dir);
        }

        $this->info("Generating sitemaps with base URL: {$this->baseUrl}");

        // ── 0. Purge stale files from any previous naming scheme ─
        // These may reference products that have since been disabled/deleted
        // (or, before the visibility fix above, never should have been listed
        // at all) — delete them rather than leaving them servable indefinitely.
        if (!$dryRun) {
            $this->purgeStaleSitemapFiles();
        }

        // ── 1. Static pages sitemap ──────────────────────────────
        $this->generateStaticSitemap($dryRun);

        // ── 2. Categories sitemap ────────────────────────────────
        $this->generateCategoriesSitemap($dryRun);

        // ── 3. Blogs sitemap ─────────────────────────────────────
        $this->generateBlogsSitemap($dryRun);

        // ── 4. Product sitemaps (chunked) ────────────────────────
        // status + is_approved + deleted_at must all match what
        // ProductRepository::getProductBySlug() requires to serve the page —
        // otherwise the sitemap links to products that 404 when clicked.
        $totalProducts = DB::table('products')
            ->where('status', 1)
            ->where('is_approved', 1)
            ->whereNull('deleted_at')
            ->count();

        // ── 4a. Featured products sitemaps ───────────────────────
        $featuredCount = DB::table('products')
            ->where('status', 1)->where('is_approved', 1)->whereNull('deleted_at')
            ->where('is_featured', 1)
            ->count();
        $featuredFiles = (int) ceil($featuredCount / $perFile);
        $this->info("Featured: {$featuredCount} → {$featuredFiles} sitemap files");

        // ── 4b. Sale products sitemaps ───────────────────────────
        $saleCount = DB::table('products')
            ->where('status', 1)->where('is_approved', 1)->whereNull('deleted_at')
            ->where('is_sale_enable', 1)
            ->count();
        $saleFiles = (int) ceil($saleCount / $perFile);
        $this->info("Sale: {$saleCount} → {$saleFiles} sitemap files");

        $totalFiles = (int) ceil($totalProducts / $perFile);
        $this->info("All products: {$totalProducts} → {$totalFiles} sitemap files");

        if ($dryRun) {
            $total = $totalFiles + $featuredFiles + $saleFiles + 3;
            $this->info("[DRY RUN] Would generate {$total} files. Exiting.");
            return;
        }

        // Generate featured sitemaps
        for ($i = 0; $i < $featuredFiles; $i++) {
            $offset = $i * $perFile;
            $this->generateProductSitemap($i, $offset, $perFile, 'featured', 'is_featured', 0.9);
            $this->line("  sitemap-products-featured-{$i}.xml ({$perFile} products, offset {$offset})");
        }

        // Generate sale sitemaps
        for ($i = 0; $i < $saleFiles; $i++) {
            $offset = $i * $perFile;
            $this->generateProductSitemap($i, $offset, $perFile, 'sale', 'is_sale_enable', 0.8);
            $this->line("  sitemap-products-sale-{$i}.xml ({$perFile} products, offset {$offset})");
        }

        // Generate all products sitemaps
        for ($i = 0; $i < $totalFiles; $i++) {
            $offset = $i * $perFile;
            $this->generateProductSitemap($i, $offset, $perFile);
            $this->line("  sitemap-products-{$i}.xml ({$perFile} products, offset {$offset})");
        }

        // ── 5. Sitemap index ─────────────────────────────────────
        $this->generateSitemapIndex($totalFiles, $featuredFiles, $saleFiles);

        $generatedFiles = 4 + ($featuredFiles + $saleFiles + $totalFiles) * 2;
        $this->newLine();
        $this->info("Done. Generated {$generatedFiles} files (en + zm) in storage/app/{$this->dir}/");
        Log::info('sitemaps:generate completed', ['product_files' => $totalFiles, 'total_products' => $totalProducts]);
    }

    /**
     * Delete any file in the sitemaps directory that doesn't match the
     * current prefix — regardless of what naming scheme produced it — so
     * stale sitemaps (possibly listing since-disabled/deleted products)
     * don't stay servable via /api/sitemaps/{file} indefinitely.
     */
    private function purgeStaleSitemapFiles(): void
    {
        $files = Storage::disk('public')->files($this->dir);
        $stale = array_filter($files, fn ($f) => !str_starts_with(basename($f), $this->prefix));

        if (empty($stale)) {
            return;
        }

        Storage::disk('public')->delete(array_values($stale));
        $this->info('Purged ' . count($stale) . ' stale sitemap file(s) not matching the current "' . $this->prefix . '" prefix.');
    }

    private function generateStaticSitemap(bool $dryRun): void
    {
        if ($dryRun) return;

        $today = now()->toDateString();
        $urls = [
            ['loc' => str_replace('/en', '', $this->baseUrl), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => "{$this->baseUrl}/collections", 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => "{$this->baseUrl}/blogs", 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['loc' => "{$this->baseUrl}/about-us", 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => "{$this->baseUrl}/contact-us", 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => "{$this->baseUrl}/auction", 'changefreq' => 'daily', 'priority' => '0.8'],
        ];

        $xml = $this->buildUrlsetXml($urls, $today);
        Storage::disk('public')->put("{$this->dir}/{$this->prefix}sitemap-static.xml", $xml);
        $this->line("  {$this->prefix}sitemap-static.xml (" . count($urls) . ' URLs)');
    }

    private function generateCategoriesSitemap(bool $dryRun): void
    {
        if ($dryRun) return;

        $today = now()->toDateString();
        $categories = DB::table('categories')
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->pluck('slug');

        $urls = $categories->map(fn($slug) => [
            'loc' => "{$this->baseUrl}/collections/{$slug}",
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ])->toArray();

        $zmUrls = $categories->map(fn($slug) => [
            'loc' => "{$this->zmBaseUrl}/collections/{$slug}",
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ])->toArray();

        if (empty($urls)) {
            $urls[] = ['loc' => "{$this->baseUrl}/collections", 'changefreq' => 'daily', 'priority' => '0.5'];
            $zmUrls[] = ['loc' => "{$this->zmBaseUrl}", 'changefreq' => 'daily', 'priority' => '0.5'];
        }

        Storage::disk('public')->put("{$this->dir}/{$this->prefix}sitemap-categories.xml", $this->buildUrlsetXml($urls, $today));
        Storage::disk('public')->put("{$this->dir}/{$this->prefix}sitemap-categories-zm.xml", $this->buildUrlsetXml($zmUrls, $today));
        $this->line("  {$this->prefix}sitemap-categories.xml (" . count($urls) . ' URLs)');
        $this->line("  {$this->prefix}sitemap-categories-zm.xml (" . count($zmUrls) . ' URLs)');
    }

    private function generateBlogsSitemap(bool $dryRun): void
    {
        if ($dryRun) return;

        $today = now()->toDateString();
        $blogs = DB::table('blogs')
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['slug', 'updated_at']);

        $urls = $blogs->map(fn($b) => [
            'loc' => "{$this->baseUrl}/blogs/{$b->slug}",
            'lastmod' => $b->updated_at ? \Carbon\Carbon::parse($b->updated_at)->toDateString() : $today,
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ])->toArray();

        if (empty($urls)) {
            $urls[] = ['loc' => "{$this->baseUrl}/blogs", 'changefreq' => 'weekly', 'priority' => '0.5'];
        }

        $xml = $this->buildUrlsetXml($urls, $today);
        Storage::disk('public')->put("{$this->dir}/{$this->prefix}sitemap-blogs.xml", $xml);
        $this->line("  {$this->prefix}sitemap-blogs.xml (" . count($urls) . ' URLs)');
    }

    private function generateProductSitemap(int $index, int $offset, int $limit, string $filter = '', string $filterColumn = '', float $priority = 0.6): void
    {
        // status + is_approved + deleted_at, matching ProductRepository::getProductBySlug()'s
        // visibility check exactly — see the note in handle().
        $query = DB::table('products')
            ->where('status', 1)
            ->where('is_approved', 1)
            ->whereNull('deleted_at');

        if ($filterColumn) {
            $query->where($filterColumn, 1);
        }

        $products = $query->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get(['slug', 'updated_at']);

        $changefreq = $filter ? 'daily' : 'weekly';
        $lastmod = fn($p) => $p->updated_at ? \Carbon\Carbon::parse($p->updated_at)->toDateString() : now()->toDateString();

        $urls = $products->map(fn($p) => [
            'loc' => "{$this->baseUrl}/product/{$p->slug}",
            'lastmod' => $lastmod($p),
            'changefreq' => $changefreq,
            'priority' => (string) $priority,
        ])->toArray();

        $zmUrls = $products->map(fn($p) => [
            'loc' => "{$this->zmBaseUrl}/product/{$p->slug}",
            'lastmod' => $lastmod($p),
            'changefreq' => $changefreq,
            'priority' => (string) $priority,
        ])->toArray();

        if (empty($urls)) {
            $urls[] = ['loc' => "{$this->baseUrl}/collections", 'changefreq' => 'daily', 'priority' => '0.5'];
            $zmUrls[] = ['loc' => "{$this->zmBaseUrl}", 'changefreq' => 'daily', 'priority' => '0.5'];
        }

        $filterPrefix = $filter ? "{$filter}-" : '';
        Storage::disk('public')->put("{$this->dir}/{$this->prefix}sitemap-products-{$filterPrefix}{$index}.xml", $this->buildUrlsetXml($urls));
        Storage::disk('public')->put("{$this->dir}/{$this->prefix}sitemap-products-zm-{$filterPrefix}{$index}.xml", $this->buildUrlsetXml($zmUrls));
    }

    private function generateSitemapIndex(int $productFileCount, int $featuredFileCount = 0, int $saleFileCount = 0): void
    {
        // Use the frontend URL — Google fetches sitemaps via the frontend domain
        $frontendUrl = rtrim(env('FRONTEND_URL', 'https://raines.africa'), '/');
        $frontendUrl = preg_replace('#/en$#', '', $frontendUrl);
        $today = now()->toDateString();

        $sitemaps = [];
        $p = $this->prefix;
        $sitemaps[] = ['loc' => "{$frontendUrl}/{$p}sitemap-static.xml", 'lastmod' => $today];
        $sitemaps[] = ['loc' => "{$frontendUrl}/{$p}sitemap-categories.xml", 'lastmod' => $today];
        $sitemaps[] = ['loc' => "{$frontendUrl}/{$p}sitemap-categories-zm.xml", 'lastmod' => $today];
        $sitemaps[] = ['loc' => "{$frontendUrl}/{$p}sitemap-blogs.xml", 'lastmod' => $today];

        // Featured products first (highest crawl priority)
        for ($i = 0; $i < $featuredFileCount; $i++) {
            $sitemaps[] = ['loc' => "{$frontendUrl}/{$p}sitemap-products-featured-{$i}.xml", 'lastmod' => $today];
            $sitemaps[] = ['loc' => "{$frontendUrl}/{$p}sitemap-products-zm-featured-{$i}.xml", 'lastmod' => $today];
        }

        // Sale products next
        for ($i = 0; $i < $saleFileCount; $i++) {
            $sitemaps[] = ['loc' => "{$frontendUrl}/{$p}sitemap-products-sale-{$i}.xml", 'lastmod' => $today];
            $sitemaps[] = ['loc' => "{$frontendUrl}/{$p}sitemap-products-zm-sale-{$i}.xml", 'lastmod' => $today];
        }

        // All products (en + zm/Kwacha variant, see GenerateSitemaps::generateProductSitemap)
        for ($i = 0; $i < $productFileCount; $i++) {
            $sitemaps[] = ['loc' => "{$frontendUrl}/{$p}sitemap-products-{$i}.xml", 'lastmod' => $today];
            $sitemaps[] = ['loc' => "{$frontendUrl}/{$p}sitemap-products-zm-{$i}.xml", 'lastmod' => $today];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($sitemaps as $s) {
            $xml .= "  <sitemap>\n    <loc>{$s['loc']}</loc>\n    <lastmod>{$s['lastmod']}</lastmod>\n  </sitemap>\n";
        }
        $xml .= '</sitemapindex>';

        Storage::disk('public')->put("{$this->dir}/{$this->prefix}sitemap.xml", $xml);
        $this->line("  {$this->prefix}sitemap.xml (index with " . count($sitemaps) . " sitemaps)");
    }

    private function buildUrlsetXml(array $urls, ?string $defaultLastmod = null): string
    {
        $today = $defaultLastmod ?? now()->toDateString();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$url['loc']}</loc>\n";
            $xml .= "    <lastmod>" . ($url['lastmod'] ?? $today) . "</lastmod>\n";
            if (isset($url['changefreq'])) $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            if (isset($url['priority'])) $xml .= "    <priority>{$url['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }
}
