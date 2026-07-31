<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Contracts\Validation\Validator;

class CreateStoreRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
             // Personal Information
            'name' => ['nullable', Rule::requiredIf(!$this->vendor_id)],
            'email'    => ['nullable',Rule::requiredIf(!$this->vendor_id), 'email', 'unique:users,email,NULL,id,deleted_at,NULL'],
            'phone'     => ['nullable', 'digits_between:8,15', Rule::requiredIf(!$this->vendor_id),'unique:users,phone,NULL,id,deleted_at,NULL'],
            'password' => ['nullable',Rule::requiredIf(!$this->vendor_id), 'min:8','confirmed'],
            'password_confirmation' => ['nullable', Rule::requiredIf(!$this->vendor_id)],

            // VAT & Identification
            'is_vat_registered' => ['required', 'in:yes,no'],
            'vat_number' => ['nullable', 'required_if:is_vat_registered,yes', 'string', 'max:50'],
            'identification_type' => ['required', 'in:id,passport'],
            'id_number' => ['required', 'string', 'max:50'], // Required for both ID and passport

            // Business Identifiers
            'legal_name' => ['required', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],

            // Store Details
            'store_name'   => ['required', 'string', 'max:255', 'unique:stores,store_name,NULL,id,deleted_at,NULL'],
            'description' => ['required', 'min:10'],
            'country_id' => ['required','exists:countries,id'],
            'state_id' => ['nullable','exists:states,id'], // State/province is optional
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string'],
            'pincode' => ['required', 'string', 'max:20'],

            // Business Information
            'monthly_revenue' => ['required', 'string', 'in:less_than_1k,1k_2.5k,2.5k_5k,5k_25k,25k_50k,50k_125k,125k_plus'],
            'has_physical_stores' => ['required', 'in:yes,no'],
            'number_of_stores' => ['nullable', 'required_if:has_physical_stores,yes', 'integer', 'min:1'],
            'is_supplier_to_retailers' => ['required', 'in:yes,no'],
            'has_marketplace_accounts' => ['required', 'in:yes,no'],

            // Product Range
            'number_of_products' => ['required', 'integer', 'min:1'],
            'primary_category' => ['required', 'string', 'max:255'],
            'stock_holding' => ['required', 'in:whole_range,some_range,on_demand'],
            'product_source' => ['required', 'in:imported,manufactured_locally,mixture'],
            'product_branding' => ['required', 'in:branded,unbranded,combination'],
            'owned_brands' => ['nullable', 'string'],
            'reseller_brands' => ['nullable', 'string'],

            // Online Presence
            'website' => ['nullable', 'url', 'max:255'],
            'social_media_page' => ['nullable', 'url', 'max:255'],
            'product_catalog' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'], // 10MB max
            'business_summary' => ['required', 'string', 'min:10'],
            'product_uniqueness' => ['nullable', 'string'],
            'intended_products' => ['nullable', 'string'],
            'certifications' => ['nullable', 'string', 'max:255'],
            'referral_source' => ['required', 'in:google,customer,expo,township_initiative,referral,tiktok,youtube,linkedin,facebook'],

            // Social Media (optional)
            'facebook' => ['nullable', 'url'],
            'twitter' => ['nullable', 'url'],
            'instagram' => ['nullable', 'url'],
            'youtube' => ['nullable', 'url'],
            'pinterest' => ['nullable', 'url'],

            // System fields
            'vendor_id' => ['nullable','exists:users,id,deleted_at,NULL'],
            'store_logo_id' => ['nullable','exists:attachments,id,deleted_at,NULL'],
            'store_cover_id' => ['nullable','exists:attachments,id,deleted_at,NULL'],
            'status' => ['nullable','min:0','max:1'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new ExceptionHandler($validator->errors()->first(), 422);
    }
}
