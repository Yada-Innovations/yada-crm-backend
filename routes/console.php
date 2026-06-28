<?php

use App\Jobs\SendInvoiceExpiryReminders;
use Illuminate\Support\Facades\Schedule;

// Run invoice expiry reminder check every day at 8:00 AM
Schedule::job(new SendInvoiceExpiryReminders)->dailyAt('08:00');

// Also run every hour for testing (remove this in production)
// Schedule::job(new SendInvoiceExpiryReminders)->hourly();