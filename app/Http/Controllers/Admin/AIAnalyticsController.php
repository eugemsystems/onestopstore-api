<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AIAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AIAnalyticsController extends Controller
{
    public function __construct(private AIAnalyticsService $service) {}

    public function index()
    {
        $history = session('ai_analytics_history', []);
        return view('admin.ai-analytics.index', compact('history'));
    }

    public function query(Request $request)
    {
        $request->validate(['question' => 'required|string|min:5|max:500']);

        $question = trim($request->input('question'));

        // Cache by question hash for 10 minutes — same question = same answer
        $cacheKey = 'ai_analytics:' . md5($question);
        $result   = Cache::remember($cacheKey, now()->addMinutes(10), fn() => $this->service->answer($question));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        // Store last 10 questions in session history
        $history   = session('ai_analytics_history', []);
        $history   = array_slice(array_merge([['question' => $question, 'time' => now()->format('H:i')]], $history), 0, 10);
        session(['ai_analytics_history' => $history]);

        return back()->with([
            'result'   => $result,
            'question' => $question,
        ]);
    }

    public function clearHistory(Request $request)
    {
        session()->forget('ai_analytics_history');
        return back();
    }
}
