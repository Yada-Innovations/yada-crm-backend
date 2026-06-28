<?php
namespace App\Jobs;

use App\Mail\RenewalReminderMail;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRenewalReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now       = Carbon::now();
        $threshold = $now->copy()->addDays(30);

        // Find active subscriptions expiring within 30 days
        // where reminder hasn't been sent yet
        $subscriptions = Subscription::where('status', 'active')
            ->whereBetween('ends_at', [$now, $threshold])
            ->where('renewal_alert_sent', false)
            ->with(['client', 'plan'])
            ->get();

        Log::info("Renewal reminder job running. Found {$subscriptions->count()} subscriptions due.");

        foreach ($subscriptions as $subscription) {
            $daysLeft = $now->diffInDays($subscription->ends_at);

            try {
                Mail::to($subscription->client->email)
                    ->send(new RenewalReminderMail($subscription));

                // Mark as sent so we don't spam
                $subscription->update(['renewal_alert_sent' => true]);

                Log::info("Renewal reminder sent to {$subscription->client->email} for {$subscription->client->name}. Days left: {$daysLeft}");
            } catch (\Exception $e) {
                Log::error("Failed to send renewal reminder to {$subscription->client->email}: {$e->getMessage()}");
            }
        }
    }
}