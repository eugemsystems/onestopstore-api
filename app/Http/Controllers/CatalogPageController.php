<?php

namespace App\Http\Controllers;

use App\Models\CatalogPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Public endpoint the React site reads catalog HTML from:
 *   GET /catalog/{slug}  ->  the currently uploaded file, as text/html.
 */
class CatalogPageController extends Controller
{
    public function show(string $slug)
    {
        $page = CatalogPage::where('slug', $slug)->where('is_active', true)->first();
        abort_unless($page && $page->fileExists(), 404);

        // The file is served exactly as uploaded. Debug tooling that injects itself into HTML
        // responses (Laravel Debugbar in dev) must not touch it.
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        return response(Storage::disk('public')->get($page->file_path), 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'public, max-age=60',
            'Last-Modified' => $page->updated_at->toRfc7231String(),
            'ETag' => '"' . md5($page->slug . ':' . $page->version . ':' . $page->updated_at->timestamp) . '"',
            'X-Content-Type-Options' => 'nosniff',
            // Uploaded HTML is arbitrary. Sandboxing (no allow-same-origin) lets its own
            // scripts/styles/forms work but gives it an opaque origin, so it cannot read the
            // admin session or any cookies/storage of this domain.
            'Content-Security-Policy' => 'sandbox allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox allow-modals allow-downloads',
        ]);
    }

    /**
     * POST /api/catalog/{slug}/hit — sent by a small script inside the catalog page, from the
     * visitor's own browser, when the page is opened (see the React route that serves it).
     * The visit is classified from that browser's User-Agent: crawlers and link-preview
     * fetchers are counted separately from people.
     */
    public function hit(Request $request, string $slug)
    {
        $page = CatalogPage::where('slug', $slug)->where('is_active', true)->first();
        abort_unless($page, 404);

        $page->recordHit(CatalogPage::isBotUserAgent($request->userAgent()));

        return response()->noContent();
    }
}
