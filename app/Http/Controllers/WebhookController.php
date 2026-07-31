<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Eloquents\WebhookRepository;

class WebhookController extends Controller
{
    protected $repository;

    public function __construct(WebhookRepository $repository){
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function paypal(Request $request)
    {
        return $this->repository->paypal($request);
    }

    /**
     * Display a listing of the resource.
     */
    public function pesepay(Request $request)
    {
        return $this->repository->pesepay($request);
    }

    /**
     * Display a listing of the resource.
     */
    public function payfast(Request $request)
    {
        return $this->repository->payfast($request);
    }

    /**
     * DPO Zambia webhook endpoint
     */
    public function dpo(Request $request)
    {
        return $this->repository->dpo($request);
    }

    public function yoco(Request $request)
    {
        return $this->repository->yoco($request);
    }

}
