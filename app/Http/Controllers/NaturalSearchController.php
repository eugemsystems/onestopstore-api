<?php

namespace App\Http\Controllers;

use App\Services\AISearchService;
use App\Services\ElasticsearchService;
use App\Http\Controllers\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NaturalSearchController extends Controller
{
    public function __construct(private AISearchService $aiSearch) {}

    // GET /api/natural-search?q=...&currency=USD&size=20&page=1
    public function search(Request $request)
    {
        // Feature flag — fall back to plain Elasticsearch when disabled
        if (!env('AI_SEARCH_ENABLED', true)) {
            return app(SearchController::class)->products(
                $request->merge(['search' => $request->query('q', '')])
            );
        }

        $raw  = trim((string) $request->query('q', ''));
        $size = max(1, min((int) $request->query('size', 20), 100));
        $page = max(1, (int) $request->query('page', 1));

        if (empty($raw)) {
            return response()->json(['error' => 'Query is required'], 422);
        }

        // AI expansion — cached 48 h
        $expansion = $this->aiSearch->expand($raw);
        $keywords  = $expansion['keywords'];

        // Build Elasticsearch multi-keyword query
        try {
            $client = ElasticsearchService::client();
            $index  = ElasticsearchService::indexName();
            $from   = ($page - 1) * $size;

            // Base filters
            $baseFilter = [
                ['term'  => ['status'      => 1]],
                ['term'  => ['is_approved' => 1]],
                ['range' => ['price'       => ['gt' => 0]]],
            ];

            // Currency-based region filters — inclusionary: show country-specific + global products
            $currency = strtoupper(trim((string) $request->query('currency', '')));
            $globalClause = ['bool' => ['filter' => [
                ['term' => ['zambia_only'  => false]],
                ['term' => ['zimbabwe_only'=> false]],
                ['term' => ['sa_only'      => false]],
            ]]];
            if ($currency === 'ZMW') {
                $baseFilter[] = ['bool' => ['should' => [['term' => ['zambia_only' => true]], $globalClause], 'minimum_should_match' => 1]];
            } elseif ($currency === 'ZW' || $currency === 'USD') {
                $baseFilter[] = ['bool' => ['should' => [['term' => ['zimbabwe_only' => true]], $globalClause], 'minimum_should_match' => 1]];
            } elseif ($currency === 'ZAR') {
                $baseFilter[] = ['bool' => ['should' => [['term' => ['sa_only' => true]], $globalClause], 'minimum_should_match' => 1]];
            }

            // For each keyword, build a multi-field match. Products matching MORE keywords rank higher.
            $shouldClauses = [];
            foreach ($keywords as $kw) {
                $kw = trim($kw);
                if (empty($kw)) continue;

                // Exact phrase in name = very high boost
                $shouldClauses[] = ['match_phrase' => ['name' => ['query' => $kw, 'boost' => 12]]];
                // Phrase in combined field
                $shouldClauses[] = ['match_phrase' => ['combined' => ['query' => $kw, 'boost' => 6]]];
                // Multi-field cross-match
                $shouldClauses[] = [
                    'multi_match' => [
                        'query'  => $kw,
                        'fields' => ['name^5', 'combined^3', 'short_description^2', 'description^1', 'tags^2', 'categories^2', 'brand.name^2'],
                        'type'   => 'best_fields',
                        'fuzziness' => strlen($kw) > 5 ? 'AUTO' : 0,
                    ],
                ];
            }

            // Also include the translated english_query as a phrase match for best results
            if ($expansion['english_query'] !== $raw) {
                $shouldClauses[] = ['match_phrase' => ['combined' => ['query' => $expansion['english_query'], 'boost' => 8]]];
                $shouldClauses[] = ['multi_match'  => ['query' => $expansion['english_query'], 'fields' => ['name^5', 'combined^3'], 'fuzziness' => 'AUTO', 'boost' => 5]];
            }

            // Category filter by AI-detected categories
            if (!empty($expansion['categories'])) {
                foreach ($expansion['categories'] as $cat) {
                    $shouldClauses[] = ['match' => ['categories' => ['query' => $cat, 'boost' => 3]]];
                }
            }

            $body = [
                'from' => $from,
                'size' => $size,
                'query' => [
                    'bool' => [
                        'filter' => $baseFilter,
                        'should' => $shouldClauses,
                        'minimum_should_match' => 1,
                    ],
                ],
                '_source' => true,
                'track_total_hits' => true,
            ];

            $res   = $client->search(['index' => $index, 'body' => $body])->asArray();
            $hits  = $res['hits']['hits'] ?? [];
            $total = $res['hits']['total']['value'] ?? 0;

            $products = array_map(fn($h) => $h['_source'], $hits);

            return response()->json([
                'ai'           => [
                    'language'     => $expansion['language'],
                    'english_query'=> $expansion['english_query'],
                    'intent'       => $expansion['intent'],
                    'keywords'     => $keywords,
                    'is_translated'=> $expansion['is_translated'],
                    'original'     => $expansion['original'],
                ],
                'data'         => $products,
                'total'        => $total,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $size),
                'per_page'     => $size,
            ]);

        } catch (\Throwable $e) {
            Log::error('NaturalSearch ES error', ['error' => $e->getMessage(), 'query' => $raw]);
            return response()->json(['error' => 'Search failed: ' . $e->getMessage()], 500);
        }
    }

    // GET /api/natural-search/expand?q=... — just return AI expansion (for frontend use)
    public function expand(Request $request)
    {
        $raw = trim((string) $request->query('q', ''));
        if (empty($raw)) return response()->json(['error' => 'Query required'], 422);
        return response()->json($this->aiSearch->expand($raw));
    }
}
