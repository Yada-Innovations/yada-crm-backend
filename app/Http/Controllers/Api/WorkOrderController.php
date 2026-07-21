<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkOrderController extends Controller
{
    public function index()
    {
        return response()->json(WorkOrder::with(['client', 'quote', 'lead', 'invoice'])->latest()->get());
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
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'technical_review', 'testing', 'completed'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'total_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'technical_review_date' => 'nullable|date',
            'technical_review_notes' => 'nullable|string',
            'technical_review_approved' => 'nullable|boolean',
            'book_of_technical_reviews_date' => 'nullable|date',
            'book_of_technical_reviews_reference' => 'nullable|string',
            'technical_reviewer' => 'nullable|string',
        ]);

        // ── PREVENT DUPLICATE WORK ORDER FOR THE SAME QUOTE ──
        if (!empty($data['quote_id'])) {
            $existing = WorkOrder::where('quote_id', $data['quote_id'])->first();
            if ($existing) {
                return response()->json([
                    'error' => 'A work order already exists for this quote.',
                    'existing_work_order_id' => $existing->id,
                ], 422);
            }
        }

        // Calculate total amount if not provided
        if (!isset($data['total_amount']) && isset($data['amount'])) {
            $taxRate = $data['tax_rate'] ?? 16;
            $data['total_amount'] = $data['amount'] + ($data['amount'] * $taxRate / 100);
        }

        $workOrder = WorkOrder::create($data);
        return response()->json($workOrder->load(['client', 'quote', 'lead', 'invoice']), 201);
    }

    public function show(WorkOrder $workOrder)
    {
        return response()->json($workOrder->load(['client', 'quote', 'lead', 'invoice']));
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'lead_id' => 'nullable|exists:leads,id',
            'wo_number' => 'nullable|string|max:50',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority' => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'technical_review', 'testing', 'completed'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'total_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'technical_review_date' => 'nullable|date',
            'technical_review_notes' => 'nullable|string',
            'technical_review_approved' => 'nullable|boolean',
            'book_of_technical_reviews_date' => 'nullable|date',
            'book_of_technical_reviews_reference' => 'nullable|string',
            'technical_reviewer' => 'nullable|string',
        ]);

        // Recalculate total amount if amount or tax_rate changed
        if (isset($data['amount']) || isset($data['tax_rate'])) {
            $amount = $data['amount'] ?? $workOrder->amount;
            $taxRate = $data['tax_rate'] ?? $workOrder->tax_rate ?? 16;
            $data['total_amount'] = $amount + ($amount * $taxRate / 100);
        }

        $workOrder->update($data);
        return response()->json($workOrder->load(['client', 'quote', 'lead', 'invoice']));
    }

    public function destroy(WorkOrder $workOrder)
    {
        try {
            if ($workOrder->invoice_id) {
                return response()->json([
                    'error' => 'Cannot delete work order with an existing invoice'
                ], 422);
            }
            $workOrder->delete();
            return response()->json(['message' => 'Work order deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete work order',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_progress', 'technical_review', 'testing', 'completed'])],
        ]);

        if ($validated['status'] === 'completed' && !$workOrder->completion_date) {
            $validated['completion_date'] = now();
        }

        $workOrder->update($validated);
        return response()->json($workOrder);
    }

    public function byStatus($status)
    {
        $validStatuses = ['pending', 'in_progress', 'technical_review', 'testing', 'completed'];
        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'error' => 'Invalid status. Allowed: ' . implode(', ', $validStatuses)
            ], 422);
        }
        $workOrders = WorkOrder::where('status', $status)
            ->with(['client', 'quote', 'lead', 'invoice'])
            ->get();
        return response()->json($workOrders);
    }

    public function stats()
    {
        $stats = [
            'total' => WorkOrder::count(),
            'pending' => WorkOrder::where('status', 'pending')->count(),
            'in_progress' => WorkOrder::where('status', 'in_progress')->count(),
            'technical_review' => WorkOrder::where('status', 'technical_review')->count(),
            'testing' => WorkOrder::where('status', 'testing')->count(),
            'completed' => WorkOrder::where('status', 'completed')->count(),
            'total_amount' => WorkOrder::sum('total_amount'),
        ];
        return response()->json($stats);
    }

    public function createInvoice(Request $request, WorkOrder $workOrder)
    {
        if ($workOrder->status !== 'completed' && $workOrder->status !== 'approved') {
            return response()->json([
                'error' => 'Work must be completed or approved before creating an invoice'
            ], 422);
        }
        if ($workOrder->invoice_id) {
            return response()->json([
                'error' => 'Invoice already exists for this work order'
            ], 422);
        }
        try {
            $workOrder->update(['status' => 'invoiced']);
            return response()->json([
                'message' => 'Invoice created successfully',
                'work_order' => $workOrder->load(['client', 'quote', 'lead', 'invoice'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create invoice',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function byClient($clientId)
    {
        $workOrders = WorkOrder::where('client_id', $clientId)
            ->with(['client', 'quote', 'lead', 'invoice'])
            ->latest()
            ->get();
        return response()->json($workOrders);
    }

    public function recent()
    {
        $workOrders = WorkOrder::with(['client', 'quote', 'lead', 'invoice'])
            ->latest()
            ->limit(10)
            ->get();
        return response()->json($workOrders);
    }

    public function byDateRange(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        $workOrders = WorkOrder::whereBetween('created_at', [
            $data['start_date'] . ' 00:00:00',
            $data['end_date'] . ' 23:59:59'
        ])->with(['client', 'quote', 'lead', 'invoice'])
          ->get();
        return response()->json($workOrders);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:work_done,id',
            'status' => ['required', Rule::in(['pending', 'in_progress', 'technical_review', 'testing', 'completed'])],
        ]);
        $count = WorkOrder::whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);
        return response()->json([
            'message' => "{$count} work orders updated successfully",
            'updated_count' => $count
        ]);
    }
}