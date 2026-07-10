<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Get all payments
     */
    public function index()
    {
        $payments = Payment::with(['invoice', 'invoice.client', 'createdBy'])
            ->latest()
            ->get();
        
        return response()->json($payments);
    }

    /**
     * Record a new payment
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'mpesa', 'cheque', 'credit_card', 'debit_card', 'other'])],
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'payment_date' => 'required|date',
        ]);

        // Get the invoice
        $invoice = Invoice::find($data['invoice_id']);
        if (!$invoice) {
            return response()->json([
                'error' => 'Invoice not found'
            ], 404);
        }

        // Calculate total paid amount for this invoice
        $paidAmount = Payment::where('invoice_id', $data['invoice_id'])->sum('amount');
        $balance = $invoice->total - $paidAmount;

        // Check if payment exceeds balance
        if ($data['amount'] > $balance) {
            return response()->json([
                'error' => 'Payment amount exceeds remaining balance',
                'balance' => $balance,
                'balance_formatted' => 'KES ' . number_format($balance, 2),
                'max_amount' => $balance
            ], 422);
        }

        // Create the payment
        $payment = Payment::create([
            'invoice_id' => $data['invoice_id'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'payment_date' => $data['payment_date'],
            'created_by' => Auth::id(),
        ]);

        // Update invoice status based on payment
        $newPaidAmount = $paidAmount + $data['amount'];
        if ($newPaidAmount >= $invoice->total) {
            $invoice->update(['status' => 'paid']);
        } elseif ($newPaidAmount > 0) {
            $invoice->update(['status' => 'partial']);
        }

        // Load relationships for response
        $payment->load(['invoice', 'invoice.client', 'createdBy']);

        return response()->json([
            'message' => 'Payment recorded successfully',
            'payment' => $payment,
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total' => $invoice->total,
                'paid_amount' => $newPaidAmount,
                'balance' => $invoice->total - $newPaidAmount,
                'status' => $invoice->status,
            ]
        ], 201);
    }

    /**
     * Get a single payment
     */
    public function show(Payment $payment)
    {
        $payment->load(['invoice', 'invoice.client', 'createdBy']);
        return response()->json($payment);
    }

    /**
     * Get payments for a specific invoice
     */
    public function byInvoice($invoiceId)
    {
        // Check if invoice exists
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return response()->json([
                'error' => 'Invoice not found'
            ], 404);
        }

        $payments = Payment::where('invoice_id', $invoiceId)
            ->with(['createdBy'])
            ->latest()
            ->get();

        $paidAmount = $payments->sum('amount');
        $balance = $invoice->total - $paidAmount;

        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total' => $invoice->total,
                'paid_amount' => $paidAmount,
                'balance' => $balance,
                'status' => $invoice->status,
            ],
            'payments' => $payments
        ]);
    }

    /**
     * Update a payment
     */
    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'payment_method' => ['sometimes', Rule::in(['cash', 'bank_transfer', 'mpesa', 'cheque', 'credit_card', 'debit_card', 'other'])],
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'payment_date' => 'sometimes|date',
        ]);

        // If amount is being updated, check if it exceeds balance
        if (isset($data['amount']) && $data['amount'] != $payment->amount) {
            $invoice = Invoice::find($payment->invoice_id);
            $totalPaid = Payment::where('invoice_id', $payment->invoice_id)
                ->where('id', '!=', $payment->id)
                ->sum('amount');
            
            $newTotalPaid = $totalPaid + $data['amount'];
            
            if ($newTotalPaid > $invoice->total) {
                return response()->json([
                    'error' => 'Payment amount would exceed invoice total',
                    'max_amount' => $invoice->total - $totalPaid
                ], 422);
            }
        }

        $payment->update($data);

        // Update invoice status
        $this->updateInvoiceStatus($payment->invoice_id);

        $payment->load(['invoice', 'invoice.client', 'createdBy']);

        return response()->json([
            'message' => 'Payment updated successfully',
            'payment' => $payment
        ]);
    }

    /**
     * Delete a payment
     */
    public function destroy(Payment $payment)
    {
        try {
            $invoiceId = $payment->invoice_id;
            
            // Delete the payment
            $payment->delete();
            
            // Update invoice status
            $this->updateInvoiceStatus($invoiceId);
            
            return response()->json([
                'message' => 'Payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function stats()
    {
        $stats = [
            'total_payments' => Payment::count(),
            'total_amount' => Payment::sum('amount'),
            'today' => Payment::whereDate('payment_date', now())->sum('amount'),
            'this_week' => Payment::whereBetween('payment_date', [
                now()->startOfWeek(), 
                now()->endOfWeek()
            ])->sum('amount'),
            'this_month' => Payment::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),
            'this_year' => Payment::whereYear('payment_date', now()->year)->sum('amount'),
            'by_method' => Payment::select('payment_method', 
                    DB::raw('count(*) as count'), 
                    DB::raw('sum(amount) as total')
                )
                ->groupBy('payment_method')
                ->get()
                ->map(function ($item) {
                    $methodLabels = [
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'mpesa' => 'M-Pesa',
                        'cheque' => 'Cheque',
                        'credit_card' => 'Credit Card',
                        'debit_card' => 'Debit Card',
                        'other' => 'Other',
                    ];
                    
                    return [
                        'method' => $item->payment_method,
                        'method_label' => $methodLabels[$item->payment_method] ?? $item->payment_method,
                        'count' => (int) $item->count,
                        'total' => (float) $item->total,
                        'formatted_total' => 'KES ' . number_format((float) $item->total, 2),
                    ];
                }),
            'recent' => Payment::with(['invoice', 'createdBy'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => (float) $payment->amount,
                        'formatted_amount' => 'KES ' . number_format((float) $payment->amount, 2),
                        'payment_method' => $payment->payment_method,
                        'reference' => $payment->reference,
                        'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                        'invoice_number' => $payment->invoice?->invoice_number,
                        'client_name' => $payment->invoice?->client?->name,
                        'created_by' => $payment->createdBy?->name,
                    ];
                }),
        ];
        
        return response()->json($stats);
    }

    /**
     * Update invoice status based on payments
     */
    private function updateInvoiceStatus($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return;
        }

        $totalPaid = Payment::where('invoice_id', $invoiceId)->sum('amount');
        
        if ($totalPaid <= 0) {
            // If no payments, set status back to sent (or draft)
            if (in_array($invoice->status, ['paid', 'partial'])) {
                $invoice->update(['status' => 'sent']);
            }
        } elseif ($totalPaid >= $invoice->total) {
            $invoice->update(['status' => 'paid']);
        } else {
            $invoice->update(['status' => 'partial']);
        }
    }
}