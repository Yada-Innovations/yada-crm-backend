<?php
namespace App\Jobs;

use App\Mail\InvoiceExpiryReminderMail;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoiceExpiryReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = Carbon::now();
        $threshold = $now->copy()->addDays(30);

        // Find invoices that:
        // 1. Are not paid
        // 2. Have a due date within 30 days
        // 3. Haven't had a reminder sent recently
        $invoices = Invoice::where('status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$now, $threshold])
            ->where(function ($query) {
                $query->whereNull('reminder_sent_at')
                    ->orWhere('reminder_sent_at', '<', Carbon::now()->subDays(7));
            })
            ->with(['client'])
            ->get();

        Log::info("Invoice expiry reminder job running. Found {$invoices->count()} invoices due.");

        foreach ($invoices as $invoice) {
            $daysLeft = $now->diffInDays($invoice->due_date);

            // Skip if less than 1 day (already overdue)
            if ($daysLeft < 1) continue;

            try {
                Mail::to($invoice->client->email)
                    ->send(new InvoiceExpiryReminderMail($invoice, $daysLeft));

                // Update reminder sent timestamp
                $invoice->update(['reminder_sent_at' => now()]);

                Log::info("Invoice reminder sent to {$invoice->client->email} for {$invoice->invoice_number}. Days left: {$daysLeft}");

            } catch (\Exception $e) {
                Log::error("Failed to send invoice reminder to {$invoice->client->email}: {$e->getMessage()}");
            }
        }
    }
}