<?php

namespace App\Enums;

enum PaymentType:string {
  const WALLET = 'wallet';
  const BANK = 'bank';
  const PAYPAL = 'paypal';
  const YOCO = 'yoco';
  const PAYFAST = 'payfast';
  const PESE = 'pese';
  const DPO_ZAMBIA = 'dpo_zambia';
}
