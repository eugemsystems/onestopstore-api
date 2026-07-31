<?php

namespace App\Http\Controllers;

use Exception;
use App\Helpers\Helpers;
use App\Http\Traits\WalletPointsTrait;
use App\Http\Requests\WalletPointsRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Http\Requests\CreditDebitWalletRequest;
use App\Repositories\Eloquents\WalletRepository;
use OpenApi\Annotations as OA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Refund;
use App\Enums\RequestEnum;
use App\Enums\PaymentType;
use App\Events\CreateRefundRequestEvent;

/**
 * @OA\Tag(name="Wallet", description="Consumer wallet management and transactions")
 */
class WalletController extends Controller
{
    use WalletPointsTrait;

    public $repository;

    public function __construct(WalletRepository $repository)
    {
        return $this->repository = $repository;
    }

    /**
     * Display a Consumer Wallet Transactions.
     *
     * @OA\Get(
     *   path="/api/wallet/consumer",
     *   tags={"Wallet"},
     *   summary="Get consumer wallet transactions",
     *   description="Retrieve wallet balance and transaction history for authenticated consumer",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="consumer_id",
     *     in="query",
     *     description="Consumer ID (optional, defaults to authenticated user)",
     *     required=false,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="paginate",
     *     in="query",
     *     description="Number of transactions per page",
     *     required=false,
     *     @OA\Schema(type="integer", default=15)
     *   ),
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Search transaction type",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="start_date",
     *     in="query",
     *     description="Filter transactions from date (Y-m-d)",
     *     required=false,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="end_date",
     *     in="query",
     *     description="Filter transactions to date (Y-m-d)",
     *     required=false,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Wallet data with transactions",
     *     @OA\JsonContent(
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="consumer_id", type="integer", example=5),
     *       @OA\Property(property="balance", type="number", format="float", example=150.50),
     *       @OA\Property(property="transactions", type="object",
     *         @OA\Property(property="data", type="array",
     *           @OA\Items(
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="amount", type="number", format="float", example=50.00),
     *             @OA\Property(property="type", type="string", example="credit"),
     *             @OA\Property(property="detail", type="string", example="Order refund"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *           )
     *         ),
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="last_page", type="integer", example=5)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(WalletPointsRequest $request)
    {
        try {

            return $this->filter($this->repository, $request);

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    // Consumer-safe wallet summary (no policy gate) – uses current user via filter()
    public function indexSelf(Request $request)
    {
        try {
            return $this->filter($this->repository, $request);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Credit Balance to Consumer Wallet.
     *
     * @OA\Post(
     *   path="/api/credit/wallet",
     *   tags={"Wallet"},
     *   summary="Credit wallet",
     *   description="Add funds to consumer wallet (Admin only)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"consumer_id", "balance"},
     *       @OA\Property(property="consumer_id", type="integer", example=5),
     *       @OA\Property(property="balance", type="number", format="float", example=100.00),
     *       @OA\Property(property="type", type="string", example="credit"),
     *       @OA\Property(property="detail", type="string", example="Admin credit")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Wallet credited successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Wallet credited successfully"),
     *       @OA\Property(property="balance", type="number", format="float", example=250.50)
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function credit(CreditDebitWalletRequest $request)
    {
        return $this->repository->credit($request);
    }

    /**
     * Debit Balance from Consumer Wallet.
     *
     * @OA\Post(
     *   path="/api/debit/wallet",
     *   tags={"Wallet"},
     *   summary="Debit wallet",
     *   description="Deduct funds from consumer wallet (Admin only)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"consumer_id", "balance"},
     *       @OA\Property(property="consumer_id", type="integer", example=5),
     *       @OA\Property(property="balance", type="number", format="float", example=50.00),
     *       @OA\Property(property="type", type="string", example="debit"),
     *       @OA\Property(property="detail", type="string", example="Purchase deduction")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Wallet debited successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Wallet debited successfully"),
     *       @OA\Property(property="balance", type="number", format="float", example=100.50)
     *     )
     *   ),
     *   @OA\Response(response=400, description="Insufficient balance"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function debit(CreditDebitWalletRequest $request)
    {
        return $this->repository->debit($request);
    }

    /**
     * Request full wallet refund.
     *
     * @OA\Post(
     *   path="/api/wallet/refund-all",
     *   tags={"Wallet"},
     *   summary="Request wallet refund",
     *   description="Consumer requests refund of entire wallet balance",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Refund request created",
     *     @OA\JsonContent(
     *       @OA\Property(property="wallet", type="object",
     *         @OA\Property(property="balance", type="number", format="float", example=0.00)
     *       ),
     *       @OA\Property(property="refund", type="object",
     *         @OA\Property(property="id", type="integer", example=15),
     *         @OA\Property(property="amount", type="number", format="float", example=150.00),
     *         @OA\Property(property="status", type="string", example="pending")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=400, description="Wallet balance is zero"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function refundAll()
    {
        try {
            $consumerId = \App\Helpers\Helpers::getCurrentUserId();
            $wallet = $this->getWallet($consumerId);
            $balance = (float) ($wallet->balance ?? 0);
            if ($balance <= 0) {
                return response()->json(['message' => 'Wallet balance is zero'], 400);
            }

            DB::beginTransaction();

            // 1) Debit full wallet balance (balance column only, not non_cashable_balance)
            $debited = $this->debitWalletBalanceOnly($consumerId, $balance, 'Wallet Refund Request');

            // 2) Create a Refund record so admin can process it from the Refunds table
            // IMPORTANT: payment_type must be WALLET since money was deducted from wallet
            // This ensures money is credited back if admin rejects the refund
            $refund = Refund::create([
                'consumer_id' => $consumerId,
                'amount'      => $balance,
                'payment_type'=> PaymentType::WALLET, // ✅ Must be WALLET, not BANK
                'status'      => RequestEnum::PENDING,
                'reason'      => 'Wallet full refund request',
                'store_id'    => null,
                'order_id'    => null,
                'product_id'  => null,
                'variation_id'=> null,
                'quantity'    => 1,
            ]);

            event(new CreateRefundRequestEvent($refund));

            if ($debited) {
                $debited->setRelation('transactions', $debited->transactions()->paginate($debited->transactions()->count()));
            }

            DB::commit();

            return response()->json([
                'wallet' => $debited,
                'refund' => $refund->fresh(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new ExceptionHandler($e->getMessage(), (int)$e->getCode());
        }
    }

    public function filter($wallet, $request)
    {
        $consumer_id = $request->consumer_id ?? Helpers::getCurrentUserId();
        $wallet = $wallet->where('consumer_id',$consumer_id)->first();

        if (!$wallet) {
            $wallet = $this->getWallet($request->consumer_id ?? Helpers::getCurrentUserId());
            $wallet = $wallet->fresh();
        }

        $transactions = $wallet->transactions()->where('type', 'LIKE', "%{$request->search}%");
        if ($request->start_date && $request->end_date) {
            $transactions->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $paginate = $request->paginate ?? $wallet->transactions()->count();
        $wallet->setRelation('transactions', $transactions->paginate($paginate));

        return $wallet;
    }
}
