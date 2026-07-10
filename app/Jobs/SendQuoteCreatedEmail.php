<?php

namespace App\Jobs;

use App\Mail\QuoteCreatedMail;
use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendQuoteCreatedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 60;

    /**
     * The quote instance.
     */
    protected $quote;

    /**
     * Create a new job instance.
     */
    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $client = $this->quote->client;
        
        if ($client && $client->email) {
            Mail::to($client->email)->send(new QuoteCreatedMail($this->quote));
            Log::info('Quote email sent', [
                'quote_id' => $this->quote->id,
                'client_email' => $client->email
            ]);
        } else {
            Log::warning('Quote email not sent - no client email', [
                'quote_id' => $this->quote->id
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['quote', 'email', 'quote:' . $this->quote->id];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendQuoteCreatedEmail failed', [
            'quote_id' => $this->quote->id,
            'client_email' => $this->quote->client?->email,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}