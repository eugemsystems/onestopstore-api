<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawRequest;
use App\Models\User;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\WithdrawalApprovedMail;
use App\Http\Traits\WalletPointsTrait;
use App\Enums\WalletPointsDetail;

class AdminWithdrawRequestController extends Controller
{
    use WalletPointsTrait;

    /**
     * Display a listing of withdrawal requests
     */
    public function index(Request $request)
    {
        $query = WithdrawRequest::with(['user.store']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by amount range
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        $withdrawals = $query->paginate(20)->withQueryString();

        // OPTIMIZED: Get all vendors for filter dropdown - eager load roles to prevent N+1
        $vendors = User::with('roles')
            ->role('vendor')
            ->whereHas('store', function($q) {
                $q->where('is_approved', 1);
            })
            ->orderBy('name')
            ->get();

        // Calculate statistics
        $stats = [
            'total_pending' => WithdrawRequest::where('status', 'pending')->sum('amount'),
            'total_approved' => WithdrawRequest::where('status', 'approved')->sum('amount'),
            'total_rejected' => WithdrawRequest::where('status', 'rejected')->sum('amount'),
            'pending_count' => WithdrawRequest::where('status', 'pending')->count(),
        ];

        return view('admin.withdrawals.index', compact('withdrawals', 'vendors', 'stats'));
    }

    /**
     * Show withdrawal request details
     */
    public function show($id)
    {
        $withdrawal = WithdrawRequest::with(['user.store', 'user.vendor_wallet'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'withdrawal' => $withdrawal,
            'vendor_wallet_balance' => $withdrawal->user->vendor_wallet->balance ?? 0,
        ]);
    }

    /**
     * Approve withdrawal request
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'payment_reference' => 'nullable|string|max:255',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $withdrawal = WithdrawRequest::with('user')->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending withdrawals can be approved.');
        }

        // Debit vendor wallet
        try {
            $this->debitVendorWallet(
                $withdrawal->vendor_id,
                $withdrawal->amount,
                WalletPointsDetail::WITHDRAWAL_APPROVED
            );

            $withdrawal->update([
                'status' => 'approved',
                'is_used' => true,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'payment_reference' => $request->payment_reference,
                'admin_notes' => $request->admin_notes,
            ]);

            // Send email notification to vendor
            try {
                Mail::to($withdrawal->user->email)->send(new WithdrawalApprovedMail($withdrawal));
            } catch (\Exception $emailException) {
                \Log::error("Failed to send withdrawal approval email: " . $emailException->getMessage());
                // Don't fail the approval process if email fails
            }

            return redirect()->route('admin.withdrawals.index')
                ->with('success', 'Withdrawal request approved successfully! Vendor has been notified via email.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to approve withdrawal: ' . $e->getMessage());
        }
    }

    /**
     * Reject withdrawal request
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $withdrawal = WithdrawRequest::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending withdrawals can be rejected.');
        }

        $withdrawal->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
        ]);

        // TODO: Send email notification to vendor

        return redirect()->route('admin.withdrawals.index')
            ->with('success', 'Withdrawal request rejected.');
    }

    /**
     * Export withdrawals to CSV
     */
    public function export(Request $request)
    {
        $query = WithdrawRequest::with(['user.store']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->get();

        $filename = 'withdrawals_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($withdrawals) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'Request ID',
                'Vendor Name',
                'Store Name',
                'Amount',
                'Status',
                'Payment Type',
                'Message',
                'Requested Date',
                'Processed Date',
            ]);

            // CSV Data
            foreach ($withdrawals as $withdrawal) {
                fputcsv($file, [
                    $withdrawal->id,
                    $withdrawal->user->name ?? 'N/A',
                    $withdrawal->user->store->store_name ?? 'N/A',
                    number_format($withdrawal->amount, 2),
                    $withdrawal->status,
                    $withdrawal->payment_type ?? 'N/A',
                    $withdrawal->message ?? '',
                    $withdrawal->created_at->format('Y-m-d H:i:s'),
                    $withdrawal->approved_at ? $withdrawal->approved_at->format('Y-m-d H:i:s') :
                        ($withdrawal->rejected_at ? $withdrawal->rejected_at->format('Y-m-d H:i:s') : 'N/A'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

