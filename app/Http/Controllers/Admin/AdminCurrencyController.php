<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:currency.index')->only(['index']);
        $this->middleware('permission:currency.create')->only(['store']);
        $this->middleware('permission:currency.edit')->only(['update']);
        $this->middleware('permission:currency.delete')->only(['destroy']);
    }

    public function index()
    {
        $currencies = Currency::orderBy('code')->get();
        return view('admin.currencies.index', compact('currencies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'                => ['required', 'string', 'max:10', Rule::unique('currencies', 'code')],
            'symbol'              => ['required', 'string', 'max:10'],
            'exchange_rate'       => ['required', 'numeric', 'min:0'],
            'no_of_decimal'       => ['required', 'integer', 'min:0', 'max:8'],
            'status'              => ['required', 'in:0,1'],
            'symbol_position'     => ['required', 'in:before_price,after_price'],
            'decimal_separator'   => ['required', 'in:comma,period,space'],
            'thousands_separator' => ['nullable', 'in:comma,period,space'],
        ]);

        $currency = Currency::create($data);

        return response()->json([
            'success' => true,
            'message' => "Currency {$currency->code} created successfully.",
            'currency' => $currency,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $currency = Currency::findOrFail($id);

        $data = $request->validate([
            'code'                => ['required', 'string', 'max:10', Rule::unique('currencies', 'code')->ignore($id)],
            'symbol'              => ['required', 'string', 'max:10'],
            'exchange_rate'       => ['required', 'numeric', 'min:0'],
            'no_of_decimal'       => ['required', 'integer', 'min:0', 'max:8'],
            'status'              => ['required', 'in:0,1'],
            'symbol_position'     => ['required', 'in:before_price,after_price'],
            'decimal_separator'   => ['required', 'in:comma,period,space'],
            'thousands_separator' => ['nullable', 'in:comma,period,space'],
        ]);

        $currency->update($data);

        return response()->json([
            'success'  => true,
            'message'  => "Currency {$currency->code} updated successfully.",
            'currency' => $currency->fresh(),
        ]);
    }

    public function destroy(int $id)
    {
        $currency = Currency::findOrFail($id);
        $code = $currency->code;
        $currency->delete();

        return response()->json([
            'success' => true,
            'message' => "Currency {$code} deleted successfully.",
        ]);
    }

    public function toggleStatus(int $id)
    {
        $currency = Currency::findOrFail($id);
        $currency->update(['status' => $currency->status ? 0 : 1]);

        return response()->json([
            'success' => true,
            'message' => "Currency {$currency->code} " . ($currency->status ? 'enabled' : 'disabled') . '.',
            'status'  => $currency->status,
        ]);
    }
}

