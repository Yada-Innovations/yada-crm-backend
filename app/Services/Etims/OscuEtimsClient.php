<?php

namespace App\Services\Etims;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OscuEtimsClient implements EtimsClientInterface
{
    public function submitInvoice(Invoice $invoice): array
    {
        $config = config('etims.oscu');

        if (empty($config['pin']) || empty($config['cmc_key']) || empty($config['base_url'])) {
            $message = 'OSCU credentials are not configured yet (PIN / CMC key / base URL missing). '
                . 'Add them to .env once KRA registration is complete.';
            Log::warning('eTIMS OSCU submission skipped: ' . $message, ['invoice_id' => $invoice->id]);

            return [
                'success' => false,
                'etims_code' => null,
                'error' => $message,
            ];
        }

        try {
            // NOTE: This payload shape is a placeholder. The real OSCU API spec
            // (KRA eTIMS OSCU Integration Guide) defines the exact field names,
            // item classification codes, and signature requirements — this must
            // be finalized against the actual spec once device registration is done.
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($config['base_url'] . '/trnsSalesSaveWr', [
                'tin' => $config['pin'],
                'bhfId' => $config['branch_id'],
                'invcNo' => $invoice->invoice_number,
                'totAmt' => (float) $invoice->total,
                'taxAmt' => (float) $invoice->tax,
                // ... remaining OSCU-specific fields go here once spec is confirmed
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'etims_code' => $data['data']['rcptNo'] ?? null,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'etims_code' => null,
                'error' => 'OSCU rejected the request: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('eTIMS OSCU submission failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            return [
                'success' => false,
                'etims_code' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}