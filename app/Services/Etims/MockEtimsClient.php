<?php

namespace App\Services\Etims;

use App\Models\Invoice;
use Illuminate\Support\Str;

class MockEtimsClient implements EtimsClientInterface
{
    public function submitInvoice(Invoice $invoice): array
    {
        // Simulate network latency like the real KRA API would have
        usleep(300000); // 0.3s

        // Simulate occasional realistic failure so retry logic gets exercised
        if (rand(1, 20) === 1) {
            return [
                'success' => false,
                'etims_code' => null,
                'error' => 'Simulated timeout from mock eTIMS server (this is expected sometimes in mock mode)',
            ];
        }

        return [
            'success' => true,
            'etims_code' => 'MOCK-' . strtoupper(Str::random(10)),
            'error' => null,
        ];
    }
}