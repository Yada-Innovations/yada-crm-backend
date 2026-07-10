<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendQuoteCreatedEmail;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class QuoteController extends Controller
{
    /**
     * Display a listing of quotes.
     */
    public function index()
    {
        $quotes = Quote::with(['client', 'service', 'createdBy'])->get();
        return response()->json($quotes);
    }

    /**
     * Store a newly created quote.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Client & Service
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'nullable|exists:services,id',

            // Features
            'features' => 'required|array|min:1',
            'features.*.name' => 'required|string|max:255',
            'features.*.description' => 'nullable|string',
            'features.*.quantity' => 'required|numeric|min:1',
            'features.*.unit_price' => 'required|numeric|min:0',
            'features.*.total' => 'nullable|numeric|min:0',

            // Financials
            'subtotal' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'total' => 'nullable|numeric|min:0',

            // Dates and Status
            'valid_until' => 'required|date|after_or_equal:today',
            'status' => ['nullable', Rule::in(['draft', 'sent', 'approved', 'rejected'])],

            // Additional Info
            'notes' => 'nullable|string',
            'supporting_document' => 'nullable|string|max:255',
        ]);

        // Set defaults
        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['tax_rate'] = $validated['tax_rate'] ?? 16;

        // Calculate totals if not provided
        if (!isset($validated['subtotal']) || !isset($validated['tax']) || !isset($validated['total'])) {
            $subtotal = 0;
            foreach ($validated['features'] as $feature) {
                $subtotal += ($feature['quantity'] ?? 0) * ($feature['unit_price'] ?? 0);
            }
            $tax = ($subtotal * $validated['tax_rate']) / 100;
            $total = $subtotal + $tax;

            $validated['subtotal'] = $subtotal;
            $validated['tax'] = $tax;
            $validated['total'] = $total;
        }

        // Set the created_by
        $validated['created_by'] = Auth::id();

        // Generate UUID
        $validated['id'] = (string) Str::uuid();

        try {
            $quote = Quote::create($validated);

            // Dispatch the email job to notify the client
            SendQuoteCreatedEmail::dispatch($quote);

            return response()->json($quote->load(['client', 'service', 'createdBy']), 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create quote',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified quote.
     */
    public function show(Quote $quote)
    {
        return response()->json($quote->load(['client', 'service', 'createdBy']));
    }

    /**
     * Update the specified quote.
     */
    public function update(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            // Client & Service
            'client_id' => 'sometimes|exists:clients,id',
            'service_id' => 'nullable|exists:services,id',

            // Features
            'features' => 'sometimes|array|min:1',
            'features.*.name' => 'required|string|max:255',
            'features.*.description' => 'nullable|string',
            'features.*.quantity' => 'required|numeric|min:1',
            'features.*.unit_price' => 'required|numeric|min:0',
            'features.*.total' => 'nullable|numeric|min:0',

            // Financials
            'subtotal' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'total' => 'nullable|numeric|min:0',

            // Dates and Status
            'valid_until' => 'sometimes|date|after_or_equal:today',
            'status' => ['nullable', Rule::in(['draft', 'sent', 'approved', 'rejected'])],

            // Additional Info
            'notes' => 'nullable|string',
            'supporting_document' => 'nullable|string|max:255',
        ]);

        // Recalculate totals if features or tax_rate changed
        if (isset($validated['features']) || isset($validated['tax_rate'])) {
            $features = $validated['features'] ?? $quote->features;
            $taxRate = $validated['tax_rate'] ?? $quote->tax_rate;

            $subtotal = 0;
            foreach ($features as $feature) {
                $subtotal += ($feature['quantity'] ?? 0) * ($feature['unit_price'] ?? 0);
            }
            $tax = ($subtotal * $taxRate) / 100;
            $total = $subtotal + $tax;

            $validated['subtotal'] = $subtotal;
            $validated['tax'] = $tax;
            $validated['total'] = $total;
        }

        try {
            $quote->update($validated);

            return response()->json($quote->load(['client', 'service', 'createdBy']));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update quote',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified quote.
     */
    public function destroy(Quote $quote)
    {
        try {
            $quote->delete();
            return response()->json(['message' => 'Quote deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete quote',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update quote status.
     */
    public function updateStatus(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['draft', 'sent', 'approved', 'rejected'])],
        ]);

        $quote->status = $validated['status'];
        $quote->save();

        return response()->json($quote);
    }

    /**
     * Send quote to client.
     */
    public function send(Request $request, Quote $quote)
    {
        // Re-send the quote email and mark as sent
        SendQuoteCreatedEmail::dispatch($quote);

        $quote->status = 'sent';
        $quote->save();

        return response()->json([
            'message' => 'Quote sent successfully',
            'quote' => $quote
        ]);
    }

    /**
     * Approve quote.
     */
    public function approve(Quote $quote)
    {
        $quote->status = 'approved';
        $quote->save();

        return response()->json([
            'message' => 'Quote approved successfully',
            'quote' => $quote
        ]);
    }

    /**
     * Reject quote.
     */
    public function reject(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $quote->status = 'rejected';
        $quote->notes = ($quote->notes ? $quote->notes . "\n" : '') . 'Rejected: ' . ($validated['reason'] ?? 'No reason provided');
        $quote->save();

        return response()->json([
            'message' => 'Quote rejected',
            'quote' => $quote
        ]);
    }

    /**
     * Get quote statistics.
     */
    public function stats()
    {
        $stats = [
            'total' => Quote::count(),
            'draft' => Quote::where('status', 'draft')->count(),
            'sent' => Quote::where('status', 'sent')->count(),
            'approved' => Quote::where('status', 'approved')->count(),
            'rejected' => Quote::where('status', 'rejected')->count(),
            'total_value' => Quote::sum('total'),
        ];

        return response()->json($stats);
    }

    /**
     * Generate PDF for quote.
     */
    public function generatePdf(Quote $quote)
    {
        // This would generate a PDF for the quote
        // Implementation depends on your PDF library (e.g., DomPDF, TCPDF)

        return response()->json([
            'message' => 'PDF generation not implemented yet',
            'quote' => $quote
        ]);
    }
}