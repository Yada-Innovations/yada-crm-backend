<?php

use App\Jobs\SendQuoteCreatedEmail;
use App\Models\Quote;
use App\Models\Client;
use Illuminate\Support\Str;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Email System ===\n\n";

$client = Client::where('email', 'rosemugo2003@gmail.com')->first();

if ($client) {
    echo "Client found: " . $client->name . "\n";
    echo "Client email: " . $client->email . "\n\n";
    
    $quote = Quote::create([
        'id' => (string) Str::uuid(),
        'quote_number' => 'Q-' . date('Ymd') . '-' . Str::random(6),
        'client_id' => $client->id,
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 160,
        'total' => 1160,
        'created_by' => 1,
    ]);

    echo "Quote created: " . $quote->quote_number . "\n";
    
    SendQuoteCreatedEmail::dispatch($quote);
    
    echo "Email job dispatched to: " . $client->email . "\n";
    echo "Check the queue worker terminal for processing.\n";
} else {
    echo "Client not found\n";
}

echo "\nDone!\n";
