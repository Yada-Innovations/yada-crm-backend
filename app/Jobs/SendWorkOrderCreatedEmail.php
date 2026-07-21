<?php

namespace App\Jobs;

use App\Mail\WorkOrderCreatedMail;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendWorkOrderCreatedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    protected $workOrder;

    public function __construct(WorkOrder $workOrder)
    {
        $this->workOrder = $workOrder;
    }

    public function handle(): void
    {
        $client = $this->workOrder->client;
        
        if ($client && $client->email) {
            Mail::to($client->email)->send(new WorkOrderCreatedMail($this->workOrder));
            Log::info('Work order email sent', [
                'work_order_id' => $this->workOrder->id,
                'client_email' => $client->email
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendWorkOrderCreatedEmail failed', [
            'work_order_id' => $this->workOrder->id,
            'client_email' => $this->workOrder->client?->email,
            'error' => $exception->getMessage()
        ]);
    }
}