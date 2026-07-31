<?php

namespace App\Http\Traits;

use Exception;
use App\Models\Point;
use App\Models\Wallet;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Models\VendorWallet;
use App\GraphQL\Exceptions\ExceptionHandler;

trait WalletPointsTrait
{
  use TransactionsTrait;

  // Wallet
  public function getWallet($consumer_id)
  {
    if (Helpers::walletIsEnable()) {
      $roleName = Helpers::getRoleNameByUserId($consumer_id);
      if ($roleName == RoleEnum::CONSUMER) {
        return Wallet::firstOrCreate(['consumer_id' => $consumer_id]);
      }

      throw new ExceptionHandler("user must be ".RoleEnum::CONSUMER, 400);
    }

    throw new ExceptionHandler('Wallet feature currently disabled. Turn it on in Settings > Activation.', 405);
  }

  public function getVendorWallet($vendor_id)
  {
    $roleName = Helpers::getRoleNameByUserId($vendor_id);
    if ($roleName == RoleEnum::VENDOR) {
      return VendorWallet::firstOrCreate(['vendor_id'=> $vendor_id]);
    }
    throw new ExceptionHandler("user must be ".RoleEnum::VENDOR, 400);
  }

  public function getVendorWalletBalance($vendor_id)
  {
    return $this->getVendorWallet($vendor_id)->balance;
  }

  public function verifyWallet($consumer_id, $balance)
  {
    if ($balance > 0.00) {
      $roleName = Helpers::getCurrentRoleName();
      if ($roleName != RoleEnum::VENDOR) {
        if (Helpers::walletIsEnable()) {
          $walletBalance = $this->getWalletBalance($consumer_id);
          if ($walletBalance >= $balance) {
            return true;
          }

          // Return false instead of throwing exception - allows partial wallet payment
          // The order can still proceed with the remaining balance to be paid via payment gateway
          return false;
        }

        // Wallet feature disabled - return false instead of throwing
        return false;
      }

      // Vendors can't use wallet - return false instead of throwing
      return false;
    }

    return false;
  }

  public function getWalletBalance($consumer_id)
  {
    $wallet = $this->getWallet($consumer_id);
    // Return total of both balance and non_cashable_balance
    // Both can be used for purchases (gift vouchers, rewards, etc.)
    return $wallet->balance + $wallet->non_cashable_balance;
  }

  public function creditWallet($consumer_id, $balance, $detail)
  {

    $wallet = $this->getWallet($consumer_id);
    if ($wallet) {
      $oldBalance = $wallet->balance;
      $wallet->increment('balance', $balance);
      $wallet = $wallet->fresh();

    }

    $this->creditTransaction($wallet, $balance, $detail);


    return $wallet;
  }

  public function debitWallet($consumer_id, $balance, $detail)
  {
    $wallet = $this->getWallet($consumer_id);
    if ($wallet) {
      $totalBalance = $wallet->balance + $wallet->non_cashable_balance;

      if ($totalBalance >= $balance) {
        // Deduct from non_cashable_balance first (gift vouchers, rewards)
        $remainingToDeduct = $balance;

        if ($wallet->non_cashable_balance > 0) {
          $deductFromNonCashable = min($wallet->non_cashable_balance, $remainingToDeduct);
          $wallet->decrement('non_cashable_balance', $deductFromNonCashable);
          $remainingToDeduct -= $deductFromNonCashable;
        }

        // Deduct remaining from regular balance
        if ($remainingToDeduct > 0 && $wallet->balance > 0) {
          $wallet->decrement('balance', $remainingToDeduct);
        }

        $this->debitTransaction($wallet, $balance, $detail);

        return $wallet;
      }

      throw new ExceptionHandler('The wallet balance is not sufficient for this order.', 400);
    }
  }

  public function debitWalletBalanceOnly($consumer_id, $balance, $detail)
  {


    $wallet = $this->getWallet($consumer_id);
    if ($wallet) {


      // For refunds, only deduct from the balance column (not non_cashable_balance)
      if ($wallet->balance >= $balance) {
        $oldBalance = $wallet->balance;
        $wallet->decrement('balance', $balance);
        $wallet = $wallet->fresh();


        $this->debitTransaction($wallet, $balance, $detail);

        return $wallet;
      }

      throw new ExceptionHandler('The wallet balance is not sufficient for this refund request.', 400);
    }
  }

  public function creditVendorWallet($vendor_id, $balance, $detail)
  {
    $vendorWallet = $this->getVendorWallet($vendor_id);
    if ($vendorWallet) {
      $vendorWallet->increment('balance', $balance);
    }

    $this->creditVendorTransaction($vendorWallet, $balance, $detail);
    return $vendorWallet;
  }

  public function debitVendorWallet($vendor_id, $balance, $detail)
  {
    $vendorWallet = $this->getVendorWallet($vendor_id);
    if ($vendorWallet) {
      if ($vendorWallet->balance >= $balance) {
        $vendorWallet->decrement('balance', $balance);
        $this->debitVendorTransaction($vendorWallet, $balance, $detail);

        return $vendorWallet;
      }

      throw new ExceptionHandler('The vendor wallet balance is not sufficient for this order.', 400);
    }
  }

  // Points
  public function getPoints($consumer_id)
  {
    if (Helpers::pointIsEnable()) {
      return Point::firstOrCreate(['consumer_id' => $consumer_id]);
    }
    throw new ExceptionHandler('Points feature currently disabled. Turn it on in Settings > Activation.', 405);
  }

  public function getPointAmount($consumer_id)
  {
    return Helpers::formatDecimal($this->getPoints($consumer_id)->balance);
  }

  public function verifyPoints($consumerId, $pointBalance)
  {
    if ($pointBalance > 0.00) {
      $roleName = Helpers::getCurrentRoleName();
      if ($roleName != RoleEnum::VENDOR) {
        if (Helpers::pointIsEnable()) {
          $points = $this->getPointAmount($consumerId);
          $pointBalance = $this->currencyToPoints($pointBalance);
          if (round($points, 2) >= abs($pointBalance)) {
            return true;
          }

          // Return false instead of throwing exception - allows partial points payment
          // The order can still proceed with the remaining balance to be paid via payment gateway
          return false;
        }

        // Points feature disabled - return false instead of throwing
        return false;
      }

      // Vendors can't use points - return false instead of throwing
      return false;
    }

    return false;
  }

  public static function getPointRatio()
  {
    $settings = Helpers::getSettings();
    $pointRatio = $settings['wallet_points']['point_currency_ratio'];
    return $pointRatio == 0 ? 1 : $pointRatio;
  }

  public function pointsToCurrency($points)
  {
    $pointRatio = $this->getPointRatio();
    return Helpers::formatDecimal($points / $pointRatio);
  }

  public function currencyToPoints($currency)
  {
    $pointRatio = $this->getPointRatio();
    return Helpers::formatDecimal($currency * $pointRatio);
  }

  public function creditPoints($consumer_id, $balance, $detail)
  {
    $points = $this->getPoints($consumer_id);
    if ($points) {
      $points->increment('balance', $balance);
    }

    $this->creditTransaction($points, $balance, $detail);
    return $points;
  }


  public function debitPoints($consumer_id, $balance, $detail)
  {
    $points = $this->getPoints($consumer_id);
    if ($points) {
      if ($points->balance >= $balance) {
        $points->decrement('balance', $balance);
        $this->debitTransaction($points, $balance, $detail);
        return $points;
      }

      throw new ExceptionHandler('The points is not sufficient for this order.', 400);
    }
  }
}
