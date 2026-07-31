<?php

namespace App\Http\Controllers;

use App\Models\DpoZambiaTransaction;
use App\Models\PayfastTransaction;
use App\Models\PesepayTransaction;
use App\Models\YocoTransaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function dpoZambia(Request $request)
    {
        $query = DpoZambiaTransaction::query()->with(['order:id,order_number,consumer_id,payment_status,order_status_id,total,currency']);
        if ($request->order_id) {
            $query->where('order_id', $request->order_id);
        }
        if ($request->company_ref) {
            $query->where('company_ref', $request->company_ref);
        }
        return $query->latest('created_at')->paginate($request->paginate ?? 15);
    }

    public function payfast(Request $request)
    {
        $query = PayfastTransaction::query()->with(['order:id,order_number,consumer_id,payment_status,order_status_id,total,currency']);
        if ($request->order_id) {
            $query->where('order_id', $request->order_id);
        }
        return $query->latest('created_at')->paginate($request->paginate ?? 15);
    }

    public function pesepay(Request $request)
    {
        $query = PesepayTransaction::query()->with(['order:id,order_number,consumer_id,payment_status,order_status_id,total,currency']);
        if ($request->order_id) {
            $query->where('order_id', $request->order_id);
        }
        return $query->latest('created_at')->paginate($request->paginate ?? 15);
    }

    public function yoco(Request $request)
    {
        $query = YocoTransaction::query()->with(['order:id,order_number,consumer_id,payment_status,order_status_id,total,currency']);
        if ($request->order_id) {
            $query->where('order_id', $request->order_id);
        }
        return $query->latest('created_at')->paginate($request->paginate ?? 15);
    }

    private function paginate($query, Request $request)
    {
        // Deprecated: kept for backward-compat if used, but prefer route-specific filtering.
        if ($request->order_id) {
            $query->where('order_id', $request->order_id);
        }
        return $query->latest('created_at')->paginate($request->paginate ?? 15);
    }
}
