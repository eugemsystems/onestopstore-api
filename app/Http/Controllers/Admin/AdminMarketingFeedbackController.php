<?php

namespace App\Http\Controllers\Admin;

use App\Models\MarketingFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMarketingFeedbackController extends BaseAdminController
{
    protected string $permissionPrefix = 'marketing-feedback';

    /**
     * Display the marketing feedback dashboard
     */
    public function index(Request $request)
    {
        $this->checkPermission('index');
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Get feedback with pagination
        $feedbackQuery = MarketingFeedback::with(['user', 'order'])
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->orderBy('created_at', 'desc');

        $feedback = $feedbackQuery->paginate(20);

        // Get statistics
        $stats = $this->getStatistics($dateFrom, $dateTo);

        return view('admin.marketing-feedback.index', compact('feedback', 'stats', 'dateFrom', 'dateTo'));
    }

    /**
     * Get statistics for the dashboard
     */
    private function getStatistics($dateFrom, $dateTo)
    {
        $query = MarketingFeedback::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        // Total responses
        $totalResponses = $query->count();

        // Rating distribution
        $ratingDistribution = MarketingFeedback::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->select('ordering_process_rating', DB::raw('count(*) as count'))
            ->groupBy('ordering_process_rating')
            ->pluck('count', 'ordering_process_rating')
            ->toArray();

        // Source distribution
        $sourceDistribution = MarketingFeedback::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->select('heard_about_source', DB::raw('count(*) as count'))
            ->groupBy('heard_about_source')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'source' => $item->heard_about_source,
                    'count' => $item->count,
                    'label' => $this->getSourceLabel($item->heard_about_source),
                ];
            });

        // Daily responses (last 30 days)
        $dailyResponses = MarketingFeedback::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Country distribution
        $countryDistribution = MarketingFeedback::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->whereNotNull('country_name')
            ->select('country_name', 'country_code', DB::raw('count(*) as count'))
            ->groupBy('country_name', 'country_code')
            ->orderBy('count', 'desc')
            ->limit(10) // Top 10 countries
            ->get()
            ->map(function ($item) {
                return [
                    'country' => $item->country_name,
                    'code' => $item->country_code,
                    'count' => $item->count,
                ];
            });

        // Average rating score (Excellent=4, Good=3, Fair=2, Poor=1)
        $ratingScores = [
            'excellent' => 4,
            'good' => 3,
            'fair' => 2,
            'poor' => 1,
        ];

        $totalScore = 0;
        $totalRatings = 0;
        foreach ($ratingDistribution as $rating => $count) {
            $totalScore += ($ratingScores[$rating] ?? 0) * $count;
            $totalRatings += $count;
        }
        $averageScore = $totalRatings > 0 ? round($totalScore / $totalRatings, 2) : 0;

        return [
            'total_responses' => $totalResponses,
            'rating_distribution' => $ratingDistribution,
            'source_distribution' => $sourceDistribution,
            'daily_responses' => $dailyResponses,
            'country_distribution' => $countryDistribution,
            'average_score' => $averageScore,
        ];
    }

    /**
     * Get API statistics
     */
    public function getStats(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $stats = $this->getStatistics($dateFrom, $dateTo);

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * View single feedback
     */
    public function show($id)
    {
        $this->checkPermission('show');

        $feedback = MarketingFeedback::with(['user', 'order'])->findOrFail($id);
        return view('admin.marketing-feedback.show', compact('feedback'));
    }

    /**
     * Delete feedback
     */
    public function destroy($id)
    {
        $this->checkPermission('delete');

        $feedback = MarketingFeedback::findOrFail($id);
        $feedback->delete();

        return redirect()->route('admin.marketing-feedback.index')
            ->with('success', 'Feedback deleted successfully');
    }

    /**
     * Export feedback to CSV
     */
    public function export(Request $request)
    {
        $this->checkPermission('export');
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $feedback = MarketingFeedback::with(['user', 'order'])
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'marketing-feedback-' . $dateFrom . '-to-' . $dateTo . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($feedback) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'ID',
                'Date',
                'Order Number',
                'User Name',
                'User Email',
                'Country Code',
                'Country Name',
                'Rating',
                'Source',
                'Additional Comments',
            ]);

            // Data rows
            foreach ($feedback as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->created_at->format('Y-m-d H:i:s'),
                    $item->order_number,
                    $item->user_name ?? $item->user?->name,
                    $item->user_email ?? $item->user?->email,
                    $item->country_code ?? '',
                    $item->country_name ?? 'Unknown',
                    $item->rating_label,
                    $item->source_label,
                    $item->additional_comments,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get source label helper
     */
    private function getSourceLabel($source)
    {
        return match($source) {
            'google_adverts' => 'Google Adverts',
            'facebook_adverts' => 'Facebook Adverts',
            'instagram_promotion' => 'Instagram Promotion',
            'comic_awards' => 'Comic Awards',
            'dare_remachinda' => 'Dare Remachinda',
            'zimcelebs' => 'ZimCelebs',
            'tiktok_advert' => 'Tiktok Advert',
            'refered_by_friend' => 'Referred by a Friend',
            'other' => 'Other',
            default => ucwords(str_replace('_', ' ', $source)),
        };
    }
}

