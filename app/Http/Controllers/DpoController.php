<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DpoController extends Controller
{
    /**
     * Handle browser return/cancel from DPO and update order status via common webhook parser,
     * then redirect to the front-end order details page.
     */
    public function return(Request $request)
    {
        // Update status using the same handler as server-to-server notifications
        \App\Payments\PdoZambia::webhookHandler($request);

        // Redirect back to front-end destination
        $redirect = (string) $request->query('redirect', config('app.url'));
        return redirect()->away($redirect);
    }

    /**
     * Initiate a DPO payment and redirect to the hosted payment page.
     *
     * Expects query params from PdoZambia::getIntent: return_url, cancel_url, amount, currency,
     * description, reference, customer_email, customer_name.
     */
    public function redirect(Request $request)
    {
        $cfg = config('services.dpo', []);
        $base = rtrim((string) ($cfg['base_url'] ?? ''), '/');

        // Build XML payload per DPO v6 docs (Request=createToken)
        $amount   = number_format((float) $request->input('amount', 0), 2, '.', '');
        $currency = (string) $request->input('currency', 'ZMW');
        $redirect = (string) $request->input('return_url');
        $back     = (string) $request->input('cancel_url', $redirect);
        $ref      = (string) $request->input('reference');
        $ptl      = (int) ($cfg['ptl'] ?? 5);

        // Validate callback URLs: must be public HTTPS (CloudFront/WAF often blocks localhost/http)
        $isInvalid = function ($url) {
            if (!$url) return true;
            $u = strtolower($url);
            if (str_contains($u, 'localhost')) return true;
            if (!str_starts_with($u, 'https://')) return true;
            return false;
        };
        if ($isInvalid($redirect) || $isInvalid($back)) {
            return response('DPO requires publicly accessible HTTPS RedirectURL and BackURL', 422);
        }

        $xml = new \SimpleXMLElement('<API3G/>' );
        $xml->addChild('CompanyToken', (string) ($cfg['company_token'] ?? ''));
        $xml->addChild('Request', 'createToken');
        $t = $xml->addChild('Transaction');
        $t->addChild('PaymentAmount', $amount);
        $t->addChild('PaymentCurrency', $currency);
        $t->addChild('CompanyRef', $ref);
        $t->addChild('CompanyRefUnique', '1');
        $t->addChild('RedirectURL', htmlspecialchars($redirect, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
        $t->addChild('BackURL', htmlspecialchars($back, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
        $t->addChild('PTL', (string) $ptl);
        $t->addChild('TransactionType', 'Payment');

        // Result URL at root as per v6 docs
        $xml->addChild('ResultURL', htmlspecialchars(route('dpo.webhook'), ENT_XML1 | ENT_COMPAT, 'UTF-8'));

        $serviceType = trim((string) ($cfg['service_type'] ?? ''));
        if ($serviceType !== '') {
            $services = $xml->addChild('Services');
            $srv = $services->addChild('Service');
            $srv->addChild('ServiceType', $serviceType);
            $srv->addChild('ServiceDescription', htmlspecialchars((string) $request->input('description', ''), ENT_XML1 | ENT_COMPAT, 'UTF-8'));
            $srv->addChild('ServiceDate', date('Y/m/d H:i'));
        }

        $initEndpoint = (string) ($cfg['init_endpoint'] ?? '/API/v6/');
        $redirectTemplate = (string) ($cfg['redirect_template'] ?? '/pay/v1/redirect/{token}');

        try {
            // Send XML to API v6 endpoint (try configured and generic)
            $candidates = [$initEndpoint, '/API/v6/'];
            $resp = null;
            $url  = null;
            foreach ($candidates as $ep) {
                $url = $base . $ep;
                $resp = Http::timeout(25)
                    ->withHeaders([
                        'User-Agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) DPO/Client',
                        'Accept'       => 'application/xml',
                        'Content-Type' => 'application/xml',
                    ])
                    ->send('POST', $url, ['body' => $xml->asXML()]);
                if ($resp->successful()) {
                    break;
                }
            }

            $body = $resp->body();


            $xmlResp = @simplexml_load_string($body);
            if ($xmlResp) {
                $arr = json_decode(json_encode($xmlResp), true);
                $token = $arr['TransToken'] ?? $arr['Token'] ?? null;
                $redirectUrl = $arr['RedirectURL'] ?? null;

                if (!$redirectUrl && $token) {
                    $redirectUrl = $base . str_replace('{token}', $token, $redirectTemplate);
                }

                if ($redirectUrl) {
                    return redirect()->away($redirectUrl);
                }
            }

            return response('Unable to initiate DPO payment', 502);

        } catch (\Throwable $e) {
            return response('DPO redirect error', 500);
        }
    }
}
