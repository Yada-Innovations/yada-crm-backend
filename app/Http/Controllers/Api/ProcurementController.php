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
            'item_name' => 'nullable|string|max:255',
            'item_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $vendor = Vendor::create([
            'id' => (string) Str::uuid(),
            ...$data,
            'status' => $data['status'] ?? 'active',
            'item_cost' => $data['item_cost'] ?? 0,
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
            'item_name' => 'nullable|string|max:255',
            'item_cost' => 'nullable|numeric|min:0',
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
        $query = PurchaseRequisition::with(['requester', 'approver', 'vendor'])->latest();

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
            'vendor_id' => 'required|exists:vendors,id',
            'department' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:1',
            'justification' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $vendor = Vendor::findOrFail($data['vendor_id']);
        $quantity = $data['quantity'] ?? 1;
        $estimatedCost = round((float) $vendor->item_cost * $quantity, 2);

        $number = 'REQ-' . str_pad((string) (PurchaseRequisition::count() + 1), 5, '0', STR_PAD_LEFT);

        $requisition = PurchaseRequisition::create([
            'id' => (string) Str::uuid(),
            'requisition_number' => $number,
            'requested_by' => $request->user()->id,
            'vendor_id' => $vendor->id,
            'department' => $data['department'] ?? null,
            'item_description' => $vendor->item_name ?? $vendor->name,
            'quantity' => $quantity,
            'estimated_cost' => $estimatedCost,
            'justification' => $data['justification'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($requisition->load(['requester', 'vendor']), 201);
    }

    public function requisitionsShow(PurchaseRequisition $requisition)
    {
        return response()->json($requisition->load(['requester', 'approver', 'vendor', 'purchases']));
    }

    public function requisitionsUpdate(Request $request, PurchaseRequisition $requisition)
    {
        $data = $request->validate([
            'vendor_id' => 'sometimes|exists:vendors,id',
            'department' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:1',
            'justification' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $vendor = isset($data['vendor_id'])
            ? Vendor::findOrFail($data['vendor_id'])
            : $requisition->vendor;

        $quantity = $data['quantity'] ?? $requisition->quantity;

        if ($vendor) {
            $data['item_description'] = $vendor->item_name ?? $vendor->name;
            $data['estimated_cost'] = round((float) $vendor->item_cost * $quantity, 2);
        }

        $requisition->update($data);
        return response()->json($requisition->load(['requester', 'approver', 'vendor']));
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

        return response()->json($requisition->load(['requester', 'approver', 'vendor']));
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

        return response()->json($requisition->load(['requester', 'approver', 'vendor']));
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
            'requisition_id' => 'required|exists:purchase_requisitions,id',
            'quantity' => 'nullable|integer|min:1',
            'purchase_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:purchase_date',
            'reminder_date' => 'nullable|date|before_or_equal:expected_delivery_date',
            'payment_status' => 'nullable|in:pending,partial,paid',
            'status' => 'nullable|in:ordered,received,cancelled',
            'notes' => 'nullable|string',
        ]);

        $requisition = PurchaseRequisition::with('vendor')->findOrFail($data['requisition_id']);

        if ($requisition->status !== 'approved') {
            return response()->json([
                'error' => 'Requisition must be approved before it can be purchased',
            ], 422);
        }

        $vendor = $requisition->vendor;
        $quantity = $data['quantity'] ?? $requisition->quantity;
        $unitCost = $vendor->item_cost ?? 0;
        $totalCost = round($quantity * $unitCost, 2);

        $number = 'PO-' . str_pad((string) (Purchase::count() + 1), 5, '0', STR_PAD_LEFT);

        $purchase = Purchase::create([
            'id' => (string) Str::uuid(),
            'purchase_number' => $number,
            'vendor_id' => $vendor->id,
            'requisition_id' => $requisition->id,
            'item_description' => $requisition->item_description,
            'category' => $vendor->category,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'purchase_date' => $data['purchase_date'],
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'reminder_date' => $data['reminder_date'] ?? null,
            'payment_status' => $data['payment_status'] ?? 'pending',
            'status' => $data['status'] ?? 'ordered',
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $requisition->update(['status' => 'converted']);

        return response()->json($purchase->load(['vendor', 'requisition']), 201);
    }

    public function purchasesShow(Purchase $purchase)
    {
        return response()->json($purchase->load(['vendor', 'requisition', 'creator']));
    }

    public function purchasesUpdate(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'quantity' => 'nullable|integer|min:1',
            'purchase_date' => 'sometimes|date',
            'expected_delivery_date' => 'nullable|date',
            'reminder_date' => 'nullable|date',
            'payment_status' => 'nullable|in:pending,partial,paid',
            'status' => 'nullable|in:ordered,received,cancelled',
            'notes' => 'nullable|string',
        ]);

        if (isset($data['quantity'])) {
            $data['total_cost'] = round($data['quantity'] * $purchase->unit_cost, 2);
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
                'converted' => PurchaseRequisition::where('status', 'converted')->count(),
            ],
            'purchases' => [
                'total' => Purchase::count(),
                'total_spent' => Purchase::sum('total_cost'),
                'pending_payment' => Purchase::where('payment_status', 'pending')->sum('total_cost'),
            ],
        ]);
    }
}