<?php

namespace App\Enums;

enum PaymentMethod {
  const COD = 'cod';
  const BANK_TRANSFER = 'bank_transfer';
  const PAYPAL = 'paypal';
//  const STRIPE = 'stripe';
  const PESE = 'pese';
  const PAYFAST = 'payfast';
  const PDO_ZAMBIA = 'pdo_zambia';
  const YOCO = 'yoco';
  const WALLET = 'wallet';

  const ALL_PAYMENT_METHODS = [
      self::PAYFAST,
      self::PDO_ZAMBIA,
      self::PESE,
      self::COD,
      self::BANK_TRANSFER,
      self::PAYPAL,
      self::YOCO,
      self::WALLET,

  ];

}
