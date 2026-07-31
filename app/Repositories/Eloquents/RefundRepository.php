<?php

namespace App\Repositories\Eloquents;

use Exception;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Product;
use App\Enums\OrderEnum;
use App\Helpers\Helpers;
use App\Enums\PaymentType;
use App\Enums\RequestEnum;
use App\Enums\PaymentStatus;
use App\Enums\WalletPointsDetail;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\WalletPointsTrait;
use App\Events\CreateRefundRequestEvent;
use App\Events\UpdateRefundRequestEvent;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;

class RefundRepository extends BaseRepository
{
    use WalletPointsTrait;

    protected $order;
    protected $product;

    protected $fieldSearchable = [
        'user.name' => 'like',
        'user.email' => 'like',
        'store.store_name' => 'like',
        'order.order_number' => 'like',
    ];

    public function boot()
    {
        try {

            $this->pushCriteria(app(RequestCriteria::class));

        } catch (ExceptionHandler $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    function model()
    {
        $this->order = new Order();
        $this->product = new Product();
        return Refund::class;
    }

    public function show($id)
    {
        try {

            return $this->model->findOrFail($id);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function getConsumerIdByOrderId($order_id)
    {
        return $this->order->findOrFail($order_id)->pluck('consumer_id')->first();
    }

    public function getOrder($order_id)
    {
        return $this->order->findOrFail($order_id)->first();
    }

    public function isRefundEnable($settings)
    {
        return (bool)$settings['refund']['status'];
    }

    public function isProductCanReturn($product_id)
    {
        return $this->product->where('id', $product_id)->pluck('is_return')->first();
    }

    public function getDeliveredDays($order)
    {
        return now()->diffInDays(Carbon::parse($order->delivered_at)->toDateString());
    }

    public function verifyStatus($order)
    {
        if (($order->payment_status == PaymentStatus::COMPLETED || $order->payment_status == PaymentStatus::COMPLETE || $order->payment_status == PaymentStatus::SUCCESS) &&
            ($order->order_status->name == OrderEnum::DELIVERED || $order->order_status->name == OrderEnum::COLLECTED)) {
            return true;
        }

        throw new Exception("Refund possible for completed payment and delivered order.", 400);
    }

    public function verifyRefund($consumer_id, $request)
    {
        $settings = Helpers::getSettings();

        if ($this->isRefundEnable($settings)) {
            //Log::info('Pass 1');
            if ($this->verifyProductRefundable($request->product_id)) {
                //Log::info('Pass 2');
                 $order = Order::findOrFail($request->order_id);
                if ($order) {
                    //Log::info('Pass 3');
                    if ($this->verifyStatus($order)) {
                        //Log::info('Pass 4');
                        if ($this->verifyDeliveryDays($order, $settings['refund']['refundable_days'])) {
                            //Log::info('Pass 5');
                            if ($this->isNotAlreadyRequest($consumer_id, $request->product_id, $request->order_id)) {
                                //Log::info('Pass 6');
                                if ($this->verifyPaymentType($consumer_id, $request->payment_type)) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }

        throw new Exception('The refund feature is currently not enabled.', 400);
    }

    public function verifyPaymentType($consumer_id, $paymentType)
    {
        // Wallet refunds credit the in-app wallet; all other gateways need a payout account on file
        if ($paymentType === PaymentType::WALLET) {
            return $this->verifyWallet($consumer_id);
        }
        return $this->verifyPaymentAccount($consumer_id);
    }

    public function verifyWallet($consumer_id)
    {
        return $this->getWallet($consumer_id);
    }

    public function verifyPaymentAccount($user_id)
    {
        $paymentAccount = Helpers::getPaymentAccount($user_id);
        if (!$paymentAccount) {
            throw new Exception("Kindly create a payment account before claiming your refund.", 400);
        }

        return $paymentAccount;
    }

    public function isNotAlreadyRequest($consumer_id, $product_id,$order_id)
    {
        if (!$this->model->where('consumer_id', $consumer_id)->
            where('product_id', $product_id)->
            where('order_id', $order_id)->whereNUll('deleted_at')->first()) {
            return true;
        }

        throw new Exception('A refund request for this product has already been submitted.', 400);
    }

    public function verifyDeliveryDays($order, $refundableDays)
    {
        $date = $this->getDeliveredDays($order);
        if ($this->getDeliveredDays($order) <= $refundableDays) {
            return true;
        }

        throw new Exception("Refund are not possible after {$refundableDays} days from delivery.", 400);
    }

    public function getConsumerOrderByProductId($consumer_id, $product_id)
    {
        return $this->order->where('consumer_id', $consumer_id)->whereRelation('products', function($products) use($product_id) {
            $products->Where('product_id', $product_id);
        })->first();
    }

    public function verifyIsPurchaseProduct($consumer_id, $product_id)
    {
        $order = $this->getConsumerOrderByProductId($consumer_id, $product_id);
        if (!$order) {
            throw new Exception('Only purchased products are eligible for refund requests.', 400);
        }

        if (!$order?->sub_orders->isEmpty()) {
            $tempOrder = null;
            foreach($order->sub_orders as $sub_order) {
                foreach($sub_order->products as $product) {
                    if ($product->id == $product_id) {
                        $tempOrder = $sub_order;
                    }
                }
            }

            $order = $tempOrder;
        }

        return $order;
    }

    public function verifyProductRefundable($product_id)
    {
        if (!$this->isProductCanReturn($product_id)) {
            throw new Exception('Refunds are not allowed for this product.', 400);
        }

        return true;
    }

    public function getRefundProductInOrder($order, $product_id)
    {
        foreach($order->products as $product) {
            if ($product->id == $product_id) {
                return $product->pivot;
            }
        }
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {

            $consumer_id = Helpers::getCurrentUserId();
            if ($this->verifyRefund($consumer_id, $request)) {
                $order = Order::findOrFail($request->order_id);
                $product = $this->getRefundProductInOrder($order, $request->product_id);
                $refund = $this->model->create([
                    'product_id' => $product->product_id,
                    'variation_id' => $product->variation_id,
                    'consumer_id' => $consumer_id,
                    'store_id' => Helpers::getStoreIdByProductId($product->product_id),
                    'order_id' => $product->order_id,
                    'amount' => $product->subtotal,
                    'quantity' => $product->quantity,
                    'reason' => $request->reason,
                    'payment_type' => $request->payment_type,
                    'refund_image_id' => $request->refund_image_id
                ]);

                $refund->order_number = $order->order_number;
                $order->products()->updateExistingPivot($request->product_id, [
                    'refund_status' => RequestEnum::PENDING
                ]);

                // Deduct money from wallet balance column only (not non_cashable_balance) when refund is requested
                // Money will only be credited back if admin rejects the refund
                if ($refund->payment_type == PaymentType::WALLET) {
                    $this->debitWalletBalanceOnly($consumer_id, $refund->amount, WalletPointsDetail::REFUND_REQUESTED);
                }

                event(new CreateRefundRequestEvent($refund));

                DB::commit();
                return $refund;
            }

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {

            $refund = $this->model->findOrFail($id);



            // Prepare update data
            $updateData = ['status' => $request['status']];

            // Include reason if provided (rejection reason from admin)
            if (isset($request['reason'])) {
                $updateData['reason'] = $request['reason'];
            }

            $refund->update($updateData);

            $refund = $refund->fresh();

            // Handle APPROVED or COMPLETED status (refund is approved)
            if ($refund->status == RequestEnum::COMPLETED || $refund->status == RequestEnum::APPROVED) {

                // ── Safety guard: never refund more than what was originally paid ──
                // Read the historical subtotal from order_products (frozen at order time).
                // This prevents an inflated product price from leaking into the refund amount.
                if (!empty($refund->order_id) && !empty($refund->product_id)) {
                    $originalPaid = DB::table('order_products')
                        ->where('order_id', $refund->order_id)
                        ->where('product_id', $refund->product_id)
                        ->whereNull('deleted_at')
                        ->value('subtotal');

                    if ($originalPaid !== null && $refund->amount > (float) $originalPaid) {
                        DB::rollback();
                        throw new Exception(
                            "Refund amount ({$refund->amount}) cannot exceed what the customer originally paid ({$originalPaid}).",
                            400
                        );
                    }
                }


                // Only restock when refund is tied to a specific product/variation
                if (!empty($refund->product_id)) {
                    if (!empty($refund->variation_id)) {
                        if (\App\Models\Variation::whereKey($refund->variation_id)->exists()) {
                            Helpers::incrementVariationQuantity($refund->variation_id, $refund->quantity);
                        }
                    } else {
                        if (\App\Models\Product::whereKey($refund->product_id)->exists()) {
                            Helpers::incrementProductQuantity($refund->product_id, $refund->quantity);
                        }
                    }
                }

                // For wallet payment type, the money was already deducted when the refund was requested
                // So we don't need to credit it here on completion - it stays deducted as the refund is approved
                // For other payment types (bank, paypal), the refund will be processed through those channels

                $refund->is_used = true;
                $refund->save();
            }

            // Handle REJECTED status - credit money back to wallet
            if ($refund->status == RequestEnum::REJECTED) {


                // Only credit back if payment type was wallet (money was deducted on request)
                if ($refund->payment_type == PaymentType::WALLET) {
                    $this->creditWallet($refund->consumer_id, $refund->amount, WalletPointsDetail::REFUND_REJECTED_CREDIT);
                }
            }

            // Update order product pivot if applicable
            if (!empty($refund->order_id)) {
                $order = Order::findOrFail($refund->order_id);
                $refund->order_number = $order->order_number;
                if (!empty($refund->product_id)) {
                    $order->products()->updateExistingPivot($refund->product_id, [
                        'refund_status' => $refund->status
                    ]);
                }
            }

            DB::commit();

            // Set total pending refunds as a custom attribute AFTER commit (not saved to DB)
            $refund->setAttribute('total_pending_refunds', $this->model->where('status', RequestEnum::PENDING)->count());

            \Log::channel('single')->info('[RefundRepository] UpdateRefundRequestEvent firing', [
                'refund_id' => $refund->id,
                'status'    => $refund->status,
                'trace'     => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10))
                    ->map(fn($f) => ($f['class'] ?? '') . '::' . ($f['function'] ?? '') . ' L' . ($f['line'] ?? ''))
                    ->implode(' → '),
            ]);

            event(new UpdateRefundRequestEvent($refund));

            return $refund;

        } catch (Exception $e){

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function destroy($id)
    {
        try {

            return $this->model->findOrFail($id)->destroy($id);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }
}
