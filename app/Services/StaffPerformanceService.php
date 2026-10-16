<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\OrderStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for staff revenue attribution, shared by the staff
 * leaderboard and the per-staff audit page so both always agree.
 *
 * An order's revenue is credited to whoever made the FIRST status change that is
 * NOT a delivery/logistics status and NOT a cancellation (i.e. the payment-confirming
 * change such as "Processing").
 */
class StaffPerformanceService
{
    public const PAID = ['COMPLETED', 'COMPLETE', 'SUCCESS'];

    public array $cancelledStatusIds = [];
    public array $nonRevenueStatusIds = [];

    public function __construct()
    {
        $this->cancelledStatusIds = OrderStatus::whereIn('slug', ['cancelled', 'canceled'])->pluck('id')->toArray();

        $deliveryStatusIds = OrderStatus::where(function ($q) {
            $q->whereIn(DB::raw('lower(trim(slug))'), [
                'shipped', 'out-for-delivery', 'out for delivery',
                'ready-for-collection', 'ready for collection',
                'collected', 'delivered',
                'in-transit-to-zim', 'in transit to zim',
                'dropped-at-the-deport', 'dropped at the deport',
                'arrived-at-local-branch', 'arrived at local branch',
            ])->orWhereIn(DB::raw('lower(trim(name))'), [
                'shipped', 'out for delivery', 'ready for collection',
                'collected', 'delivered',
            ]);
        })->pluck('id')->toArray();

        $this->nonRevenueStatusIds = array_values(array_unique(array_merge($deliveryStatusIds, $this->cancelledStatusIds)));
    }

    /**
     * Resolve the period filter. Only calendar months or whole years are allowed
     * (period=month&month=YYYY-MM, or period=year&year=YYYY). Defaults to the current month.
     *
     * @return array{0:string,1:Carbon,2:Carbon,3:string,4:string,5:int} period, from, to, label, month(YYYY-MM), year
     */
    public static function resolvePeriod(Request $request): array
    {
        $period = $request->input('period') === 'year' ? 'year' : 'month';

        try {
            $month = Carbon::createFromFormat('!Y-m', (string) $request->input('month', now()->format('Y-m')));
        } catch (\Throwable) {
            $month = now()->startOfMonth();
        }
        $year = (int) $request->input('year', now()->year);
        if ($year < 2000 || $year > (int) now()->year + 1) {
            $year = (int) now()->year;
        }

        if ($period === 'year') {
            $from = Carbon::create($year, 1, 1)->startOfDay();
            $to = Carbon::create($year, 12, 31)->endOfDay();
            $label = (string) $year;
        } else {
            $from = $month->copy()->startOfMonth();
            $to = $month->copy()->endOfMonth();
            $label = $month->format('F Y');
        }

        return [$period, $from, $to, $label, $month->format('Y-m'), $year];
    }

    public function staffUsers(): Collection
    {
        // Only the "Staff Raines" role (case/space/dash/underscore-insensitive, either word order).
        return User::whereHas('roles', fn ($q) => $q->whereRaw(
                "LOWER(REPLACE(REPLACE(REPLACE(roles.name, '_', ''), '-', ''), ' ', '')) IN ('staffraines', 'rainesstaff')"
            ))
            ->whereNull('deleted_at')->select('id', 'name', 'email')->orderBy('name')->get();
    }

    /** Paid, non-cancelled, top-level orders credited to $userId within the window. */
    public function attributedOrders(int $userId, ?Carbon $from, Carbon $to)
    {
        $nonRev = $this->nonRevenueStatusIds;

        return DB::table('orders')
            ->whereExists(function ($sub) use ($userId, $nonRev) {
                $sub->select(DB::raw(1))
                    ->from('order_status_histories as h')
                    ->whereColumn('h.order_id', 'orders.id')
                    ->where('h.updated_by_id', $userId)
                    ->when(!empty($nonRev), fn ($s) => $s->whereNotIn('h.new_status_id', $nonRev))
                    ->whereRaw('NOT EXISTS (
                        SELECT 1 FROM order_status_histories h2
                        WHERE h2.order_id = h.order_id
                          AND h2.id < h.id
                          AND h2.updated_by_id IS NOT NULL
                          AND h2.updated_by_id != 0
                          ' . (!empty($nonRev) ? 'AND h2.new_status_id NOT IN (' . implode(',', array_map('intval', $nonRev)) . ')' : '') . '
                    )');
            })
            ->whereIn('orders.payment_status', self::PAID)
            ->when(!empty($this->cancelledStatusIds), fn ($q) => $q->whereNotIn('orders.order_status_id', $this->cancelledStatusIds))
            ->whereNull('orders.parent_id')->whereNull('orders.deleted_at')
            ->when($from, fn ($q) => $q->where('orders.created_at', '>=', $from))
            ->where('orders.created_at', '<=', $to);
    }

    /** USD-equivalent expression: exchange_rate is "local units per 1 USD". */
    public static function usdExpr(string $prefix = 'orders'): string
    {
        return "{$prefix}.total / COALESCE(NULLIF({$prefix}.exchange_rate, 0), 1)";
    }

    public function currencySymbols(): Collection
    {
        return Currency::query()->pluck('symbol', 'code')->mapWithKeys(fn ($s, $c) => [strtoupper($c) => $s]);
    }

    /** Headline numbers for one staff member. */
    public function summary(User $user, ?Carbon $from, Carbon $to): array
    {
        $symbols = $this->currencySymbols();

        $byCurrency = $this->attributedOrders($user->id, $from, $to)
            ->selectRaw('orders.currency, MIN(orders.currency_symbol) as currency_symbol, COUNT(*) as orders_count, SUM(orders.total) as total_amount, SUM(' . self::usdExpr() . ') as usd_total')
            ->groupBy('orders.currency')->orderByDesc('usd_total')->get()
            ->map(function ($r) use ($symbols) {
                $r->currency_symbol = $symbols[strtoupper((string) $r->currency)] ?? $r->currency_symbol;
                return $r;
            });

        $byStatus = $this->attributedOrders($user->id, $from, $to)
            ->join('order_status', 'orders.order_status_id', '=', 'order_status.id')
            ->selectRaw('order_status.name as status_name, COUNT(*) as cnt')
            ->groupBy('order_status.name')->pluck('cnt', 'status_name');

        $orderCount = (int) $byCurrency->sum('orders_count');
        $usdTotal = (float) $byCurrency->sum('usd_total');

        $docs = DB::table('invoices_quotations')
            ->where('created_by', $user->id)->whereNull('deleted_at')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->where('created_at', '<=', $to)
            ->selectRaw('document_type, COUNT(*) as cnt')->groupBy('document_type')->pluck('cnt', 'document_type');

        return [
            'id'                  => $user->id,
            'name'                => $user->name,
            'email'               => $user->email,
            'total_paid_orders'   => $orderCount,
            'usd_total'           => $usdTotal,
            'avg_order_usd'       => $orderCount ? $usdTotal / $orderCount : 0,
            'revenue_by_currency' => $byCurrency,
            'orders_by_status'    => $byStatus,
            'quotations'          => (int) $docs->get('quotation', 0),
            'invoices'            => (int) $docs->get('invoice', 0),
            'proformas'           => (int) $docs->get('proforma', 0),
        ];
    }
}
