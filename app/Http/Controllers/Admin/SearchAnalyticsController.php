<?php

namespace App\Http\Controllers\Admin;

use App\Models\SearchQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchAnalyticsController extends BaseAdminController
{
    public function index(Request $request)
    {
        // Get date range from request or default to last 30 days
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        // Overall Statistics
        $totalSearches = SearchQuery::where('created_at', '>=', $startDate)->count();
        $uniqueSearches = SearchQuery::where('created_at', '>=', $startDate)
            ->distinct('normalized_query')
            ->count('normalized_query');

        $avgResults = SearchQuery::where('created_at', '>=', $startDate)
            ->avg('results_count');

        $zeroResultsCount = SearchQuery::where('created_at', '>=', $startDate)
            ->where('results_count', 0)
            ->count();

        $successRate = $totalSearches > 0
            ? round((($totalSearches - $zeroResultsCount) / $totalSearches) * 100, 2)
            : 0;

        // Popular Searches
        $popularSearches = SearchQuery::getPopularSearches(20, $days);

        // No Results Searches (Inventory Gaps)
        $noResultsSearches = SearchQuery::getNoResultsSearches(20, $days);

        // Trending Searches
        $trendingSearches = SearchQuery::getTrendingSearches(20);

        // Searches by Day (Last 14 days)
        $searchesByDay = SearchQuery::where('created_at', '>=', now()->subDays(14))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Searches by Hour (Last 7 days)
        $searchesByHour = SearchQuery::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('EXTRACT(HOUR FROM created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Top Categories (from filters)
        $topCategories = SearchQuery::where('created_at', '>=', $startDate)
            ->whereNotNull('filters')
            ->get()
            ->map(function($query) {
                return $query->filters['category'] ?? null;
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(10);

        // Top Brands (from filters)
        $topBrands = SearchQuery::where('created_at', '>=', $startDate)
            ->whereNotNull('filters')
            ->get()
            ->map(function($query) {
                return $query->filters['brand'] ?? null;
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(10);

        // Recent Searches (Paginated)
        $recentSearches = SearchQuery::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(30, ['*'], 'recent_page');

        // Top Users by Search Count
        $topUsers = SearchQuery::where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->with('user:id,name,email')
            ->selectRaw('user_id, COUNT(*) as search_count')
            ->groupBy('user_id')
            ->orderByDesc('search_count')
            ->limit(10)
            ->get();

        return view('admin.analytics.search', compact(
            'days',
            'totalSearches',
            'uniqueSearches',
            'avgResults',
            'successRate',
            'zeroResultsCount',
            'popularSearches',
            'noResultsSearches',
            'trendingSearches',
            'searchesByDay',
            'searchesByHour',
            'topCategories',
            'topBrands',
            'recentSearches',
            'topUsers'
        ));
    }

    /**
     * Export search data as CSV
     */
    public function export(Request $request)
    {
        $days = $request->input('days', 30);
        $type = $request->input('type', 'all'); // all, popular, no-results, trending

        $filename = "search_analytics_{$type}_" . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($type, $days) {
            $file = fopen('php://output', 'w');

            switch($type) {
                case 'popular':
                    fputcsv($file, ['Search Query', 'Search Count']);
                    $data = SearchQuery::getPopularSearches(100, $days);
                    foreach($data as $row) {
                        fputcsv($file, [$row->normalized_query, $row->search_count]);
                    }
                    break;

                case 'no-results':
                    fputcsv($file, ['Search Query', 'Search Count']);
                    $data = SearchQuery::getNoResultsSearches(100, $days);
                    foreach($data as $row) {
                        fputcsv($file, [$row->normalized_query, $row->search_count]);
                    }
                    break;

                case 'trending':
                    fputcsv($file, ['Search Query', 'Recent Count', 'Growth %']);
                    $data = SearchQuery::getTrendingSearches(100);
                    foreach($data as $row) {
                        fputcsv($file, [
                            $row['query'],
                            $row['recent_count'],
                            round($row['growth'] * 100, 2)
                        ]);
                    }
                    break;

                default: // all
                    fputcsv($file, ['Date', 'Query', 'Results', 'User Email', 'Filters', 'Sort By']);
                    $data = SearchQuery::with('user:id,email')
                        ->where('created_at', '>=', now()->subDays($days))
                        ->orderBy('created_at', 'desc')
                        ->get();
                    foreach($data as $row) {
                        fputcsv($file, [
                            $row->created_at->format('Y-m-d H:i:s'),
                            $row->query,
                            $row->results_count,
                            $row->user ? $row->user->email : 'Guest',
                            json_encode($row->filters),
                            $row->sort_by
                        ]);
                    }
                    break;
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
