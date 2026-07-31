<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVoucherController extends Controller
{
    /**
     * Display a listing of vouchers with stats
     */
    public function index(Request $request)
    {
        // Check permission
        $this->authorize('view', Voucher::class);

        // Get filter parameters
        $status = $request->get('status');
        $search = $request->get('search');
        $perPage = $request->get('per_page', 25);

        // Build query
        $query = Voucher::with(['product', 'order', 'purchasedBy', 'redeemedBy'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhereHas('purchasedBy', function($q2) use ($search) {
                      $q2->where('name', 'LIKE', "%{$search}%")
                         ->orWhere('email', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('order', function($q2) use ($search) {
                      $q2->where('order_number', 'LIKE', "%{$search}%");
                  });
            });
        }

        $vouchers = $query->paginate($perPage);

        // Calculate stats
        $stats = [
            'total' => Voucher::count(),
            'active' => Voucher::where('status', 'active')->count(),
            'redeemed' => Voucher::where('status', 'redeemed')->count(),
            'expired' => Voucher::whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->where('status', '!=', 'redeemed')
                ->count(),
            'total_value' => Voucher::where('status', 'active')->sum('amount'),
            'redeemed_value' => Voucher::where('status', 'redeemed')->sum('amount'),
        ];

        return view('admin.vouchers.index', compact('vouchers', 'stats', 'status', 'search'));
    }

    /**
     * Display the specified voucher with full details
     */
    public function show($id)
    {
        // Check permission
        $this->authorize('view', Voucher::class);

        $voucher = Voucher::with([
            'product',
            'order.consumer',
            'order.products',
            'order.billing_address',
            'order.shipping_address',
            'purchasedBy',
            'redeemedBy'
        ])->findOrFail($id);

        // Get payment transactions for this order
        $transactions = [];
        if ($voucher->order && $voucher->order->consumer_id) {
            // Get the user's wallet
            $wallet = \App\Models\Wallet::where('consumer_id', $voucher->order->consumer_id)->first();

            if ($wallet) {
                $transactions = \App\Models\Transaction::where('wallet_id', $wallet->id)
                    ->where(function($query) use ($voucher) {
                        $query->where('detail', 'LIKE', '%' . $voucher->order->order_number . '%')
                              ->orWhere('detail', 'LIKE', '%Gift Voucher%' . $voucher->code . '%');
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        }

        // Get redemption transaction if redeemed
        $redemptionTransaction = null;
        if ($voucher->status === 'redeemed' && $voucher->redeemed_by) {
            // Get the redeemer's wallet
            $redeemerWallet = \App\Models\Wallet::where('consumer_id', $voucher->redeemed_by)->first();

            if ($redeemerWallet) {
                $redemptionTransaction = \App\Models\Transaction::where('wallet_id', $redeemerWallet->id)
                    ->where('detail', 'LIKE', '%' . $voucher->code . '%')
                    ->where('type', 'credit')
                    ->first();
            }
        }

        // Get payment gateway details from order
        $paymentDetails = null;
        if ($voucher->order) {
            $paymentDetails = [
                'method' => $voucher->order->payment_method,
                'status' => $voucher->order->payment_status,
                'total' => $voucher->order->total,
                'currency' => $voucher->order->currency,
                'currency_symbol' => $voucher->order->currency_symbol,
                'exchange_rate' => $voucher->order->exchange_rate,
                'order_status' => $voucher->order->order_status->name ?? 'N/A',
            ];
        }

        return view('admin.vouchers.show', compact(
            'voucher',
            'transactions',
            'redemptionTransaction',
            'paymentDetails'
        ));
    }

    /**
     * Get voucher stats for dashboard or API
     */
    public function stats()
    {
        // Check permission
        $this->authorize('view', Voucher::class);

        $stats = [
            'overview' => [
                'total_vouchers' => Voucher::count(),
                'active_vouchers' => Voucher::where('status', 'active')->count(),
                'redeemed_vouchers' => Voucher::where('status', 'redeemed')->count(),
                'expired_vouchers' => Voucher::whereNotNull('expires_at')
                    ->where('expires_at', '<', now())
                    ->where('status', '!=', 'redeemed')
                    ->count(),
            ],
            'financial' => [
                'total_value' => Voucher::sum('amount'),
                'active_value' => Voucher::where('status', 'active')->sum('amount'),
                'redeemed_value' => Voucher::where('status', 'redeemed')->sum('amount'),
                'outstanding_value' => Voucher::where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', now());
                    })
                    ->sum('amount'),
            ],
            'recent' => [
                'last_7_days' => Voucher::where('created_at', '>=', now()->subDays(7))->count(),
                'last_30_days' => Voucher::where('created_at', '>=', now()->subDays(30))->count(),
                'redeemed_7_days' => Voucher::where('status', 'redeemed')
                    ->where('redeemed_at', '>=', now()->subDays(7))
                    ->count(),
                'redeemed_30_days' => Voucher::where('status', 'redeemed')
                    ->where('redeemed_at', '>=', now()->subDays(30))
                    ->count(),
            ],
            'by_currency' => Voucher::select('currency_code', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->groupBy('currency_code')
                ->get()
                ->mapWithKeys(function($item) {
                    return [$item->currency_code => [
                        'count' => $item->count,
                        'total' => $item->total
                    ]];
                }),
        ];

        return response()->json($stats);
    }
}

