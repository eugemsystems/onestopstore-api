<?php

namespace App\Repositories\Eloquents;

use App\GraphQL\Exceptions\ExceptionHandler;
use Exception;
use App\Models\Setting;
use App\Models\Currency;
use App\Helpers\Helpers;
use Illuminate\Support\Arr;
use App\Enums\PaymentMethod;
use App\Enums\FrontSettingsEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Prettus\Repository\Eloquent\BaseRepository;

class SettingRepository extends BaseRepository
{
    protected $currency;

    function model()
    {
        $this->currency = new Currency();
        return Setting::class;
    }

    public function frontSettings()
    {
        try {

            $settingValues = Helpers::getSettings();
            $paymentMethods = PaymentMethod::ALL_PAYMENT_METHODS;

            foreach ($paymentMethods as $paymentMethod) {
                $methodData = [
                    "name" => $paymentMethod,
                    "title" => $settingValues['payment_methods'][$paymentMethod]['title'],
                    "status" => $settingValues['payment_methods'][$paymentMethod]['status']
                ];

                // Include banking details for bank_transfer method
                if ($paymentMethod === 'bank_transfer' && isset($settingValues['payment_methods'][$paymentMethod]['accounts'])) {
                    $methodData['accounts'] = $settingValues['payment_methods'][$paymentMethod]['accounts'];
                    $methodData['company'] = $settingValues['payment_methods'][$paymentMethod]['company'] ?? null;
                }

                $paymentMethodStatus[] = $methodData;
            }

            $settings['values'] = Arr::only($settingValues, array_column(FrontSettingsEnum::cases(), 'value'));
            $settings['values']['payment_methods'] = $paymentMethodStatus;

            return $settings;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode());
        }
    }

    public function update($request, $id)
    {
        //Request is an array
        DB::beginTransaction();
        try {
            $settings = $this->model->first();
            $settings->update($request);
            //$settings = $settings->fresh();
            $this->env($request['values']);

            DB::commit();
            reCacheSettings();
            return getCachedSettings();

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), (int)$e->getCode());
        }
    }

    public function setDefaultCurrencyBasePrice($settings)
    {
        $currency = $this->currency->findOrFail($settings['general']['default_currency_id']);
        $currency->update([
            'exchange_rate' => true
        ]);
    }

    public function env($value)
    {
        try {

            if (isset($value['email'])) {
                DotenvEditor::setKeys([
                    'MAIL_MAILER' => $value['email']["mail_mailer"],
                    'MAIL_HOST' => $value['email']["mail_host"],
                    'MAIL_PORT' => $value['email']["mail_port"],
                    'MAIL_USERNAME' => $value['email']["mail_username"],
                    'MAIL_PASSWORD' => $value['email']["mail_password"],
                    'MAIL_ENCRYPTION' => $value['email']["mail_encryption"],
                    'MAIL_FROM_ADDRESS' => $value['email']["mail_from_address"],
                    'MAIL_FROM_NAME' => $value['email']["mail_from_name"],
                    'MAILGUN_DOMAIN' => $value['email']["mailgun_domain"],
                    'MAILGUN_SECRET' => $value['email']["mailgun_secret"],
                ]);

                DotenvEditor::save();
            }

            if (isset($value['payment_methods'])) {
                $paypal_mode = $value['payment_methods']['paypal']["sandbox_mode"]? 'sandbox' : 'live';
                DotenvEditor::setKeys([
                    // PayPal
                    'PAYPAL_MODE' =>  $paypal_mode,
                    'PAYPAL_CLIENT_ID' => $value['payment_methods']['paypal']["client_id"],
                    'PAYPAL_CLIENT_SECRET' => $value['payment_methods']['paypal']["client_secret"],

                    // PesePay
                    'PESE_API_KEY' => $value['payment_methods']['pese']["key"],
                    'PESE_SECRET_KEY' => $value['payment_methods']['pese']["secret"],

                    // DPO (Zambia)
                    'DPO_BASE_URL'          => $value['payment_methods']['pdo_zambia']["base_url"] ?? null,
                    'DPO_COMPANY_TOKEN'     => $value['payment_methods']['pdo_zambia']["company_token"] ?? null,
                    'DPO_SERVICE_TYPE'      => $value['payment_methods']['pdo_zambia']["service_type"] ?? null,
                    'DPO_PTL'               => $value['payment_methods']['pdo_zambia']["ptl"] ?? null,
                    'DPO_INIT_ENDPOINT'     => $value['payment_methods']['pdo_zambia']["init_endpoint"] ?? null,
                    'DPO_REDIRECT_TEMPLATE' => $value['payment_methods']['pdo_zambia']["redirect_template"] ?? null,
                ]);

                DotenvEditor::save();
            }

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), (int)$e->getCode());
        }
    }
}
