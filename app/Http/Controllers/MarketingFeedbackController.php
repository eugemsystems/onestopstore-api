<?php

namespace App\Http\Controllers;

use App\Models\MarketingFeedback;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MarketingFeedbackController extends Controller
{
    /**
     * Submit marketing feedback
     */
    public function submit(Request $request)
    {
        // Convert order_number to string if it's not already
        $request->merge([
            'order_number' => (string) $request->input('order_number'),
        ]);

        $validated = $request->validate([
            'order_number' => 'required|string|min:1',
            'ordering_process_rating' => 'required|in:excellent,good,fair,poor',
            'heard_about_source' => 'required|string',
            'heard_about_other' => 'nullable|string|max:255',
            'additional_comments' => 'nullable|string|max:1000',
            'feedback_token' => 'nullable|string', // Token is optional
        ], [
            'order_number.required' => 'Order number is required.',
            'order_number.string' => 'Order number must be a valid text.',
            'ordering_process_rating.required' => 'Please rate your ordering process.',
            'ordering_process_rating.in' => 'Please select a valid rating.',
            'heard_about_source.required' => 'Please tell us how you heard about us.',
        ]);

        try {
            // Find order
            $order = Order::where('order_number', $validated['order_number'])->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            // Attempt to get authenticated user (optional, doesn't require auth)
            // Try sanctum first, then fallback to regular auth
            $user = null;
            if ($request->bearerToken()) {
                try {
                    $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
                    if ($token) {
                        $user = $token->tokenable;
                    }
                } catch (\Exception $e) {
                    // Token invalid or expired, that's okay
                }
            }

            // If no token auth, try session auth
            if (!$user) {
                $user = Auth::user();
            }

            // Check token validity
            $hasValidToken = !empty($validated['feedback_token']) && $order->verifyFeedbackToken($validated['feedback_token']);

            // Check if user is the owner of the order
            $isOrderOwner = $user && $order->consumer_id == $user->id;

            // Authorization logic:
            // 1. User is logged in and owns the order → Allow ✅
            // 2. Valid token (even if not logged in) → Allow ✅
            // 3. Logged in but NOT the owner AND no valid token → Deny ❌
            // 4. Not logged in AND no valid token → Deny ❌

            if (!$isOrderOwner && !$hasValidToken) {

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please use the link from your email or log in.',
                ], 401);
            }


            // Detect country from IP address
            $ipAddress = $request->ip();
            $countryData = $this->getCountryFromIP($ipAddress);

            // Create feedback
            $feedback = MarketingFeedback::create([
                'user_id' => $user?->id ?? $order->consumer_id,
                'order_number' => $validated['order_number'],
                'order_id' => $order->id,
                'ordering_process_rating' => $validated['ordering_process_rating'],
                'heard_about_source' => $validated['heard_about_source'],
                'heard_about_other' => $validated['heard_about_other'] ?? null,
                'user_name' => $user?->name ?? $order->consumer?->name,
                'user_email' => $user?->email ?? $order->consumer?->email,
                'user_phone' => $user?->phone ?? $order->consumer?->phone,
                'additional_comments' => $validated['additional_comments'] ?? null,
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
                'country_code' => $countryData['code'],
                'country_name' => $countryData['name'],
            ]);


            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback!',
                'feedback_id' => $feedback->id,
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback. Please try again.',
            ], 500);
        }
    }

    /**
     * Check if feedback already submitted for an order
     */
    public function checkSubmitted(Request $request)
    {
        $orderNumber = $request->get('order_number');

        if (!$orderNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Order number is required',
            ], 400);
        }

        $exists = MarketingFeedback::where('order_number', $orderNumber)->exists();

        return response()->json([
            'success' => true,
            'already_submitted' => $exists,
        ]);
    }

    /**
     * Get country from IP address
     * Uses multiple free services as fallback
     */
    private function getCountryFromIP($ip)
    {
        // Default values
        $defaultCountry = [
            'code' => null,
            'name' => 'Unknown',
        ];

        // Skip for local/private IPs
        if ($ip === '127.0.0.1' || $ip === '::1' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $defaultCountry;
        }

        try {
            // Try ip-api.com (free, no key required, 45 requests/minute)
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode");

            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'code' => $data['countryCode'] ?? null,
                        'name' => $data['country'] ?? 'Unknown',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get country from IP', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return $defaultCountry;
    }
}

