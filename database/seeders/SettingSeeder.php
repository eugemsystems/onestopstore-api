<?php

namespace Database\Seeders;

use App\Helpers\Helpers;
use App\Models\Attachment;
use App\Models\Setting;
use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run(){

        $currency_id = Currency::where('status', true)->first()->id;
        //get uuid for the default images
        $favicon = optional(Attachment::find(1))->uuid;
        $logo_white = optional(Attachment::find(2))->uuid;
        $logo_dark = optional(Attachment::find(3))->uuid;
        $tiny_logo = optional(Attachment::find(4))->uuid;
        $maintainance = optional(Attachment::find(5))->uuid;
        $values = [
            'general' => [
                'light_logo_image_id' => $logo_white??Str::uuid(),
                'dark_logo_image_id' => $logo_dark??Str::uuid(),
                'tiny_logo_image_id' => $tiny_logo??Str::uuid(),
                'favicon_image_id' => $favicon??Str::uuid(),
                'site_title' => config('app.name'),
                'site_tagline' => "Online store",
                'site_name' => config('app.name'),
                'site_url' => config('app.frontend_url'),
                'default_timezone' => 'Africa/Harare',
                'default_currency_id' => $currency_id,
                'admin_site_language_direction' => 'ltr',
                'min_order_amount' => 50,
                'min_order_free_shipping' => 100,
                'product_sku_prefix' => 'RA',
                'mode' => 'light-only',
                'copyright' => 'Copyright '.date('Y').' © '.config('app.name').' By <a href="https://eugemsystems.com">EugemSytems</a>?',
                'specials_button_label' => 'Specials',
                'specials_button_url' => '',
            ],
            'activation' => [
                'multivendor' => false,
                'point_enable' => false,
                'coupon_enable' => true,
                'wallet_enable' => false,
                'stock_product_hide' => true,
                'store_auto_approve' => true,
                'product_auto_approve' => true,
            ],
            'wallet_points' => [
                'signup_points' => 100,
                'min_per_order_amount' => 100,
                'point_currency_ratio' => 30,
                'reward_per_order_amount' => 10,
            ],
            'vendor_commissions' => [
                'status' => false,
                'min_withdraw_amount' => 500,
                'default_commission_rate' => 10,
                'is_category_based_commission' => true,
            ],
            'email' => [
                'mail_host' => 'sandbox.smtp.mailtrap.io',
                'mail_port' => '2525',
                'mail_mailer' => 'smtp',
                'mail_username' => 'd123f2c3b9a2bd',
                'mail_password' => '499db81753d271',
                'mail_encryption' => '',
                'mail_from_name' => config('app.name'),
                'mail_from_address' => env('MAIL_FROM_ADDRESS'),
                'mailgun_domain' => null,
                'mailgun_secret' => null
            ],
            'refund' => [
                'status' => true,
                "refundable_days" => 7,
            ],
            'newsletter' => [
                'status' => false,
                'mailchip_api_key' => '',
                'mailchip_list_id' => '',
            ],
            'delivery' => [
                'default_delivery'=> 1,
                'default' => [
                    'title' => 'Standard Delivery',
                    'description' => 'Approx 3 to 7 Days'
                ],
                'shipping_options' => [
                    [
                        'price'       => 0,
                        'title'       => 'Free Collection - Harare Branch',
                        'description' => '32 Rhodesville Avenue Greendale - (3-7 Business Days)',
                    ],
                    [
                        'price'       => 0,
                        'title'       => 'Free Collection - Bulawayo Branch',
                        'description' => 'Office 90 George Silundika. 2nd Floor, Between 8th and 9th Avenue - (3-7 Business Days)',
                    ],
                    [
                        'price'       => 0,
                        'title'       => 'Free Collection - Lusaka Branch',
                        'description' => 'Niyati Plaza, Kalingalinga Area, 35235 Alick Nkhata Rd, Lusaka, Zambia - (5-7 Business Days)',
                    ],
                    [
                        'price'       => 0,
                        'title'       => 'Free Collection - Mutare Branch',
                        'description' => '1A Twin Towers Complex 37 Robert Mugabe Rd - (3-7 Business Days)',
                    ],
                    [
                        'price'       => 15,
                        'title'       => 'Standard Home Delivery',
                        'description' => 'NB: This covers 15km radius from the nearest branch',
                    ]
                ],
                'same_day_delivery' => false,
                'same_day' => [
                    'title' => 'Express Delivery',
                    'description' => 'Schedule'
                ],
                'same_day_intervals' => [
                    [
                        'title' => 'Morning',
                        'description' => '8.00 AM - 12.00 AM',
                    ],
                    [
                        'title' => 'Noon',
                        'description' => '12.00 PM - 2.00 PM'
                    ],
                    [
                        'title' => 'Afternoon',
                        'description' => '02.00 PM - 05.00 PM',
                    ],
                    [
                        'title' => 'Evening',
                        'description' => '05.00 PM - 08.00 PM'
                    ]
                ]
            ],
            'payment_methods' => [
                'cod' => [
                    'title' => 'Cash On Delivery',
                    'status' => true
                ],
                // 'accounts'/'company' is what SettingRepository::frontSettings() actually reads for
                // bank_transfer (not the old flat account_name/bank_name/... fields) — fill in real
                // account details for this site before going live, these are placeholders.
                'bank_transfer' => [
                    'title' => 'Bank Transfer',
                    'status' => true,
                    'company' => config('app.name'),
                    'accounts' => [
                        [
                            'bank' => 'Your Bank Name',
                            'account_number' => '0000000000',
                            'bic' => 'ABCDEFXX',
                        ],
                    ],
                    'reference_prefix' => 'ORDER',
                ],
                'paypal' => [
                    'title' => 'PayPal',
                    'client_id' => '',
                    'client_secret' => '',
                    'status' => false,
                    'sandbox_mode' => true,
                ],
                'pese' => [
                    'title' => 'Pese Pay',
                    'key' => '',
                    'secret' => '',
                    'status' => false,
                ],
                'payfast' => [
                    'title'        => 'Payfast',
                    'status'       => [],
                    'passphrase'   => '',
                    'merchant_id'  => '',
                    'merchant_key' => '',
                    'sandbox_mode' => ['on'],
                ],
                // DPO/3gdirectpay (Zambia) — base_url is DPO's fixed API endpoint, the rest are
                // this merchant's own credentials (set via the admin settings form, which syncs
                // them to .env — see SettingRepository::update()).
                'pdo_zambia' => [
                    'title' => 'PDO Zambia',
                    'status' => false,
                    'base_url' => 'https://secure.3gdirectpay.com',
                    'company_token' => '',
                    'service_type' => '',
                    'ptl' => '',
                    'init_endpoint' => '/API/v6/Transaction/CreatePayment',
                    'redirect_template' => '/payv3.php?ID={token}',
                ],
                // Yoco credentials live in .env (YOCO_PUBLIC_KEY/YOCO_SECRET_KEY/...), this is
                // just the on/off toggle + display title.
                'yoco' => [
                    'title' => 'Yoco',
                    'status' => false,
                ],
                'wallet' => [
                    'title' => 'Wallet',
                    'status' => false,
                ],
            ],
            // Disabled by default — these IDs/keys are tied to a specific domain/business account
            // (reCAPTCHA site keys are domain-locked; Pixel/GA IDs attribute traffic to whoever
            // owns them), so fill in this site's own values before enabling.
            'analytics' => [
                'facebook_pixel' => [
                    'status' => [],
                    'pixel_id' => '',
                ],
                'google_analytics' => [
                    'measurement_id' => '',
                ],
            ],
            'google_reCaptcha' => [
                'secret' => '',
                'site_key' => '',
                'status' => false,
            ],
            'maintenance' => [
                'title' => "We will be back Soon..",
                'maintenance_mode' => false,
                'maintenance_image_id' => $maintainance,
                'description' => "We are busy to updating our store for you."
            ]
        ];

        // Use DB::table for raw insert with JSON handling
        DB::table('settings')->updateOrInsert(
            ['id' => 1], // Match condition
            ['values' => DB::raw("'" . json_encode($values) . "'")] // Raw JSON string
        );

        DB::table('seeders')->updateOrInsert(
            ['name' => 'SettingSeeder'],
            ['is_completed' => true]
        );
    }

}
