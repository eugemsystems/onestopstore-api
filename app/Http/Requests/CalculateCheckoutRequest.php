<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class CalculateCheckoutRequest extends FormRequest
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
        return [
            'consumer_id' => ['exists:users,id'],
            'products' => ['required','array'],
            'products.*.product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'products.*.variation_id' => ['nullable', Rule::exists('variations', 'id')->whereNull('deleted_at')],
            'coupon' => ['nullable', Rule::exists('coupons', 'code')->whereNull('deleted_at')],
            'payment_method' => ['nullable'],
            'billing_address_id' => ['required', Rule::exists('addresses', 'id')->whereNull('deleted_at')],
            'shipping_address_id'=>['required', Rule::exists('addresses', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages()
    {
        return [
            'products.*.product_id.required' => 'Product ID is required for all items.',
            'products.*.product_id.exists' => 'One or more products in your cart are no longer available. Please refresh your cart and try again.',
            'products.*.variation_id.exists' => 'The selected product variation is no longer available.',
            'coupon.exists' => "We could not find an {$this->coupon} coupon",
            'billing_address_id.exists' => 'The selected billing address is invalid.',
            'shipping_address_id.exists' => 'The selected shipping address is invalid.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        // Log which products failed validation for debugging
        if ($this->has('products')) {
            $productIds = collect($this->products)->pluck('product_id')->filter();
            $variationIds = collect($this->products)->pluck('variation_id')->filter();

            \Log::warning('Checkout validation failed', [
                'product_ids' => $productIds->toArray(),
                'variation_ids' => $variationIds->toArray(),
                'errors' => $validator->errors()->toArray(),
                'user_id' => auth()->id() ?? 'guest',
                'ip' => request()->ip(),
            ]);
        }

        throw new ExceptionHandler($validator->errors()->first(), 422);
    }
}
