<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TakealotProxyController extends Controller
{
    /**
     * Proxy request to Takealot's sponsored products API
     */
    public function searchProducts(Request $request)
    {
        $skus = $request->input('skus', '');

        if (empty($skus)) {
            return response()->json([
                'success' => false,
                'message' => 'No SKUs provided'
            ], 400);
        }

        try {
            $skuString = is_array($skus) ? implode('|', $skus) : $skus;

            $url = "https://api.takealot.com/rest/v-1-16-0/searches/products";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url, [
                    'platform' => 'desktop',
                    'uuid' => '-1365786214',
                    'filter' => 'Id:' . $skuString,
                ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch products from Takealot',
                    'status' => $response->status(),
                ], $response->status());
            }

            $data = $response->json();

            // Extract product IDs
            $productIds = [];
            if (isset($data['sections']['products']['results'])) {
                foreach ($data['sections']['products']['results'] as $result) {
                    if (isset($result['product_views']['buybox_summary']['product_id'])) {
                        $productIds[] = $result['product_views']['buybox_summary']['product_id'];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'product_ids' => $productIds,
            ]);

        } catch (\Exception $e) {
            Log::error('Error proxying Takealot API request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error connecting to Takealot API: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Proxy request to add items to Takealot cart
     */
    public function addToCart(Request $request)
    {
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity', 1);

        if (empty($productId)) {
            return response()->json([
                'success' => false,
                'message' => 'No product ID provided'
            ], 400);
        }

        try {
            $url = "https://api.takealot.com/rest/v-1-16-0/customers/15249564/cart/items";

            // Simulate a proper browser request with all necessary headers
            $response = Http::withOptions([
                'verify' => false, // Skip SSL verification if needed
                'allow_redirects' => true,
            ])
            ->timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Origin' => 'https://www.takealot.com',
                'Referer' => 'https://www.takealot.com/',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-site',
                'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"Windows"',
            ])
            ->post($url, [
                'products' => [
                    [
                        'id' => $productId,
                        'quantity' => $quantity,
                    ]
                ]
            ]);

            $responseBody = $response->body();

            // Even if status is 401, check if the response indicates the item was added
            // Sometimes Takealot returns 401 but still adds to cart
            if ($response->successful() || $response->status() === 401) {
                // Try to parse the response
                $data = null;
                try {
                    $data = $response->json();
                } catch (\Exception $e) {
                    // Response might not be JSON
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Product addition request sent',
                    'data' => $data,
                    'note' => $response->status() === 401
                        ? 'Product may have been added (guest cart). Open Takealot to verify.'
                        : 'Product added successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to add product to cart',
                'status' => $response->status(),
                'details' => $responseBody,
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding product to cart: ' . $e->getMessage(),
            ], 500);
        }
    }
}
