<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    use HasFactory;

    protected $fillable = [
        'query',
        'normalized_query',
        'results_count',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'filters',
        'sort_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'results_count' => 'integer',
    ];

    /**
     * Get the user that performed the search
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get popular searches
     */
    public static function getPopularSearches($limit = 10, $days = 30)
    {
        return self::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('normalized_query, COUNT(*) as search_count')
            ->groupBy('normalized_query')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get searches with no results
     */
    public static function getNoResultsSearches($limit = 10, $days = 30)
    {
        return self::where('created_at', '>=', now()->subDays($days))
            ->where('results_count', 0)
            ->selectRaw('normalized_query, COUNT(*) as search_count')
            ->groupBy('normalized_query')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get trending searches (searches that increased recently)
     */
    public static function getTrendingSearches($limit = 10)
    {
        $recentSearches = self::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('normalized_query, COUNT(*) as recent_count')
            ->groupBy('normalized_query')
            ->get()
            ->pluck('recent_count', 'normalized_query');

        $olderSearches = self::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
            ->selectRaw('normalized_query, COUNT(*) as older_count')
            ->groupBy('normalized_query')
            ->get()
            ->pluck('older_count', 'normalized_query');

        $trending = [];
        foreach ($recentSearches as $query => $recentCount) {
            $olderCount = $olderSearches->get($query, 0);
            $growth = $olderCount > 0 ? ($recentCount - $olderCount) / $olderCount : $recentCount;
            $trending[] = [
                'query' => $query,
                'recent_count' => $recentCount,
                'growth' => $growth,
            ];
        }

        return collect($trending)
            ->sortByDesc('growth')
            ->take($limit)
            ->values();
    }
}
