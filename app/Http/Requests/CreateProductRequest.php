<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Contracts\Validation\Validator;

class CreateProductRequest extends FormRequest
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

    protected function normalizeBool($v): int
    {
        if (is_array($v)) { $v = reset($v); }
        if (is_string($v)) {
            $v = strtolower(trim($v));
            if (in_array($v, ['on','true','1','yes'], true)) return 1;
            if (in_array($v, ['off','false','0','no',''], true)) return 0;
        }
        return (int)!!$v;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'has_expedited_shipping' => $this->normalizeBool($this->input('has_expedited_shipping')),
            'standard_shipping_days' => $this->input('standard_shipping_days') !== '' ? (int)$this->input('standard_shipping_days') : null,
            'expedited_shipping_days'=> $this->input('expedited_shipping_days') !== '' ? (int)$this->input('expedited_shipping_days') : null,
            'standard_shipping_price'=> $this->input('standard_shipping_price') !== '' ? (float)$this->input('standard_shipping_price') : null,
            'expedited_shipping_price'=> $this->input('expedited_shipping_price') !== '' ? (float)$this->input('expedited_shipping_price') : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'short_description' => ['required', 'string'],
            'store_id' => ['nullable','exists:stores,id,deleted_at,NULL'],
            'type' => ['required','in:simple,classified'],
            'price' => ['required_if:type,==,simple'],
            'categories'=>['required','exists:categories,id,deleted_at,NULL'],
            'stock_status' => ['required_if:type,==,simple', 'in:in_stock,out_of_stock'],
            'attributes_ids' => ['required_if:type,==,classified','exists:attributes,id,deleted_at,NULL'],
            'tags' => ['nullable','array','exists:tags,id,deleted_at,NULL'],

            // Shipping: allow nullable and coerce types in prepareForValidation
            'has_expedited_shipping' => ['nullable','in:0,1'],
            'standard_shipping_days' => ['nullable','integer','min:0'],
            'expedited_shipping_days'=> ['nullable','integer','min:0'],
            'standard_shipping_price'=> ['nullable','numeric','min:0'],
            'expedited_shipping_price'=> ['nullable','numeric','min:0'],

            // Accept either numeric IDs or UUIDs for attachments on create
            'product_meta_image_id' => ['nullable','exists:attachments,id,deleted_at,NULL'],
            'product_thumbnail_id' => ['nullable','exists:attachments,id,deleted_at,NULL'],
            'product_galleries_id' => ['nullable','array'],
            'product_galleries_id.*' => ['nullable','exists:attachments,id,deleted_at,NULL'],
            'product_meta_image_uuid' => ['nullable','exists:attachments,uuid,deleted_at,NULL'],
            'product_thumbnail_uuid' => ['nullable','exists:attachments,uuid,deleted_at,NULL'],
            'product_galleries_uuid' => ['nullable','array'],
            'product_galleries_uuid.*' => ['nullable','exists:attachments,uuid,deleted_at,NULL'],
            'quantity' => ['numeric','required_if:type,==,simple'],
            'sku' => ['required_if:type,==,simple', 'unique:products,sku,NULL,id,deleted_at,NULL'],
            'is_external' => ['min:0', 'max:1'],
            'external_url' => ['required_if:is_external,==,1', 'nullable'],
            'external_button_text' => ['required_if:type,==,external', 'nullable'],
            'discount' => ['nullable','numeric','regex:/^([0-9]{1,2}){1}(\.[0-9]{1,2})?$/'],
            'tax_id' => ['nullable','exists:taxes,id,deleted_at,NULL'],
            'meta_title' => ['nullable','string','max:255'],
            'meta_description' => ['nullable','string'],
            'show_stock_quantity' => ['min:0', 'max:1'],
            'is_featured' => ['min:0', 'max:1'],
            'size_chart_image_id' => ['nullable','exists:attachments,id,deleted_at,NULL'],
            'secure_checkout' => ['min:0', 'max:1'],
            'safe_checkout' => ['min:0', 'max:1'],
            'social_share' => ['min:0', 'max:1'],
            'encourage_order' => ['min:0', 'max:1'],
            'encourage_view' => ['min:0', 'max:1'],
            'is_cod' => ['min:0', 'max:1'],
            'is_return' => ['min:0', 'max:1'],
            'is_free_shipping' => ['min:0', 'max:1'],
            'is_changeable' => ['min:0', 'max:1'],
            'is_sale_enable' => ['min:0', 'max:1'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_expired_at' => ['nullable','date', 'after:sale_starts_at'],
            'status' => ['required','min:0','max:1'],
            'cross_sell_products' => ['nullable','exists:products,id,deleted_at,NULL'],
            'related_products' => ['nullable','exists:products,id,deleted_at,NULL'],
            'visible_time' => ['nullable','date'],
            'variations.*.name' => ['required_if:type,==,classified', 'string'],
            'variations.*.price' => ['required_if:type,==,classified', 'numeric'],
            'variations.*.sale_price' => ['nullable', 'numeric'],
            'variations.*.stock_status' => ['required_if:type,==,classified', 'in:in_stock,out_of_stock,coming_soon'],
            'variations.*.attribute_values' => ['required_if:type,==,classified','exists:attribute_values,id,deleted_at,NULL'],
            'variations.*.discount' => ['nullable','numeric', 'regex:/^([0-9]{1,2}){1}(\.[0-9]{1,2})?$/'],
            'variations.*.sku' => ['required_if:type,==,classified', 'string', 'unique:variations,sku'],
            'variations.*.status' => ['required_if:type,==,classified','min:0','max:1'],
            'variations.*.variation_image_id' => ['nullable','exists:attachments,id,deleted_at,NULL']
        ];
    }

    public function messages()
    {
        return [
            'discount.regex' => 'Enter discount between 0 to 99.99',
            'type.in' => 'Product type can be either simple or classified',
            'stock_status.in' => 'Stock status can be either in_stock or out_of_stock',
            'video_provider.in' => 'Video Provider can in youtube or vimeo or daily_motion',
            'variations.*.discount.regex' => 'Enter Variations discount between 0 to 99.99',
            'variations.*.stock_status.in' => 'Variations Stock status can be either in_stock or out_of_stock or coming_soon',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new ExceptionHandler($validator->errors()->first(), 422);
    }
}
