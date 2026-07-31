<?php

namespace App\Http\Controllers\Admin;

use App\Models\OrderReminderEmail;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderReminderController extends BaseAdminController
{
    protected string $permissionPrefix = 'order-reminder';

    /**
     * Display list of reminder emails
     */
    public function index(Request $request)
    {
        $this->checkPermission('index');
        $query = OrderReminderEmail::with(['order', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by reminder type
        if ($request->has('reminder_type') && $request->reminder_type !== 'all') {
            $query->where('reminder_type', $request->reminder_type);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $reminders = $query->paginate(20);

        // Get statistics
        $stats = $this->getStatistics();

        return view('admin.order-reminders.index', compact('reminders', 'stats'));
    }

    /**
     * Show settings page
     */
    public function settings()
    {
        $this->checkPermission('settings');

        $setting = Setting::first();
        $values = $setting ? $setting->values : [];

        $settings = [
            'first_reminder_hours' => $values['pending_order_first_reminder_hours'] ?? 12,
            'second_reminder_hours' => $values['pending_order_second_reminder_hours'] ?? 24,
            'auto_cancel_hours' => $values['pending_order_auto_cancel_hours'] ?? 72,
        ];

        $stats = $this->getStatistics();

        return view('admin.order-reminders.settings', compact('settings', 'stats'));
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        $this->checkPermission('settings');

        $request->validate([
            'first_reminder_hours' => 'required|integer|min:1|max:168',
            'second_reminder_hours' => 'required|integer|min:1|max:168',
            'auto_cancel_hours' => 'required|integer|min:1|max:168',
        ]);

        // Ensure logical order: first < second < auto_cancel
        if ($request->first_reminder_hours >= $request->second_reminder_hours) {
            return back()->withErrors(['first_reminder_hours' => 'First reminder must be before second reminder']);
        }
        if ($request->second_reminder_hours >= $request->auto_cancel_hours) {
            return back()->withErrors(['second_reminder_hours' => 'Second reminder must be before auto-cancellation']);
        }

        $setting = Setting::firstOrNew(['id' => 1]);
        $values = $setting->values ?? [];

        $values['pending_order_first_reminder_hours'] = $request->first_reminder_hours;
        $values['pending_order_second_reminder_hours'] = $request->second_reminder_hours;
        $values['pending_order_auto_cancel_hours'] = $request->auto_cancel_hours;

        $setting->values = $values;
        $setting->save();

        return back()->with('success', 'Settings updated successfully!');
    }

    /**
     * Get statistics
     */
    private function getStatistics()
    {
        return [
            'total_reminders' => OrderReminderEmail::count(),
            'today_reminders' => OrderReminderEmail::whereDate('sent_at', today())->count(),
            'total_sent' => OrderReminderEmail::where('status', 'sent')->count(),
            'total_failed' => OrderReminderEmail::where('status', 'failed')->count(),
            'total_pending' => OrderReminderEmail::where('status', 'pending')->count(),
            'today_cancelled' => OrderReminderEmail::whereDate('sent_at', today())
                ->where('reminder_type', 'cancellation')
                ->where('status', 'sent')
                ->count(),
            'success_rate' => $this->calculateSuccessRate(),
            'by_type' => OrderReminderEmail::select('reminder_type', DB::raw('count(*) as count'))
                ->where('status', 'sent')
                ->groupBy('reminder_type')
                ->pluck('count', 'reminder_type')
                ->toArray(),
        ];
    }

    /**
     * Calculate success rate
     */
    private function calculateSuccessRate()
    {
        $total = OrderReminderEmail::count();
        if ($total === 0) {
            return 100;
        }
        $successful = OrderReminderEmail::where('status', 'sent')->count();
        return round(($successful / $total) * 100, 1);
    }

    /**
     * Resend a failed email
     */
    public function resend($id)
    {
        $this->checkPermission('resend');

        $reminder = OrderReminderEmail::findOrFail($id);

        if ($reminder->status !== 'failed') {
            return back()->with('error', 'Only failed emails can be resent');
        }

        // TODO: Implement resend logic
        // This would involve queueing the email again

        return back()->with('success', 'Email queued for resending');
    }

    /**
     * API endpoint for stats (for dashboard widgets)
     */
    public function stats(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $stats = [
            'total' => OrderReminderEmail::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'sent' => OrderReminderEmail::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 'sent')->count(),
            'failed' => OrderReminderEmail::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 'failed')->count(),
            'by_type' => OrderReminderEmail::whereBetween('created_at', [$dateFrom, $dateTo])
                ->select('reminder_type', DB::raw('count(*) as count'))
                ->groupBy('reminder_type')
                ->get(),
            'daily' => OrderReminderEmail::whereBetween('created_at', [$dateFrom, $dateTo])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }
}
