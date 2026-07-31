<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Http\Traits\WalletPointsTrait;
use App\Enums\WalletPointsDetail;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AdminWalletController extends Controller
{
    use WalletPointsTrait;

    public function __construct()
    {
        $this->middleware('can:wallet.index')->only(['index', 'listWallets', 'manage']);
        $this->middleware('can:wallet.show')->only(['getUserWallet', 'getTransactions']);
        $this->middleware('can:wallet.edit')->only(['credit', 'debit']);
    }

    /**
     * Display wallet management page
     */
    public function index()
    {
        return view('admin.wallet.index');
    }

    /**
     * Display specific wallet management page
     */
    public function manage($userId)
    {
        // Get user
        $user = User::findOrFail($userId);

        // Get or create wallet
        $wallet = Wallet::firstOrCreate(
            ['consumer_id' => $user->id],
            ['balance' => 0]
        );

        // Get transactions with pagination
        $transactions = Transaction::where('wallet_id', $wallet->id)
            ->whereNotNull('wallet_id')
            ->with(['order', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate transaction statistics
        $stats = [
            'total_credits' => Transaction::where('wallet_id', $wallet->id)
                ->where('type', 'credit')
                ->sum('amount'),
            'total_debits' => Transaction::where('wallet_id', $wallet->id)
                ->where('type', 'debit')
                ->sum('amount'),
            'transaction_count' => Transaction::where('wallet_id', $wallet->id)->count(),
            'last_transaction' => Transaction::where('wallet_id', $wallet->id)
                ->orderBy('created_at', 'desc')
                ->first(),
        ];

        return view('admin.wallet.manage', compact('user', 'wallet', 'transactions', 'stats'));
    }

    /**
     * Display list of all wallets with statistics
     */
    public function listWallets(Request $request)
    {
        // Get filter parameters
        $search = $request->get('search');
        $minBalance = $request->get('min_balance');
        $maxBalance = $request->get('max_balance');
        $sortBy = $request->get('sort_by', 'balance');
        $sortOrder = $request->get('sort_order', 'desc');

        // Build query — include wallets with any balance (regular OR gift card)
        $query = Wallet::with(['consumer' => function($q) {
            $q->select('id', 'name', 'email', 'created_at');
        }])
        ->where(function($q) {
            $q->where('balance', '>', 0)
              ->orWhere('non_cashable_balance', '>', 0);
        });

        // Apply filters
        if ($search) {
            $query->whereHas('consumer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($minBalance !== null && $minBalance !== '') {
            $query->where('balance', '>=', $minBalance);
        }

        if ($maxBalance !== null && $maxBalance !== '') {
            $query->where('balance', '<=', $maxBalance);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $wallets = $query->paginate(20);

        // Calculate statistics
        $totalWalletBalance    = Wallet::sum('balance');
        $totalGiftCardBalance  = Wallet::sum('non_cashable_balance');
        $statistics = [
            'total_wallets'           => Wallet::count(),
            'total_balance'           => $totalWalletBalance,
            'total_gift_card_balance' => $totalGiftCardBalance,
            'total_combined_balance'  => $totalWalletBalance + $totalGiftCardBalance,
            'average_balance'         => Wallet::avg('balance') ?? 0,
            'wallets_with_balance'    => Wallet::where(function($q) {
                $q->where('balance', '>', 0)->orWhere('non_cashable_balance', '>', 0);
            })->count(),
            'zero_balance_wallets'    => Wallet::where('balance', '=', 0)
                                               ->where('non_cashable_balance', '=', 0)->count(),
            'highest_balance'         => Wallet::max('balance') ?? 0,
            'lowest_balance'          => Wallet::where('balance', '>', 0)->min('balance') ?? 0,
        ];

        // Get recent transactions statistics
        $recentTransactions = Transaction::whereNotNull('wallet_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("
                COUNT(*) as transaction_count,
                SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as total_credits,
                SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as total_debits
            ")
            ->first();

        $statistics['recent_transactions'] = $recentTransactions->transaction_count ?? 0;
        $statistics['recent_credits'] = $recentTransactions->total_credits ?? 0;
        $statistics['recent_debits'] = $recentTransactions->total_debits ?? 0;

        return view('admin.wallet.list', compact('wallets', 'statistics'));
    }

    /**
     * Get user wallet balance via AJAX
     */
    public function getUserWallet(Request $request)
    {
        $request->validate([
            'consumer_id' => 'required|exists:users,id'
        ]);

        $consumer = User::findOrFail($request->consumer_id);

        // Get or create wallet
        $wallet = Wallet::firstOrCreate(
            ['consumer_id' => $consumer->id],
            ['balance' => 0]
        );

        $walletBalance   = floatval($wallet->balance ?? 0);
        $giftCardBalance = floatval($wallet->non_cashable_balance ?? 0);

        return response()->json([
            'success'           => true,
            'balance'           => $walletBalance,          // kept for backward compat
            'wallet_balance'    => $walletBalance,
            'gift_card_balance' => $giftCardBalance,
            'combined_balance'  => $walletBalance + $giftCardBalance,
            'consumer_name'     => $consumer->name,
            'consumer_email'    => $consumer->email
        ]);
    }

    /**
     * Get wallet transactions for a user via AJAX
     */
    public function getTransactions(Request $request)
    {

        $request->validate([
            'consumer_id' => 'required|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Get or create wallet for this consumer
        $wallet = Wallet::firstOrCreate(
            ['consumer_id' => $request->consumer_id],
            ['balance' => 0]
        );

        // Query transactions for this wallet
        $query = Transaction::where('wallet_id', $wallet->id)
            ->whereNotNull('wallet_id')
            ->orderBy('created_at', 'desc');

        // Apply date filters if provided
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->paginate(15);

        return response()->json([
            'success' => true,
            'transactions' => $transactions
        ]);
    }

    /**
     * Credit wallet
     */
    public function credit(Request $request)
    {

        $request->validate([
            'consumer_id' => 'required|exists:users,id',
            'balance' => 'required|numeric|min:0.01',
            'remark' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $consumer = User::findOrFail($request->consumer_id);

            // Get or create wallet
            $wallet = Wallet::firstOrCreate(
                ['consumer_id' => $consumer->id],
                ['balance' => 0]
            );

            // Credit wallet
            $detail = $request->remark ?: WalletPointsDetail::ADMIN_CREDIT;
            $this->creditWallet($consumer->id, $request->balance, $detail);

            DB::commit();

            // Audit log
            try {
                ActivityLogger::make()->useLog('wallet')->event('credit')->on($consumer)
                    ->withProperties(['amount' => $request->balance, 'remark' => $detail])
                    ->log("Wallet credited: {$this->getSystemDefaultCurrencySymbol()}{$request->balance} to {$consumer->name} ({$consumer->email}). Remark: {$detail}");
            } catch (\Throwable) {}

            return response()->json([
                'success' => true,
                'message' => "Successfully credited {$this->getSystemDefaultCurrencySymbol()}{$request->balance} to {$consumer->name}'s wallet",
                'new_balance' => $wallet->fresh()->balance
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to credit wallet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debit wallet
     */
    public function debit(Request $request)
    {

        $request->validate([
            'consumer_id' => 'required|exists:users,id',
            'balance' => 'required|numeric|min:0.01',
            'remark' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $consumer = User::findOrFail($request->consumer_id);

            // Get wallet
            $wallet = Wallet::where('consumer_id', $consumer->id)->first();

            if (!$wallet || $wallet->balance < $request->balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance'
                ], 422);
            }

            // Debit wallet
            $detail = $request->remark ?: WalletPointsDetail::ADMIN_DEBIT;
            $this->debitWallet($consumer->id, $request->balance, $detail);

            DB::commit();

            // Audit log
            try {
                ActivityLogger::make()->useLog('wallet')->event('debit')->on($consumer)
                    ->withProperties(['amount' => $request->balance, 'remark' => $detail])
                    ->log("Wallet debited: {$this->getSystemDefaultCurrencySymbol()}{$request->balance} from {$consumer->name} ({$consumer->email}). Remark: {$detail}");
            } catch (\Throwable) {}

            return response()->json([
                'success' => true,
                'message' => "Successfully debited {$this->getSystemDefaultCurrencySymbol()}{$request->balance} from {$consumer->name}'s wallet",
                'new_balance' => $wallet->fresh()->balance
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to debit wallet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system default currency symbol
     */
    private function getSystemDefaultCurrencySymbol()
    {
        return getSettings()?->general?->default_currency?->symbol ?? '$';
    }
}

