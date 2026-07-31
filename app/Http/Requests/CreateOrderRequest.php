<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Foundation\Http\FormRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $roleName = Helpers::getCurrentRoleName();
        $order = [
            'consumer_id' => ['required','nullable', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'products' => ['required','array'],
            'products.*.product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'products.*.variation_id' => ['nullable', Rule::exists('variations', 'id')],
            'products.*.selected_attribute_ids' => ['nullable','array'],
            'products.*.variation_display_name' => ['nullable','string','max:255'],
            'coupon' => ['nullable', Rule::exists('coupons', 'code')->whereNull('deleted_at')],
            'billing_address_id' => ['required', Rule::exists('addresses', 'id')->whereNull('deleted_at')],
            'shipping_address_id'=>['required', Rule::exists('addresses', 'id')->whereNull('deleted_at')],
            'payment_method' => ['string', 'in:'.implode(',', PaymentMethod::ALL_PAYMENT_METHODS)],
            'delivery_interval' => ['nullable','string'],
            'currency' => ['nullable','string','max:10'],
            'currency_symbol' => ['nullable','string','max:5'],
            'exchange_rate' => ['nullable','numeric','min:0'],
            'payfast_fee' => ['nullable','numeric','min:0'],
        ];

        if ($roleName == RoleEnum::CONSUMER) {
            return array_merge($order, ['consumer_id' => [Rule::exists('users', 'id')->whereNull('deleted_at')]]);
        }

        return $order;
    }

    public function failedValidation(Validator $validator)
    {
        throw new ExceptionHandler($validator->errors()->first(), 422);
    }
}
