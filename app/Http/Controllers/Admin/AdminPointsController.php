<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Point;
use App\Models\Transaction;
use App\Http\Traits\WalletPointsTrait;
use App\Enums\WalletPointsDetail;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AdminPointsController extends Controller
{
    use WalletPointsTrait;

    public function __construct()
    {
        $this->middleware('can:point.index')->only(['index']);
        $this->middleware('can:point.show')->only(['getUserPoints', 'getTransactions']);
        $this->middleware('can:point.edit')->only(['credit', 'debit']);
    }

    /**
     * Display points management page
     */
    public function index()
    {
        return view('admin.points.index');
    }

    /**
     * Get user points balance via AJAX
     */
    public function getUserPoints(Request $request)
    {
        $request->validate([
            'consumer_id' => 'required|exists:users,id'
        ]);

        $consumer = User::findOrFail($request->consumer_id);

        // Get points balance from Point model
        $point = \App\Models\Point::firstOrCreate(
            ['consumer_id' => $consumer->id],
            ['balance' => 0]
        );

        return response()->json([
            'success' => true,
            'balance' => $point->balance ?? 0,
            'consumer_name' => $consumer->name,
            'consumer_email' => $consumer->email
        ]);
    }

    /**
     * Get points transactions for a user via AJAX
     */
    public function getTransactions(Request $request)
    {

        $request->validate([
            'consumer_id' => 'required|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Get or create Point record for this consumer
        $point = Point::firstOrCreate(
            ['consumer_id' => $request->consumer_id],
            ['balance' => 0]
        );

        // Query transactions for this point record
        $query = Transaction::where('point_id', $point->id)
            ->whereNotNull('point_id')
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
     * Credit points
     */
    public function credit(Request $request)
    {

        $request->validate([
            'consumer_id' => 'required|exists:users,id',
            'balance' => 'required|numeric|min:1',
            'remark' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $consumer = User::findOrFail($request->consumer_id);

            // Convert the currency amount to points using the point_currency_ratio
            // If user enters $60, we need to convert it to points based on the system ratio
            $pointsToCredit = $this->currencyToPoints($request->balance);

            // Credit points using the trait method
            $detail = $request->remark ?: WalletPointsDetail::ADMIN_CREDIT;
            $point = $this->creditPoints($consumer->id, $pointsToCredit, $detail);

            DB::commit();

            // Audit log
            try {
                ActivityLogger::make()->useLog('points')->event('credit')->on($consumer)
                    ->withProperties(['points' => $pointsToCredit, 'value' => $request->balance, 'remark' => $detail])
                    ->log("Points credited: {$pointsToCredit} pts (\${$request->balance}) to {$consumer->name} ({$consumer->email}). Remark: {$detail}");
            } catch (\Throwable) {}

            return response()->json([
                'success' => true,
                'message' => "Successfully credited {$pointsToCredit} points (equivalent to \${$request->balance}) to {$consumer->name}",
                'new_balance' => $point->fresh()->balance ?? 0
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to credit points: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debit points
     */
    public function debit(Request $request)
    {

        $request->validate([
            'consumer_id' => 'required|exists:users,id',
            'balance' => 'required|numeric|min:1',
            'remark' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $consumer = User::findOrFail($request->consumer_id);

            // Convert the currency amount to points using the point_currency_ratio
            $pointsToDebit = $this->currencyToPoints($request->balance);

            // Get point record
            $point = \App\Models\Point::firstOrCreate(
                ['consumer_id' => $consumer->id],
                ['balance' => 0]
            );

            if ($point->balance < $pointsToDebit) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient points balance. User has {$point->balance} points but {$pointsToDebit} points (equivalent to \${$request->balance}) is required."
                ], 422);
            }

            // Debit points using the trait method
            $detail = $request->remark ?: WalletPointsDetail::ADMIN_DEBIT;
            $point = $this->debitPoints($consumer->id, $pointsToDebit, $detail);

            DB::commit();

            // Audit log
            try {
                ActivityLogger::make()->useLog('points')->event('debit')->on($consumer)
                    ->withProperties(['points' => $pointsToDebit, 'value' => $request->balance, 'remark' => $detail])
                    ->log("Points debited: {$pointsToDebit} pts (\${$request->balance}) from {$consumer->name} ({$consumer->email}). Remark: {$detail}");
            } catch (\Throwable) {}

            return response()->json([
                'success' => true,
                'message' => "Successfully debited {$pointsToDebit} points (equivalent to \${$request->balance}) from {$consumer->name}",
                'new_balance' => $point->fresh()->balance ?? 0
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to debit points: ' . $e->getMessage()
            ], 500);
        }
    }
}

