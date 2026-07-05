<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkDone;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class WorkDoneController extends Controller
{
    public function index()
    {
        $workDone = WorkDone::with(['client', 'lead', 'invoice', 'assignedTo', 'createdBy'])
            ->latest()
            ->get();
        return response()->json($workDone);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'lead_id' => 'nullable|exists:leads,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['nullable', Rule::in(['development', 'design', 'consulting', 'support'])],
            'priority' => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed', 'approved', 'invoiced'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $validated['id'] = (string) Str::uuid();
        $validated['created_by'] = Auth::id();
        $validated['tax_rate'] = $validated['tax_rate'] ?? 16;
        $validated['amount'] = $validated['amount'] ?? 0;
        $validated['total_amount'] = $validated['amount'] + ($validated['amount'] * $validated['tax_rate'] / 100);

        try {
            $workDone = WorkDone::create($validated);
            return response()->json($workDone->load(['client', 'lead', 'invoice']), 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create work',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(WorkDone $workDone)
    {
        return response()->json($workDone->load(['client', 'lead', 'invoice', 'assignedTo', 'createdBy']));
    }

    public function update(Request $request, WorkDone $workDone)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'lead_id' => 'nullable|exists:leads,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => ['nullable', Rule::in(['development', 'design', 'consulting', 'support'])],
            'priority' => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed', 'approved', 'invoiced'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // Recalculate total if amount or tax_rate changed
        if (isset($validated['amount']) || isset($validated['tax_rate'])) {
            $amount = $validated['amount'] ?? $workDone->amount;
            $taxRate = $validated['tax_rate'] ?? $workDone->tax_rate;
            $validated['total_amount'] = $amount + ($amount * $taxRate / 100);
        }

        try {
            $workDone->update($validated);
            return response()->json($workDone->load(['client', 'lead', 'invoice']));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update work',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(WorkDone $workDone)
    {
        try {
            // Check if work has an invoice
            if ($workDone->invoice_id) {
                return response()->json([
                    'error' => 'Cannot delete work that has an invoice'
                ], 422);
            }
            
            $workDone->delete();
            return response()->json(['message' => 'Work deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete work',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create invoice from work done
     * One work done creates exactly one invoice
     */
    public function createInvoice(Request $request, WorkDone $workDone)
    {
        try {
            // Check if invoice already exists
            if ($workDone->invoice_id) {
                return response()->json([
                    'error' => 'Invoice already exists for this work'
                ], 422);
            }

            // Check if work is approved
            if ($workDone->status !== 'approved') {
                return response()->json([
                    'error' => 'Work must be approved before creating an invoice'
                ], 422);
            }

            // Generate invoice number
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Calculate totals
            $subtotal = $workDone->amount;
            $tax = $workDone->amount * $workDone->tax_rate / 100;
            $total = $workDone->total_amount;

            // Create invoice
            $invoice = Invoice::create([
                'id' => (string) Str::uuid(),
                'client_id' => $workDone->client_id,
                'invoice_number' => $invoiceNumber,
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'tax_rate' => $workDone->tax_rate,
                'total' => $total,
                'status' => 'draft',
                'notes' => 'Invoice for: ' . $workDone->title,
                'created_by' => Auth::id(),
            ]);

            // Create invoice item from work done
            InvoiceItem::create([
                'id' => (string) Str::uuid(),
                'invoice_id' => $invoice->id,
                'description' => $workDone->title . ($workDone->description ? ' - ' . $workDone->description : ''),
                'quantity' => 1,
                'unit_price' => $workDone->amount,
                'total' => $workDone->amount,
            ]);

            // Update work done with invoice id
            $workDone->update([
                'invoice_id' => $invoice->id,
                'status' => 'invoiced'
            ]);

            return response()->json([
                'message' => 'Invoice created successfully',
                'work_done' => $workDone->load(['client', 'lead', 'invoice']),
                'invoice' => $invoice->load(['client', 'items'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create invoice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update work status
     */
    public function updateStatus(Request $request, WorkDone $workDone)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'approved', 'invoiced'])],
        ]);

        // If status is completed, set completion date
        if ($validated['status'] === 'completed' && !$workDone->completion_date) {
            $validated['completion_date'] = now();
        }

        $workDone->update($validated);
        return response()->json($workDone);
    }

    /**
     * Get work by status
     */
    public function byStatus($status)
    {
        $workDone = WorkDone::where('status', $status)
            ->with(['client', 'lead', 'invoice'])
            ->get();
        return response()->json($workDone);
    }

    /**
     * Get work by client
     */
    public function byClient($clientId)
    {
        $workDone = WorkDone::where('client_id', $clientId)
            ->with(['client', 'lead', 'invoice'])
            ->get();
        return response()->json($workDone);
    }

    /**
     * Get work statistics
     */
    public function stats()
    {
        $stats = [
            'total' => WorkDone::count(),
            'pending' => WorkDone::where('status', 'pending')->count(),
            'in_progress' => WorkDone::where('status', 'in_progress')->count(),
            'completed' => WorkDone::where('status', 'completed')->count(),
            'approved' => WorkDone::where('status', 'approved')->count(),
            'invoiced' => WorkDone::where('status', 'invoiced')->count(),
            'total_amount' => WorkDone::sum('total_amount'),
        ];

        return response()->json($stats);
    }
}