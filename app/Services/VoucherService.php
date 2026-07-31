<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherService
{
    /**
     * Generate vouchers for gift card products in an order
     */
    public static function generateVouchersForOrder(Order $order)
    {
        $vouchers = [];

        foreach ($order->products as $orderProduct) {
            $product = $orderProduct->pivot;
            $productModel = Product::find($orderProduct->id);

            // Check if this is a gift card product
            if ($productModel && $productModel->is_gift_card) {
                $quantity = $product->quantity;

                // Generate one voucher per quantity
                for ($i = 0; $i < $quantity; $i++) {
                    $voucher = self::createVoucher([
                        'amount' => $product->single_price,
                        'currency_code' => $order->currency ?? 'USD',
                        'product_id' => $productModel->id,
                        'order_id' => $order->id,
                        'purchased_by' => $order->consumer_id,
                        'validity_days' => $productModel->voucher_validity_days,
                    ]);

                    $vouchers[] = $voucher;
                }
            }
        }

        // Return as collection for consistency with Laravel conventions
        return collect($vouchers);
    }

    /**
     * Create a single voucher
     */
    public static function createVoucher(array $data)
    {
        $expiresAt = null;
        if (isset($data['validity_days']) && $data['validity_days'] > 0) {
            $expiresAt = now()->addDays($data['validity_days']);
        }

        return Voucher::create([
            'code' => Voucher::generateUniqueCode(),
            'amount' => $data['amount'],
            'currency_code' => $data['currency_code'] ?? 'USD',
            'product_id' => $data['product_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'purchased_by' => $data['purchased_by'] ?? null,
            'status' => 'active',
            'expires_at' => $expiresAt,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Redeem a voucher for a user
     */
    public static function redeemVoucher(string $code, User $user)
    {
        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            throw new \Exception('Invalid voucher code.');
        }

        if (!$voucher->canRedeem()) {
            if ($voucher->isRedeemed()) {
                throw new \Exception('This voucher has already been redeemed.');
            }
            if ($voucher->isExpired()) {
                throw new \Exception('This voucher has expired.');
            }
            throw new \Exception('This voucher cannot be redeemed.');
        }

        DB::beginTransaction();
        try {
            // Redeem voucher (this will create the transaction record)
            $voucher->redeem($user);

            DB::commit();

            return $voucher;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get vouchers for a user (purchased)
     */
    public static function getUserPurchasedVouchers(User $user, $status = null)
    {
        $query = Voucher::where('purchased_by', $user->id)
            ->with(['product', 'order', 'redeemedBy'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Get vouchers redeemed by a user
     */
    public static function getUserRedeemedVouchers(User $user)
    {
        return Voucher::where('redeemed_by', $user->id)
            ->with(['product', 'purchasedBy'])
            ->orderBy('redeemed_at', 'desc')
            ->get();
    }

    /**
     * Check voucher validity
     */
    public static function checkVoucher(string $code)
    {
        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            return [
                'valid' => false,
                'message' => 'Invalid voucher code.',
            ];
        }

        if ($voucher->isRedeemed()) {
            return [
                'valid' => false,
                'message' => 'This voucher has already been redeemed.',
                'voucher' => $voucher,
            ];
        }

        if ($voucher->isExpired()) {
            return [
                'valid' => false,
                'message' => 'This voucher has expired.',
                'voucher' => $voucher,
            ];
        }

        if (!$voucher->isActive()) {
            return [
                'valid' => false,
                'message' => 'This voucher is not active.',
                'voucher' => $voucher,
            ];
        }

        return [
            'valid' => true,
            'message' => 'Voucher is valid and ready to be redeemed.',
            'voucher' => $voucher,
        ];
    }
}

