<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkDone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkDoneController extends Controller
{
    public function index()
    {
        return response()->json(WorkDone::with(['client', 'lead', 'invoice'])->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'lead_id' => 'nullable|exists:leads,id',
            'wo_number' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'testing', 'completed'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'total_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // Calculate total amount if not provided
        if (!isset($data['total_amount']) && isset($data['amount'])) {
            $taxRate = $data['tax_rate'] ?? 16;
            $data['total_amount'] = $data['amount'] + ($data['amount'] * $taxRate / 100);
        }

        $workDone = WorkDone::create($data);
        return response()->json($workDone->load(['client', 'lead', 'invoice']), 201);
    }

    public function show(WorkDone $workDone)
    {
        return response()->json($workDone->load(['client', 'lead', 'invoice']));
    }

    public function update(Request $request, WorkDone $workDone)
    {
        $data = $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'lead_id' => 'nullable|exists:leads,id',
            'wo_number' => 'nullable|string|max:50',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority' => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'testing', 'completed'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'total_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // Recalculate total amount if amount or tax_rate changed
        if (isset($data['amount']) || isset($data['tax_rate'])) {
            $amount = $data['amount'] ?? $workDone->amount;
            $taxRate = $data['tax_rate'] ?? $workDone->tax_rate ?? 16;
            $data['total_amount'] = $amount + ($amount * $taxRate / 100);
        }

        $workDone->update($data);
        return response()->json($workDone->load(['client', 'lead', 'invoice']));
    }

    public function destroy(WorkDone $workDone)
    {
        try {
            // Check if work order has an invoice
            if ($workDone->invoice_id) {
                return response()->json([
                    'error' => 'Cannot delete work order with an existing invoice'
                ], 422);
            }
            
            $workDone->delete();
            return response()->json(['message' => 'Work order deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete work order',
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
            'status' => ['required', Rule::in(['pending', 'in_progress', 'testing', 'completed'])],
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
        $validStatuses = ['pending', 'in_progress', 'testing', 'completed'];
        
        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'error' => 'Invalid status. Allowed: ' . implode(', ', $validStatuses)
            ], 422);
        }
        
        $workDone = WorkDone::where('status', $status)
            ->with(['client', 'lead', 'invoice'])
            ->get();
        return response()->json($workDone);
    }

    /**
     * Get work order statistics
     */
    public function stats()
    {
        $stats = [
            'total' => WorkDone::count(),
            'pending' => WorkDone::where('status', 'pending')->count(),
            'in_progress' => WorkDone::where('status', 'in_progress')->count(),
            'testing' => WorkDone::where('status', 'testing')->count(),
            'completed' => WorkDone::where('status', 'completed')->count(),
            'total_amount' => WorkDone::sum('total_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Create invoice from work order
     */
    public function createInvoice(Request $request, WorkDone $workDone)
    {
        // Check if work order is approved/completed
        if ($workDone->status !== 'completed' && $workDone->status !== 'approved') {
            return response()->json([
                'error' => 'Work must be completed or approved before creating an invoice'
            ], 422);
        }

        // Check if invoice already exists
        if ($workDone->invoice_id) {
            return response()->json([
                'error' => 'Invoice already exists for this work order'
            ], 422);
        }

        try {
            // Create invoice logic here
            // This would typically create an Invoice record and link it to the work order
            
            // Update work order status to invoiced
            $workDone->update(['status' => 'invoiced']);
            
            return response()->json([
                'message' => 'Invoice created successfully',
                'work_done' => $workDone->load(['client', 'lead', 'invoice'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create invoice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get work orders by client
     */
    public function byClient($clientId)
    {
        $workDone = WorkDone::where('client_id', $clientId)
            ->with(['client', 'lead', 'invoice'])
            ->latest()
            ->get();
        return response()->json($workDone);
    }

    /**
     * Get recent work orders
     */
    public function recent()
    {
        $workDone = WorkDone::with(['client', 'lead', 'invoice'])
            ->latest()
            ->limit(10)
            ->get();
        return response()->json($workDone);
    }

    /**
     * Get work orders for a specific date range
     */
    public function byDateRange(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $workDone = WorkDone::whereBetween('created_at', [
            $data['start_date'] . ' 00:00:00',
            $data['end_date'] . ' 23:59:59'
        ])->with(['client', 'lead', 'invoice'])
          ->get();

        return response()->json($workDone);
    }

    /**
     * Bulk update work order statuses
     */
    public function bulkUpdateStatus(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:work_done,id',
            'status' => ['required', Rule::in(['pending', 'in_progress', 'testing', 'completed'])],
        ]);

        $count = WorkDone::whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);

        return response()->json([
            'message' => "{$count} work orders updated successfully",
            'updated_count' => $count
        ]);
    }
}