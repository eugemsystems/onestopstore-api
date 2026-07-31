<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use App\Helpers\Helpers;

class VoucherController extends Controller
{
    /**
     * Get user's purchased vouchers (optimized - only necessary fields)
     */
    public function myVouchers(Request $request)
    {
        try {
            $userId = Helpers::getCurrentUserId();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $query = Voucher::where('purchased_by', $userId)
                ->with([
                    'product:id,name,price,is_gift_card'
                ])
                ->select([
                    'id',
                    'code',
                    'amount',
                    'currency_code',
                    'product_id',
                    'order_id',
                    'status',
                    'redeemed_at',
                    'expires_at',
                    'created_at'
                ])
                ->orderBy('created_at', 'desc');

            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $vouchers = $query->get();

            return response()->json([
                'success' => true,
                'data' => ['vouchers' => $vouchers]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vouchers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Redeem a voucher
     */
    public function redeemVoucher(Request $request)
    {
        try {
            $request->validate(['code' => 'required|string']);

            $userId = Helpers::getCurrentUserId();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $user = \App\Models\User::findOrFail($userId);
            $voucher = VoucherService::redeemVoucher($request->code, $user);

            return response()->json([
                'success' => true,
                'message' => 'Voucher redeemed successfully! ' . $voucher->currency_code . ' ' . number_format($voucher->amount, 2) . ' has been added to your wallet.',
                'data' => [
                    'voucher' => $voucher,
                    'wallet_balance' => $user->wallet?->balance ?? 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Check if a voucher code is valid
     */
    public function checkVoucher(Request $request)
    {
        try {
            $request->validate(['code' => 'required|string']);

            $result = VoucherService::checkVoucher($request->code);

            return response()->json([
                'success' => $result['valid'],
                'message' => $result['message'],
                'data' => isset($result['voucher']) ? ['voucher' => $result['voucher']] : null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check voucher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's redeemed vouchers
     */
    public function redeemedVouchers(Request $request)
    {
        try {
            $userId = Helpers::getCurrentUserId();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $user = \App\Models\User::findOrFail($userId);
            $vouchers = VoucherService::getUserRedeemedVouchers($user);

            return response()->json([
                'success' => true,
                'data' => ['vouchers' => $vouchers]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch redeemed vouchers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend voucher email
     */
    public function resendEmail(Request $request, $voucherId)
    {
        try {
            $userId = Helpers::getCurrentUserId();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Find voucher and verify ownership
            $voucher = Voucher::where('id', $voucherId)
                ->where('purchased_by', $userId)
                ->with('order')
                ->first();

            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher not found or you do not have permission to access it'
                ], 404);
            }

            // Get all vouchers from the same order
            $orderVouchers = Voucher::where('order_id', $voucher->order_id)->get();

            // Send email
            $user = \App\Models\User::findOrFail($userId);
            \Illuminate\Support\Facades\Mail::to($user->email)
                ->send(new \App\Mail\GiftVoucherMail($voucher->order, $orderVouchers));

            return response()->json([
                'success' => true,
                'message' => 'Voucher codes have been sent to your email: ' . $user->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend email: ' . $e->getMessage()
            ], 500);
        }
    }
}

