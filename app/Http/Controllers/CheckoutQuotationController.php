<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculateCheckoutRequest;
use App\Http\Traits\CheckoutTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

/**
 * Generates a customer-facing "quotation" PDF for the current checkout
 * payload (cart items + address/payment selections). This is an
 * informational pre-order quote only — nothing is persisted. All pricing
 * is re-derived server-side via CheckoutTrait::calculate() exactly like the
 * live checkout-totals endpoint, so client-submitted prices are never trusted.
 */
class CheckoutQuotationController extends Controller
{
    use CheckoutTrait;

    /**
     * Build a configured PDF instance, mirroring the battle-tested dompdf
     * options used by AdminInvoiceQuotationController::buildPdf().
     */
    private function buildPdf(array $data): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadView('checkout.quotation-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isFontSubsettingEnabled', true);
        $pdf->setOption('defaultFont', 'Arial');
        $pdf->setOption('chroot', '/');
        $pdf->setOption('enable_remote', true);
        $pdf->setOption('debugCss', false);
        $pdf->setOption('debugLayout', false);
        $pdf->setOption('debugLayoutLines', false);
        $pdf->setOption('debugLayoutBlocks', false);

        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
                'method' => 'GET',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'follow_location' => 1,
                'max_redirects' => 5,
                'ignore_errors' => false,
            ],
            'https' => [
                'timeout' => 60,
                'method' => 'GET',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'follow_location' => 1,
                'max_redirects' => 5,
                'ignore_errors' => false,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'SNI_enabled' => true,
            ],
        ]);

        $pdf->setHttpContext($context);

        return $pdf;
    }

    /**
     * Download a PDF quotation for the given checkout payload.
     * Reuses the exact same read-only calculation as the live checkout-totals
     * endpoint (CheckoutController::verifyCheckout) — no DB writes occur here.
     */
    public function download(CalculateCheckoutRequest $request)
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        $result = $this->calculate($request);

        // calculate() can return a JsonResponse (out-of-stock/inactive product
        // errors from isOutOfStock()) instead of the costs array — bubble that up.
        if (!is_array($result) || !isset($result['items'], $result['total'])) {
            if ($result instanceof \Illuminate\Http\JsonResponse) {
                return $result;
            }

            return response()->json([
                'success' => false,
                'message' => 'Unable to generate a quotation for the current cart.',
            ], 422);
        }

        // Flatten the per-store grouped items into one line-item list for the PDF.
        $lineItems = collect($result['items'])->pluck('products')->flatten(1);

        $user = $request->user();
        $currency = strtoupper($user->preferred_currency ?? $request->input('currency', 'USD'));

        $data = [
            'result'      => $result,
            'lineItems'   => $lineItems,
            'user'        => $user,
            'currency'    => $currency,
            'generatedAt' => now(),
        ];

        try {
            return $this->buildPdf($data)->download('quotation-' . now()->format('Ymd-His') . '.pdf');
        } catch (\Throwable $e) {
            Log::error('Checkout quotation PDF generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate quotation PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}
