<?php

namespace App\Repositories\Eloquents;

use App\Payments\PesePay;
use Exception;
use App\Models\Order;
use App\Payments\PayFast;
use App\Payments\PayPal;
use App\Payments\PdoZambia;
use App\Payments\Yoco;
use App\GraphQL\Exceptions\ExceptionHandler;
use Prettus\Repository\Eloquent\BaseRepository;

class WebhookRepository extends BaseRepository
{
    function model()
    {
        return Order::class;
    }

    public function paypal($request)
    {
        try {

            return PayPal::webhookHandler($request);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function pesepay($request)
    {
        try {

            return PesePay::webhookHandler($request);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function payfast($request)
    {
        try {

            return PayFast::webhookHandler($request);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function dpo($request)
    {
        try {

            return PdoZambia::webhookHandler($request);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function yoco($request)
    {
        try {

            return Yoco::webhookHandler($request);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

}
