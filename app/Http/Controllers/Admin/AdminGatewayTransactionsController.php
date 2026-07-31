<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesepayTransaction;
use App\Models\PayfastTransaction;
use App\Models\DpoZambiaTransaction;
use App\Models\OrderTransaction;
use App\Models\Transaction;
use App\Models\VendorTransaction;
use App\Models\YocoTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminGatewayTransactionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:gateway-transactions.index');
    }

    public function index()
    {
        return view('admin.gateway-transactions.index');
    }

    // ─── PESEPAY ────────────────────────────────────────────────────────────

    public function pesepay(Request $request)
    {
        $query = DB::table('pesepay_transactions');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'ilike', "%{$search}%")
                  ->orWhere('reason_for_payment', 'ilike', "%{$search}%")
                  ->orWhere('transaction_status', 'ilike', "%{$search}%")
                  ->orWhere('application_name', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('transaction_status', 'ilike', $status);
        }
        if ($currency = $request->get('currency')) {
            $query->where('currency_code', $currency);
        }
        if ($from = $request->get('date_from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to = $request->get('date_to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $sortCol = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        if (!in_array($sortCol, ['id','reference_number','transaction_status','amount','currency_code','created_at'])) {
            $sortCol = 'created_at';
        }
        $query->orderBy($sortCol, $sortDir === 'asc' ? 'asc' : 'desc');

        return response()->json($query->paginate(25)->withQueryString());
    }

    // ─── PAYFAST ────────────────────────────────────────────────────────────

    public function payfast(Request $request)
    {
        $query = PayfastTransaction::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('pf_payment_id', 'ilike', "%{$search}%")
                  ->orWhere('m_payment_id', 'ilike', "%{$search}%")
                  ->orWhere('item_name', 'ilike', "%{$search}%")
                  ->orWhere('email_address', 'ilike', "%{$search}%")
                  ->orWhere('name_first', 'ilike', "%{$search}%")
                  ->orWhere('name_last', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('payment_status', 'ilike', $status);
        }
        if ($type = $request->get('type')) {
            $query->where('custom_str1', $type);
        }
        if ($from = $request->get('date_from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to = $request->get('date_to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $sortCol = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        if (!in_array($sortCol, ['id','pf_payment_id','payment_status','amount_gross','amount_net','created_at'])) {
            $sortCol = 'created_at';
        }
        $query->orderBy($sortCol, $sortDir === 'asc' ? 'asc' : 'desc');

        return response()->json($query->paginate(25)->withQueryString());
    }

    // ─── DPO ZAMBIA ─────────────────────────────────────────────────────────

    public function dpo(Request $request)
    {
        $query = DpoZambiaTransaction::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('trans_id', 'ilike', "%{$search}%")
                  ->orWhere('company_ref', 'ilike', "%{$search}%")
                  ->orWhere('customer_name', 'ilike', "%{$search}%")
                  ->orWhere('customer_email', 'ilike', "%{$search}%")
                  ->orWhere('result_explanation', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('transaction_status', 'ilike', $status);
        }
        if ($currency = $request->get('currency')) {
            $query->where('transaction_currency', $currency);
        }
        if ($from = $request->get('date_from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to = $request->get('date_to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $sortCol = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        if (!in_array($sortCol, ['id','trans_id','transaction_status','payment_amount','transaction_currency','customer_name','created_at'])) {
            $sortCol = 'created_at';
        }
        $query->orderBy($sortCol, $sortDir === 'asc' ? 'asc' : 'desc');

        return response()->json($query->paginate(25)->withQueryString());
    }

    // ─── ORDER TRANSACTIONS ──────────────────────────────────────────────────

    public function orderTransactions(Request $request)
    {
        $query = OrderTransaction::with(['order:id,order_number,payment_status,total,currency']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'ilike', "%{$search}%")
                  ->orWhereHas('order', fn($o) => $o->where('order_number', 'ilike', "%{$search}%"));
            });
        }
        if ($from = $request->get('date_from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to = $request->get('date_to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $sortCol = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        if (!in_array($sortCol, ['id','transaction_id','order_id','created_at'])) {
            $sortCol = 'created_at';
        }
        $query->orderBy($sortCol, $sortDir === 'asc' ? 'asc' : 'desc');

        return response()->json($query->paginate(25)->withQueryString());
    }

    // ─── WALLET/POINTS TRANSACTIONS ─────────────────────────────────────────

    public function transactions(Request $request)
    {
        $query = Transaction::with(['wallet:id,consumer_id,balance', 'order:id,order_number']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('detail', 'ilike', "%{$search}%")
                  ->orWhere('type', 'ilike', "%{$search}%");
            });
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($source = $request->get('source')) {
            if ($source === 'wallet') {
                $query->whereNotNull('wallet_id')->whereNull('point_id');
            } elseif ($source === 'points') {
                $query->whereNotNull('point_id');
            }
        }
        if ($from = $request->get('date_from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to = $request->get('date_to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $sortCol = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        if (!in_array($sortCol, ['id','type','amount','wallet_id','order_id','created_at'])) {
            $sortCol = 'created_at';
        }
        $query->orderBy($sortCol, $sortDir === 'asc' ? 'asc' : 'desc');

        return response()->json($query->paginate(25)->withQueryString());
    }

    // ─── VENDOR TRANSACTIONS ─────────────────────────────────────────────────

    public function vendorTransactions(Request $request)
    {
        $query = VendorTransaction::with(['vendor_wallet:id,vendor_id,balance']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('detail', 'ilike', "%{$search}%")
                  ->orWhere('type', 'ilike', "%{$search}%");
            });
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($from = $request->get('date_from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to = $request->get('date_to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $sortCol = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        if (!in_array($sortCol, ['id','type','amount','vendor_id','created_at'])) {
            $sortCol = 'created_at';
        }
        $query->orderBy($sortCol, $sortDir === 'asc' ? 'asc' : 'desc');

        return response()->json($query->paginate(25)->withQueryString());
    }

    // ─── YOCO ────────────────────────────────────────────────────────────────

    public function yoco(Request $request)
    {
        $query = YocoTransaction::with(['order:id,order_number,payment_status,total,currency']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('gateway_transaction_id', 'ilike', "%{$search}%")
                  ->orWhere('order_number', 'ilike', "%{$search}%")
                  ->orWhere('status', 'ilike', "%{$search}%");
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', 'ilike', $status);
        }
        if ($currency = $request->get('currency')) {
            $query->where('currency', $currency);
        }
        if ($from = $request->get('date_from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to = $request->get('date_to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $sortCol = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        if (!in_array($sortCol, ['id','gateway_transaction_id','status','amount_cents','currency','created_at'])) {
            $sortCol = 'created_at';
        }
        $query->orderBy($sortCol, $sortDir === 'asc' ? 'asc' : 'desc');

        return response()->json($query->paginate(25)->withQueryString());
    }
}

