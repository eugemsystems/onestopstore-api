<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OrderStatus;
use App\Models\User;
use App\Services\StaffPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminStaffPerformanceController extends Controller
{
    private function adminOnly(): void
    {
        abort_unless(auth()->user()?->can('staff-performance.view'), 403);
    }

    /** Leaderboard: every staff member ranked by USD-equivalent revenue for the month/year. */
    public function index(Request $request, StaffPerformanceService $svc)
    {
        $this->adminOnly();
        [$period, $from, $to, $label, $month, $year] = StaffPerformanceService::resolvePeriod($request);

        $staff = $svc->staffUsers()
            ->map(fn ($u) => $svc->summary($u, $from, $to))
            ->sortByDesc('usd_total')->values();

        $grand = [
            'orders' => $staff->sum('total_paid_orders'),
            'usd'    => $staff->sum('usd_total'),
        ];

        return view('admin.staff-performance.index', compact('staff', 'grand', 'period', 'from', 'to', 'label', 'month', 'year'));
    }

    /** Audit page: every order credited to one staff member, with proof of how it was attributed. */
    public function show(Request $request, User $user, StaffPerformanceService $svc)
    {
        $this->adminOnly();
        [$period, $from, $to, $label, $month, $year] = StaffPerformanceService::resolvePeriod($request);

        $summary = $svc->summary($user, $from, $to);

        $paginator = $svc->attributedOrders($user->id, $from, $to)
            ->leftJoin('users as c', 'c.id', '=', 'orders.consumer_id')
            ->leftJoin('order_status as os', 'os.id', '=', 'orders.order_status_id')
            ->selectRaw('orders.id, orders.order_number, orders.total, orders.currency, orders.currency_symbol, orders.exchange_rate,
                orders.payment_status, orders.payment_method, orders.note, orders.created_at, orders.updated_at, orders.created_by_id,
                (' . StaffPerformanceService::usdExpr() . ') as usd_total,
                (' . StaffPerformanceService::localExpr() . ') as local_total,
                (' . StaffPerformanceService::rateExpr() . ') as rate,
                c.name as customer_name, c.email as customer_email, os.name as status_name')
            ->orderByDesc('orders.created_at')
            ->paginate(50)->withQueryString();

        $orderIds = $paginator->pluck('id')->all();
        $symbols = $svc->currencySymbols();

        // Full status history per order (who changed what, when).
        $histories = DB::table('order_status_histories as h')
            ->leftJoin('order_status as o', 'o.id', '=', 'h.old_status_id')
            ->leftJoin('order_status as n', 'n.id', '=', 'h.new_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'h.updated_by_id')
            ->whereIn('h.order_id', $orderIds)
            ->orderBy('h.id')
            ->selectRaw('h.id, h.order_id, h.new_status_id, h.updated_by_id, h.created_at, o.name as old_status, n.name as new_status, u.name as by_name')
            ->get()->groupBy('order_id');

        // Any logged edits made to these orders (total/price tampering trail).
        $edits = ActivityLog::where('subject_type', \App\Models\Order::class)
            ->whereIn('subject_id', $orderIds)
            ->whereNotIn('event', ['created'])
            ->orderBy('id')->get()->groupBy('subject_id');
        $causerNames = User::whereIn('id', $edits->flatten()->pluck('causer_id')->filter()->unique())->pluck('name', 'id');

        // Customers with the creator/credited staff being the same account are worth a second look.
        $creatorIds = collect($paginator->items())->pluck('created_by_id')->filter()->unique();
        $creatorNames = User::whereIn('id', $creatorIds)->pluck('name', 'id');

        $nonRev = $svc->nonRevenueStatusIds;
        $rows = collect($paginator->items())->map(function ($o) use ($histories, $edits, $causerNames, $creatorNames, $user, $nonRev, $symbols) {
            $hist = $histories->get($o->id, collect());
            $credit = $hist->first(fn ($h) => (int) $h->updated_by_id === (int) $user->id && !in_array((int) $h->new_status_id, $nonRev, true));
            $orderEdits = $edits->get($o->id, collect());
            $flags = [];
            if ($orderEdits->isNotEmpty()) {
                $flags[] = $orderEdits->count() . ' logged edit(s)';
            }
            if ($o->created_by_id && (int) $o->created_by_id === (int) $user->id) {
                $flags[] = 'Created by this staff member';
            }
            if (str_starts_with((string) $o->note, 'Created from ')) {
                $flags[] = 'Converted from ' . trim(explode('#', substr($o->note, 13))[0]);
            }
            if ($credit && strtotime($credit->created_at) - strtotime($o->created_at) < 120) {
                $flags[] = 'Confirmed <2 min after creation';
            }
            $o->symbol = $symbols[strtoupper((string) $o->currency)] ?? $o->currency_symbol ?? $o->currency;
            $o->history = $hist;
            $o->credit = $credit;
            $o->edits = $orderEdits->map(fn ($e) => [
                'event' => $e->event, 'description' => $e->description,
                'by' => $causerNames[$e->causer_id] ?? '—', 'at' => $e->created_at,
                'old' => $e->properties['old'] ?? [], 'new' => $e->properties['attributes'] ?? [],
            ]);
            $o->creator_name = $creatorNames[$o->created_by_id] ?? null;
            $o->flags = $flags;
            return $o;
        });

        $orders = new LengthAwarePaginator($rows, $paginator->total(), $paginator->perPage(), $paginator->currentPage(), [
            'path' => $paginator->path(), 'query' => $request->query(),
        ]);

        // Daily USD trend for the chosen window (month → per day, year → per month).
        $fmt = $period === 'year' ? 'YYYY-MM' : 'YYYY-MM-DD';
        $trend = $svc->attributedOrders($user->id, $from, $to)
            ->selectRaw("to_char(orders.created_at, '{$fmt}') as bucket, COUNT(*) as cnt, SUM(" . StaffPerformanceService::usdExpr() . ') as usd')
            ->groupBy('bucket')->orderBy('bucket')->get();

        return view('admin.staff-performance.show', compact('user', 'summary', 'orders', 'trend', 'period', 'from', 'to', 'label', 'month', 'year'));
    }
}
