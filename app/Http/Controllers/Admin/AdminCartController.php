<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cart;
use App\Models\CartReminder;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminCartController extends BaseAdminController
{
    protected string $permissionPrefix = 'cart-reminder';

    /**
     * Display abandoned carts and reminder log
     */
    public function index(Request $request)
    {
        $this->checkPermission('index');

        // Current cart contents grouped by user (paginate on distinct consumers
        // to keep this performant for tables with thousands of cart rows).
        // Users are ordered by their most recent cart activity, newest first.
        $perPage = 20;
        $cartPage = (int) $request->get('cart_page', 1);

        $distinctConsumerIds = Cart::whereNull('deleted_at')
            ->selectRaw('consumer_id, MAX(created_at) as latest_activity')
            ->groupBy('consumer_id')
            ->orderByDesc('latest_activity')
            ->pluck('consumer_id');

        $totalConsumers = $distinctConsumerIds->count();
        $pagedConsumerIds = $distinctConsumerIds->forPage($cartPage, $perPage)->values();

        $cartsByUserUnordered = Cart::with(['product', 'variation', 'consumer'])
            ->whereNull('deleted_at')
            ->whereIn('consumer_id', $pagedConsumerIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('consumer_id');

        // groupBy() doesn't preserve the newest-first order from $pagedConsumerIds
        // (it follows the query's row order), so re-key it explicitly.
        $cartsByUser = $pagedConsumerIds->mapWithKeys(fn ($id) => [$id => $cartsByUserUnordered->get($id, collect())]);

        $carts = new LengthAwarePaginator(
            $cartsByUser,
            $totalConsumers,
            $perPage,
            $cartPage,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'cart_page']
        );

        // Reminder log, newest first
        $reminderQuery = CartReminder::with('user')->orderByDesc('created_at')->orderByDesc('id');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $reminderQuery->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $reminderQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $reminderQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $reminders = $reminderQuery->paginate($perPage, ['*'], 'reminder_page');

        // Get statistics
        $stats = $this->getStatistics($totalConsumers);

        return view('admin.cart-reminders.index', compact('carts', 'reminders', 'stats'));
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
            'first_hours' => $values['cart_reminder_first_hours'] ?? 12,
            'spacing_hours' => $values['cart_reminder_spacing_hours'] ?? 24,
            'max_count' => $values['cart_reminder_max_count'] ?? 3,
        ];

        return view('admin.cart-reminders.settings', compact('settings'));
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        $this->checkPermission('settings');

        $request->validate([
            'first_hours' => 'required|integer|min:1|max:168',
            'spacing_hours' => 'required|integer|min:1|max:168',
            'max_count' => 'required|integer|min:1|max:10',
        ]);

        $setting = Setting::firstOrNew(['id' => 1]);
        $values = $setting->values ?? [];

        $values['cart_reminder_first_hours'] = $request->first_hours;
        $values['cart_reminder_spacing_hours'] = $request->spacing_hours;
        $values['cart_reminder_max_count'] = $request->max_count;

        $setting->values = $values;
        $setting->save();

        return back()->with('success', 'Settings updated successfully!');
    }

    /**
     * Get statistics
     */
    private function getStatistics(?int $totalUsersWithCarts = null)
    {
        return [
            'total_carts' => Cart::whereNull('deleted_at')->count(),
            'total_users_with_carts' => $totalUsersWithCarts ?? Cart::whereNull('deleted_at')->distinct('consumer_id')->count('consumer_id'),
            'reminders_sent_today' => CartReminder::whereDate('sent_at', today())->where('status', 'sent')->count(),
            'reminders_sent_week' => CartReminder::where('sent_at', '>=', now()->subDays(7))->where('status', 'sent')->count(),
            'total_reminders' => CartReminder::count(),
            'total_failed' => CartReminder::where('status', 'failed')->count(),
        ];
    }
}
