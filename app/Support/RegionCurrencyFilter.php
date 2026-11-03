<?php

namespace App\Support;

/**
 * Maps a currency code to the product region flags (zambia_only,
 * zimbabwe_only, sa_only) it should exclude - a product with none of the
 * three set is global and always included, one flagged for a different
 * region is excluded. Same convention as GenerateGoogleMerchantFeed:
 * ZMW -> Zambia, ZAR -> South Africa, everything else (USD, etc.) -> Zimbabwe.
 *
 * Shared by CustomFeedGenerator and OrderProductsExport so the two don't
 * drift apart on what "filter by region" means.
 */
class RegionCurrencyFilter
{
    public static function excludedColumns(string $currencyCode): array
    {
        return match (strtoupper($currencyCode)) {
            'ZMW' => ['zimbabwe_only', 'sa_only'],
            'ZAR' => ['zambia_only', 'zimbabwe_only'],
            default => ['zambia_only', 'sa_only'],
        };
    }
}
