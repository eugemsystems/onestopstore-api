<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\CommissionHistory;
use App\Models\WithdrawRequest;
use App\Models\Store;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorDashboardController extends Controller
{
    /**
     * Get current vendor's store
     */
    protected function getVendorStore()
    {
        $user = Auth::user();

        if (!$user->hasRole('vendor')) {
            abort(403, 'Unauthorized. Vendor role required.');
        }

        // ✅ FIX: Eager load vendor and vendor_wallet relationships
        // This ensures wallet balance is available throughout the controller
        $store = Store::where('vendor_id', $user->id)
            ->where('is_approved', 1)
            ->with(['vendor.vendor_wallet'])
            ->first();

        if (!$store) {
            abort(403, 'No approved store found for this vendor.');
        }

        // ✅ FIX: Ensure vendor wallet exists (create if missing)
        if ($store->vendor && !$store->vendor->vendor_wallet) {
            \App\Models\VendorWallet::create(['vendor_id' => $store->vendor_id]);
            $store->load('vendor.vendor_wallet'); // Reload the relationship
        }

        return $store;
    }

    /**
     * Vendor Dashboard
     */
    public function index()
    {
        $store = $this->getVendorStore();

        // Get vendor's product IDs
        $vendorProductIds = Product::where('store_id', $store->id)->pluck('id');

        // Get statistics
        $stats = [
            'total_products' => Product::where('store_id', $store->id)->count(),
            'active_products' => Product::where('store_id', $store->id)->where('status', 1)->where('is_approved', 1)->count(),
            'pending_products' => Product::where('store_id', $store->id)->where('is_approved', 0)->count(),

            // Orders containing vendor's products
            'total_orders' => Order::whereHas('products', function($query) use ($vendorProductIds) {
                $query->whereIn('products.id', $vendorProductIds);
            })->count(),
            'pending_orders' => Order::whereHas('products', function($query) use ($vendorProductIds) {
                $query->whereIn('products.id', $vendorProductIds);
            })->whereIn('order_status_id', [1, 2, 3])->count(),
            'completed_orders' => Order::whereHas('products', function($query) use ($vendorProductIds) {
                $query->whereIn('products.id', $vendorProductIds);
            })->where('order_status_id', 5)->count(),

            'total_earnings' => CommissionHistory::where('store_id', $store->id)->sum('vendor_commission'),
            'this_month_earnings' => CommissionHistory::where('store_id', $store->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('vendor_commission'),

            'wallet_balance' => $store->vendor->vendor_wallet->balance ?? 0,
            'pending_withdrawals' => WithdrawRequest::where('vendor_id', $store->vendor_id)
                ->where('status', 'pending')
                ->sum('amount'),
        ];

        // Get recent orders containing vendor's products (last 10)
        $recentOrders = Order::whereHas('products', function($query) use ($vendorProductIds) {
                $query->whereIn('products.id', $vendorProductIds);
            })
            ->with(['consumer', 'order_status', 'products' => function($query) use ($vendorProductIds) {
                // Only load vendor's products in the relationship
                $query->whereIn('products.id', $vendorProductIds);
            }])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get monthly earnings for chart (last 12 months)
        $monthlyEarnings = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $earnings = CommissionHistory::where('store_id', $store->id)
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('vendor_commission');

            $monthlyEarnings[] = [
                'month' => $month->format('M Y'),
                'earnings' => $earnings,
            ];
        }

        return view('admin.vendor.dashboard', compact('store', 'stats', 'recentOrders', 'monthlyEarnings'));
    }

    /**
     * Vendor Products
     */
    public function products(Request $request)
    {
        $store = $this->getVendorStore();

        $query = Product::where('store_id', $store->id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by approval status
        if ($request->filled('is_approved')) {
            $query->where('is_approved', $request->is_approved);
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total' => Product::where('store_id', $store->id)->count(),
            'active' => Product::where('store_id', $store->id)->where('status', 1)->where('is_approved', 1)->count(),
            'pending_approval' => Product::where('store_id', $store->id)->where('is_approved', 0)->count(),
            'inactive' => Product::where('store_id', $store->id)->where('status', 0)->count(),
        ];

        return view('admin.vendor.products', compact('store', 'products', 'stats'));
    }

    /**
     * Vendor Orders
     */
    public function orders(Request $request)
    {
        $store = $this->getVendorStore();

        // Get vendor's product IDs
        $vendorProductIds = Product::where('store_id', $store->id)->pluck('id');

        // Query orders containing vendor's products
        $query = Order::whereHas('products', function($q) use ($vendorProductIds) {
                $q->whereIn('products.id', $vendorProductIds);
            })
            ->with(['consumer', 'order_status', 'products' => function($q) use ($vendorProductIds) {
                // Only load vendor's products in the relationship
                $q->whereIn('products.id', $vendorProductIds);
            }]);

        // Filter by status
        if ($request->filled('order_status_id')) {
            $query->where('order_status_id', $request->order_status_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Search by order number
        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Get commission for each order (vendor's commission only)
        $commissions = CommissionHistory::where('store_id', $store->id)
            ->whereIn('order_id', $orders->pluck('id'))
            ->pluck('vendor_commission', 'order_id');

        $stats = [
            'total_orders' => Order::whereHas('products', function($q) use ($vendorProductIds) {
                $q->whereIn('products.id', $vendorProductIds);
            })->count(),
            'pending' => Order::whereHas('products', function($q) use ($vendorProductIds) {
                $q->whereIn('products.id', $vendorProductIds);
            })->whereIn('order_status_id', [1, 2, 3])->count(),
            'completed' => Order::whereHas('products', function($q) use ($vendorProductIds) {
                $q->whereIn('products.id', $vendorProductIds);
            })->where('order_status_id', 5)->count(),
            'cancelled' => Order::whereHas('products', function($q) use ($vendorProductIds) {
                $q->whereIn('products.id', $vendorProductIds);
            })->where('order_status_id', 6)->count(),
        ];

        return view('admin.vendor.orders', compact('store', 'orders', 'commissions', 'stats'));
    }

    /**
     * Vendor Commissions
     */
    public function commissions(Request $request)
    {
        $store = $this->getVendorStore();

        $query = CommissionHistory::where('store_id', $store->id)
            ->with([
                'order',
                'items' => function($query) {
                    $query->with(['product', 'category']);
                }
            ]);

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $commissions = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total_earnings' => CommissionHistory::where('store_id', $store->id)->sum('vendor_commission'),
            'this_month' => CommissionHistory::where('store_id', $store->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('vendor_commission'),
            'last_month' => CommissionHistory::where('store_id', $store->id)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->sum('vendor_commission'),
            'total_orders' => CommissionHistory::where('store_id', $store->id)->count(),
        ];

        return view('admin.vendor.commissions', compact('store', 'commissions', 'stats'));
    }

    /**
     * Vendor Withdrawals
     */
    public function withdrawals(Request $request)
    {
        $store = $this->getVendorStore();

        $query = WithdrawRequest::where('vendor_id', $store->vendor_id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'wallet_balance' => $store->vendor->vendor_wallet->balance ?? 0,
            'pending_amount' => WithdrawRequest::where('vendor_id', $store->vendor_id)->where('status', 'pending')->sum('amount'),
            'approved_amount' => WithdrawRequest::where('vendor_id', $store->vendor_id)->where('status', 'approved')->sum('amount'),
            'rejected_amount' => WithdrawRequest::where('vendor_id', $store->vendor_id)->where('status', 'rejected')->sum('amount'),
        ];

        $settings = Helpers::getSettings();
        $minWithdrawAmount = $settings['vendor_commissions']['min_withdraw_amount'] ?? 500;

        // Load vendor's saved payment accounts
        $paymentAccounts = \App\Models\PaymentAccount::where('user_id', $store->vendor_id)
            ->where('status', 1)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.vendor.withdrawals', compact('store', 'withdrawals', 'stats', 'minWithdrawAmount', 'paymentAccounts'));
    }

    /**
     * Create Withdrawal Request
     */
    public function createWithdrawal(Request $request)
    {
        $store = $this->getVendorStore();

        $settings = Helpers::getSettings();
        $minWithdrawAmount = $settings['vendor_commissions']['min_withdraw_amount'] ?? 500;
        $walletBalance = $store->vendor->vendor_wallet->balance ?? 0;

        $request->validate([
            'amount' => "required|numeric|min:{$minWithdrawAmount}|max:{$walletBalance}",
            'message' => 'nullable|string|max:500',
            'payment_type' => 'required|string|in:Bank,Mobile Money',
            'payment_account_id' => 'nullable|exists:payment_accounts,id',
            // Bank transfer fields (required only if payment_account_id is NOT provided and payment_type is Bank)
            'bank_name' => [
                'nullable',
                function($attribute, $value, $fail) use ($request) {
                    if ($request->payment_type === 'Bank' && !$request->filled('payment_account_id') && !$value) {
                        $fail('The bank name field is required when not using a saved account.');
                    }
                }
            ],
            'bank_holder_name' => [
                'nullable',
                function($attribute, $value, $fail) use ($request) {
                    if ($request->payment_type === 'Bank' && !$request->filled('payment_account_id') && !$value) {
                        $fail('The bank holder name field is required when not using a saved account.');
                    }
                }
            ],
            'bank_account_no' => [
                'nullable',
                function($attribute, $value, $fail) use ($request) {
                    if ($request->payment_type === 'Bank' && !$request->filled('payment_account_id') && !$value) {
                        $fail('The bank account number field is required when not using a saved account.');
                    }
                }
            ],
            'swift' => 'nullable|string|max:50',
            'ifsc' => 'nullable|string|max:50',
            // Mobile money fields (required only if payment_account_id is NOT provided and payment_type is Mobile Money)
            'mobile_money_provider' => [
                'nullable',
                function($attribute, $value, $fail) use ($request) {
                    if ($request->payment_type === 'Mobile Money' && !$request->filled('payment_account_id') && !$value) {
                        $fail('The mobile money provider field is required when not using a saved account.');
                    }
                }
            ],
            'mobile_money_number' => [
                'nullable',
                function($attribute, $value, $fail) use ($request) {
                    if ($request->payment_type === 'Mobile Money' && !$request->filled('payment_account_id') && !$value) {
                        $fail('The mobile money number field is required when not using a saved account.');
                    }
                }
            ],
            'mobile_money_name' => [
                'nullable',
                function($attribute, $value, $fail) use ($request) {
                    if ($request->payment_type === 'Mobile Money' && !$request->filled('payment_account_id') && !$value) {
                        $fail('The mobile money account name field is required when not using a saved account.');
                    }
                }
            ],
            'save_payment_details' => 'nullable|boolean',
        ]);

        // If using existing payment account
        if ($request->filled('payment_account_id')) {
            $paymentAccount = \App\Models\PaymentAccount::where('id', $request->payment_account_id)
                ->where('user_id', $store->vendor_id)
                ->first();

            if (!$paymentAccount) {
                return redirect()->back()->with('error', 'Invalid payment account selected.');
            }

            $paymentDetails = [
                'payment_account_id' => $paymentAccount->id,
                'bank_name' => $paymentAccount->bank_name,
                'bank_holder_name' => $paymentAccount->bank_holder_name,
                'bank_account_no' => $paymentAccount->bank_account_no,
                'swift' => $paymentAccount->swift,
                'ifsc' => $paymentAccount->ifsc,
            ];
        } else {
            // Create new payment account if save_payment_details is checked
            if ($request->boolean('save_payment_details')) {
                $paymentAccount = \App\Models\PaymentAccount::create([
                    'user_id' => $store->vendor_id,
                    'bank_name' => $request->payment_type === 'Bank' ? $request->bank_name : $request->mobile_money_provider,
                    'bank_holder_name' => $request->payment_type === 'Bank' ? $request->bank_holder_name : $request->mobile_money_name,
                    'bank_account_no' => $request->payment_type === 'Bank' ? $request->bank_account_no : $request->mobile_money_number,
                    'swift' => $request->swift,
                    'ifsc' => $request->ifsc,
                    'is_default' => 0,
                    'status' => 1,
                ]);

                $paymentDetails = [
                    'payment_account_id' => $paymentAccount->id,
                    'bank_name' => $paymentAccount->bank_name,
                    'bank_holder_name' => $paymentAccount->bank_holder_name,
                    'bank_account_no' => $paymentAccount->bank_account_no,
                    'swift' => $paymentAccount->swift,
                    'ifsc' => $paymentAccount->ifsc,
                ];
            } else {
                // Use temporary payment details (not saved)
                $paymentDetails = [
                    'payment_account_id' => null,
                    'bank_name' => $request->payment_type === 'Bank' ? $request->bank_name : $request->mobile_money_provider,
                    'bank_holder_name' => $request->payment_type === 'Bank' ? $request->bank_holder_name : $request->mobile_money_name,
                    'bank_account_no' => $request->payment_type === 'Bank' ? $request->bank_account_no : $request->mobile_money_number,
                    'swift' => $request->swift,
                    'ifsc' => $request->ifsc,
                ];
            }
        }

        WithdrawRequest::create([
            'vendor_id' => $store->vendor_id,
            'amount' => $request->amount,
            'message' => $request->message,
            'payment_type' => $request->payment_type,
            'payment_details' => json_encode($paymentDetails),
            'status' => 'pending',
            'is_used' => false,
        ]);

        return redirect()->route('admin.vendor.withdrawals.index')
            ->with('success', 'Withdrawal request submitted successfully! You will be notified once processed.');
    }

    /**
     * Export vendor commissions to CSV
     */
    public function exportCommissions(Request $request)
    {
        $store = $this->getVendorStore();

        $query = CommissionHistory::where('store_id', $store->id)->with(['order']);

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $commissions = $query->orderBy('created_at', 'desc')->get();

        $filename = 'my_commissions_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($commissions) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Order Number', 'Vendor Commission', 'Admin Commission', 'Total Amount', 'Date']);

            foreach ($commissions as $commission) {
                fputcsv($file, [
                    $commission->order->order_number ?? 'N/A',
                    number_format($commission->vendor_commission, 2),
                    number_format($commission->admin_commission, 2),
                    number_format($commission->vendor_commission + $commission->admin_commission, 2),
                    $commission->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

