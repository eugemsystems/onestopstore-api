<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyDetectionService
{
    /**
     * Country code to currency code mapping
     */
    protected static $countryToCurrency = [
        'ZM' => 'ZMW',  // Zambia -> Zambian Kwacha
        'ZA' => 'ZAR',  // South Africa -> South African Rand
        'ZW' => 'USD',  // Zimbabwe -> USD (uses USD primarily)
        'US' => 'USD',  // United States -> USD
        'GB' => 'GBP',  // United Kingdom -> British Pound
        // Add more mappings as needed
    ];

    /**
     * Detect currency based on IP address
     *
     * @param string|null $ipAddress
     * @return array ['code' => 'ZMW', 'symbol' => 'K', 'exchange_rate' => 27.5]
     */
    public static function detectCurrencyFromIP($ipAddress = null)
    {
        try {
            // Get IP address
            $ip = $ipAddress ?: request()->ip();


            // Check if local IP - but be less strict for VPN detection
            if (self::isStrictlyLocalIP($ip)) {
                return self::getDefaultCurrency();
            }

            // Try to get from cache first (cache for 1 hour for better VPN handling)
            $cacheKey = 'currency_detection_' . $ip;

            // Allow cache bypass with query parameter for testing
            if (request()->has('refresh_currency')) {
                Cache::forget($cacheKey);
            }

            $cached = Cache::get($cacheKey);

            if ($cached) {
                return $cached;
            }

            // Detect country from IP
            $countryCode = self::getCountryFromIP($ip);

            if (!$countryCode) {
                return self::getDefaultCurrency();
            }


            // Get currency for country
            $currencyCode = self::$countryToCurrency[$countryCode] ?? 'USD';


            // Get currency details from database
            $currency = Currency::where('code', $currencyCode)
                ->where('status', 1)
                ->first();

            if (!$currency) {
                return self::getDefaultCurrency();
            }

            $result = [
                'code' => $currency->code,
                'symbol' => $currency->symbol,
                'exchange_rate' => floatval($currency->exchange_rate),
                'country_code' => $countryCode,
            ];

            // Cache the result for 1 hour (shorter for better VPN handling)
            Cache::put($cacheKey, $result, now()->addHour());


            return $result;

        } catch (\Exception $e) {

            return self::getDefaultCurrency();
        }
    }

    /**
     * Get country code from IP address using ip-api.com (free, no key required)
     */
    protected static function getCountryFromIP($ip)
    {
        try {
            // Use ip-api.com free service (no API key required)
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");


            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === 'success') {

                    return $data['countryCode'] ?? null;
                } else {
                    Log::warning('IP geolocation failed', [
                        'ip' => $ip,
                        'data' => $data
                    ]);
                }
            }

            return null;

        } catch (\Exception $e) {

            return null;
        }
    }

    /**
     * Check if IP is strictly local/private (not VPN)
     */
    protected static function isStrictlyLocalIP($ip)
    {
        // Only check for localhost and truly private networks
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'localhost') {
            return true;
        }

        // Check if IP is in strictly private range (not public VPN ranges)
        $longIp = ip2long($ip);
        if ($longIp === false) {
            return true; // Can't determine, assume local
        }

        // Only block these specific private ranges
        $strictPrivateRanges = [
            '127.0.0.0' => '127.255.255.255',  // Localhost
            '10.0.0.0' => '10.255.255.255',    // Private network
            '192.168.0.0' => '192.168.255.255', // Private network
        ];

        foreach ($strictPrivateRanges as $start => $end) {
            if ($longIp >= ip2long($start) && $longIp <= ip2long($end)) {
                return true;
            }
        }

        // Allow 172.16.0.0/12 range as it might be VPN
        // VPN IPs often fall outside these strict ranges

        return false;
    }

    /**
     * Get default currency (USD)
     */
    protected static function getDefaultCurrency()
    {
        $currency = Currency::where('code', 'USD')
            ->where('status', 1)
            ->first();

        if (!$currency) {
            // Fallback if USD not in database
            return [
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 1.0,
                'country_code' => 'US',
            ];
        }

        return [
            'code' => $currency->code,
            'symbol' => $currency->symbol,
            'exchange_rate' => floatval($currency->exchange_rate),
            'country_code' => 'US',
        ];
    }

    /**
     * Convert amount from USD to target currency
     */
    public static function convertAmount($amount, $targetCurrencyCode)
    {
        try {
            // Get base currency (USD)
            $baseCurrency = Currency::where('code', 'USD')->first();
            $baseRate = $baseCurrency ? floatval($baseCurrency->exchange_rate) : 1.0;

            // Get target currency
            $targetCurrency = Currency::where('code', $targetCurrencyCode)
                ->where('status', 1)
                ->first();

            if (!$targetCurrency) {
                return $amount; // Return original if currency not found
            }

            $targetRate = floatval($targetCurrency->exchange_rate);

            // Convert: amount_in_usd * (target_rate / base_rate)
            $converted = $amount * ($targetRate / $baseRate);

            return round($converted, 2);

        } catch (\Exception $e) {
            Log::error('Currency conversion failed', [
                'amount' => $amount,
                'target_currency' => $targetCurrencyCode,
                'error' => $e->getMessage()
            ]);
            return $amount;
        }
    }

    /**
     * Format amount with currency symbol
     */
    public static function formatAmount($amount, $currencyCode)
    {
        $currency = Currency::where('code', $currencyCode)->first();

        if (!$currency) {
            return '$' . number_format($amount, 2);
        }

        $formatted = number_format($amount, $currency->no_of_decimal ?? 2);

        // Symbol position: 'before' or 'after'
        if (($currency->symbol_position ?? 'before') === 'before') {
            return $currency->symbol . $formatted;
        } else {
            return $formatted . ' ' . $currency->symbol;
        }
    }
}

