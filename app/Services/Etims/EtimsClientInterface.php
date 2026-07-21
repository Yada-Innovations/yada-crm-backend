<?php

namespace App\Services\Etims;

use App\Models\Invoice;

interface EtimsClientInterface
{
    /**
     * Submit an invoice to eTIMS.
     * Must return: ['success' => bool, 'etims_code' => ?string, 'error' => ?string]
     */
    public function submitInvoice(Invoice $invoice): array;
}