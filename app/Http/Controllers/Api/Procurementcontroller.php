<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\PurchaseRequisition;
use App\Models\Purchase;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ProcurementController extends Controller
{
    // ═══════════════════ VENDORS ═══════════════════

    public function vendorsIndex(Request $request)
    {
        $query = Vendor::query()->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->search) {
            $query->search($request->search);
        }

        return response()->json($query->get());
    }

    public function vendorsStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $vendor = Vendor::create([
            'id' => (string) Str::uuid(),
            ...$data,
            'status' => $data['status'] ?? 'active',
            'created_by' => $request->user()->id,
        ]);

        return response()->json($vendor, 201);
    }

    public function vendorsShow(Vendor $vendor)
    {
        return response()->json($vendor->load('purchases'));
    }

    public function vendorsUpdate(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $vendor->update($data);
        return response()->json($vendor);
    }

    public function vendorsDestroy(Vendor $vendor)
    {
        $vendor->delete();
        return response()->json(['message' => 'Vendor deleted']);
    }

    // ═══════════════════ REQUISITIONS ═══════════════════

    public function requisitionsIndex(Request $request)
    {
        $query = PurchaseRequisition::with(['requester', 'approver'])->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->requested_by) {
            $query->where('requested_by', $request->requested_by);
        }

        return response()->json($query->get());
    }

    public function requisitionsStore(Request $request)
    {
        $data = $request->validate([
            'department' => 'nullable|string|max:255',
            'item_description' => 'required|string',
            'quantity' => 'nullable|integer|min:1',
            'estimated_cost' => 'nullable|numeric|min:0',
            'justification' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $number = 'REQ-' . str_pad((string) (PurchaseRequisition::count() + 1), 5, '0', STR_PAD_LEFT);

        $requisition = PurchaseRequisition::create([
            'id' => (string) Str::uuid(),
            'requisition_number' => $number,
            'requested_by' => $request->user()->id,
            'department' => $data['department'] ?? null,
            'item_description' => $data['item_description'],
            'quantity' => $data['quantity'] ?? 1,
            'estimated_cost' => $data['estimated_cost'] ?? 0,
            'justification' => $data['justification'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($requisition->load('requester'), 201);
    }

    public function requisitionsShow(PurchaseRequisition $requisition)
    {
        return response()->json($requisition->load(['requester', 'approver', 'purchases']));
    }

    public function requisitionsUpdate(Request $request, PurchaseRequisition $requisition)
    {
        $data = $request->validate([
            'department' => 'nullable|string|max:255',
            'item_description' => 'sometimes|string',
            'quantity' => 'nullable|integer|min:1',
            'estimated_cost' => 'nullable|numeric|min:0',
            'justification' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $requisition->update($data);
        return response()->json($requisition->load(['requester', 'approver']));
    }

    public function requisitionsDestroy(PurchaseRequisition $requisition)
    {
        $requisition->delete();
        return response()->json(['message' => 'Requisition deleted']);
    }

    public function requisitionsApprove(Request $request, PurchaseRequisition $requisition)
    {
        $requisition->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => Carbon::now(),
        ]);

        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $requisition->requested_by,
            'type' => 'success',
            'title' => 'Requisition Approved',
            'message' => "Your requisition {$requisition->requisition_number} was approved.",
            'link' => "/procurement/requisitions/{$requisition->id}",
        ]);

        return response()->json($requisition->load(['requester', 'approver']));
    }

    public function requisitionsReject(Request $request, PurchaseRequisition $requisition)
    {
        $data = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $requisition->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => Carbon::now(),
            'notes' => $data['notes'] ?? $requisition->notes,
        ]);

        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $requisition->requested_by,
            'type' => 'warning',
            'title' => 'Requisition Rejected',
            'message' => "Your requisition {$requisition->requisition_number} was rejected.",
            'link' => "/procurement/requisitions/{$requisition->id}",
        ]);

        return response()->json($requisition->load(['requester', 'approver']));
    }

    // ═══════════════════ PURCHASES ═══════════════════

    public function purchasesIndex(Request $request)
    {
        $query = Purchase::with(['vendor', 'requisition', 'creator'])->latest();

        if ($request->vendor_id) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->date_from) {
            $query->where('purchase_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('purchase_date', '<=', $request->date_to);
        }

        return response()->json($query->get());
    }

    public function purchasesStore(Request $request)
    {
        $data = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'requisition_id' => 'nullable|exists:purchase_requisitions,id',
            'item_description' => 'required|string',
            'category' => 'nullable|string|max:100',
            'quantity' => 'nullable|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'purchase_date' => 'required|date',
            'payment_status' => 'nullable|in:pending,partial,paid',
            'status' => 'nullable|in:ordered,received,cancelled',
            'notes' => 'nullable|string',
        ]);

        $quantity = $data['quantity'] ?? 1;
        $unitCost = $data['unit_cost'] ?? 0;
        $totalCost = round($quantity * $unitCost, 2);

        $number = 'PO-' . str_pad((string) (Purchase::count() + 1), 5, '0', STR_PAD_LEFT);

        $purchase = Purchase::create([
            'id' => (string) Str::uuid(),
            'purchase_number' => $number,
            'vendor_id' => $data['vendor_id'] ?? null,
            'requisition_id' => $data['requisition_id'] ?? null,
            'item_description' => $data['item_description'],
            'category' => $data['category'] ?? null,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'purchase_date' => $data['purchase_date'],
            'payment_status' => $data['payment_status'] ?? 'pending',
            'status' => $data['status'] ?? 'ordered',
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        // If linked to a requisition, mark it converted
        if (!empty($data['requisition_id'])) {
            PurchaseRequisition::where('id', $data['requisition_id'])->update(['status' => 'converted']);
        }

        return response()->json($purchase->load(['vendor', 'requisition']), 201);
    }

    public function purchasesShow(Purchase $purchase)
    {
        return response()->json($purchase->load(['vendor', 'requisition', 'creator']));
    }

    public function purchasesUpdate(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'item_description' => 'sometimes|string',
            'category' => 'nullable|string|max:100',
            'quantity' => 'nullable|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'purchase_date' => 'sometimes|date',
            'payment_status' => 'nullable|in:pending,partial,paid',
            'status' => 'nullable|in:ordered,received,cancelled',
            'notes' => 'nullable|string',
        ]);

        if (isset($data['quantity']) || isset($data['unit_cost'])) {
            $quantity = $data['quantity'] ?? $purchase->quantity;
            $unitCost = $data['unit_cost'] ?? $purchase->unit_cost;
            $data['total_cost'] = round($quantity * $unitCost, 2);
        }

        $purchase->update($data);
        return response()->json($purchase->load(['vendor', 'requisition']));
    }

    public function purchasesDestroy(Purchase $purchase)
    {
        $purchase->delete();
        return response()->json(['message' => 'Purchase deleted']);
    }

    // ═══════════════════ STATS ═══════════════════

    public function stats()
    {
        return response()->json([
            'vendors' => [
                'total' => Vendor::count(),
                'active' => Vendor::where('status', 'active')->count(),
            ],
            'requisitions' => [
                'total' => PurchaseRequisition::count(),
                'pending' => PurchaseRequisition::where('status', 'pending')->count(),
                'approved' => PurchaseRequisition::where('status', 'approved')->count(),
                'rejected' => PurchaseRequisition::where('status', 'rejected')->count(),
            ],
            'purchases' => [
                'total' => Purchase::count(),
                'total_spent' => Purchase::sum('total_cost'),
                'pending_payment' => Purchase::where('payment_status', 'pending')->sum('total_cost'),
            ],
        ]);
    }
}