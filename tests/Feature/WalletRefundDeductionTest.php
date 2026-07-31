<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Enums\PaymentType;
use App\Enums\RequestEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * Test to verify wallet refund deduction behavior
 *
 * CRITICAL: Refunds should ONLY deduct from balance column, NOT from non_cashable_balance
 */
class WalletRefundDeductionTest extends TestCase
{
    /**
     * Test that refund deducts ONLY from balance column
     *
     * @return void
     */
    public function test_refund_deducts_only_from_balance_column()
    {
        // This test verifies that when a customer requests a wallet refund,
        // the money is deducted ONLY from the balance column and NOT from non_cashable_balance

        $this->markTestIncomplete(
            'Manual test required: Create a customer with balance=100, non_cashable_balance=50. ' .
            'Request a refund for $60 with payment_type=wallet. ' .
            'Expected: Should fail because balance (100) < 60 is false, but we need balance >= amount. ' .
            'If balance was 40 and non_cashable_balance was 50, requesting $60 refund should FAIL.'
        );
    }

    /**
     * Test that refund with insufficient balance column fails
     *
     * @return void
     */
    public function test_refund_fails_when_balance_insufficient_even_with_non_cashable()
    {
        // Customer has $30 in balance, $50 in non_cashable_balance
        // Requests $50 refund
        // Should FAIL even though total is $80

        $this->markTestIncomplete(
            'Manual test required: Create wallet with balance=30, non_cashable_balance=50. ' .
            'Request $50 wallet refund. ' .
            'Expected: Should fail with error "The wallet balance is not sufficient for this refund request"'
        );
    }

    /**
     * Test that refund rejection credits back to balance only
     *
     * @return void
     */
    public function test_refund_rejection_credits_back_to_balance_only()
    {
        // Customer has $100 in balance, $20 in non_cashable
        // Requests $50 refund -> balance becomes $50
        // Admin rejects
        // balance should return to $100, non_cashable should stay $20

        $this->markTestIncomplete(
            'Manual test required: Create wallet with balance=100, non_cashable_balance=20. ' .
            'Request $50 wallet refund (balance becomes 50). ' .
            'Admin rejects refund. ' .
            'Expected: balance=100, non_cashable_balance=20'
        );
    }
}

