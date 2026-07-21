<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\Etims\EtimsClientInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncInvoiceToEtimsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // seconds between retries

    public function __construct(public Invoice $invoice)
    {
    }

    public function handle(EtimsClientInterface $client): void
    {
        $this->invoice->update(['etims_status' => 'syncing']);

        $result = $client->submitInvoice($this->invoice);

        if ($result['success']) {
            $this->invoice->update([
                'etims_status' => 'synced',
                'etims_code' => $result['etims_code'],
                'etims_error' => null,
                'etims_synced_at' => now(),
                'etims_attempts' => $this->invoice->etims_attempts + 1,
            ]);
        } else {
            $this->invoice->update([
                'etims_status' => 'failed',
                'etims_error' => $result['error'],
                'etims_attempts' => $this->invoice->etims_attempts + 1,
            ]);
        }
    }
}