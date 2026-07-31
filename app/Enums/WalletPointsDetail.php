<?php

namespace App\Enums;

enum WalletPointsDetail:string {
  const SIGN_UP_BONUS = 'Welcome! Bonus credited.';
  const REWARD = 'Reward Points for placing an order.';
  const REFUND = 'Amount Returned.';
  const REJECTED = 'Request Not Approved.';
  const REFUND_REJECTED = 'Refund rejected - amount credited back.';
  const ADMIN_CREDIT = 'Admin has credited the balance.';
  const ADMIN_DEBIT = 'Admin has debited the balance.';
  const WALLET_ORDER= "Wallet amount successfully debited for Order";
  const POINTS_ORDER= "Point amount successfully debited for Order";
  const COMMISSION = 'Admin has sended a commission';
  const WITHDRAW = 'Balance Withdrawn Requested';
  const WITHDRAWAL_APPROVED = 'Withdrawal approved - balance debited.';
  const REFUND_REQUESTED = 'Refund requested - amount deducted from balance.';
  const REFUND_REJECTED_CREDIT = 'Refund rejected - amount credited back to balance.';
  const OUT_OF_STOCK_CREDIT = 'Store credit for out of stock item';
}
