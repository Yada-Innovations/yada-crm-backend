<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Jobs\SyncInvoiceToEtimsJob;

class InvoiceController extends Controller
{
    const MIN_MARGIN = 50;

    public function index() {
        return response()->json(Invoice::with(['client', 'workOrder', 'creator'])->latest()->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'client_id'     => 'required|exists:clients,id',
            'work_order_id' => 'nullable|exists:work_done,id',
            'items'         => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'discount_pct'  => 'nullable|numeric|min:0|max:50',
            'subtotal'      => 'nullable|numeric|min:0',
            'tax'           => 'nullable|numeric|min:0',
            'tax_rate'      => 'nullable|numeric|min:0|max:100',
            'total'         => 'nullable|numeric|min:0',
            'status'        => 'nullable|in:draft,sent,paid,overdue,cancelled',
            'due_date'      => 'nullable|date',
            'issue_date'    => 'nullable|date',
            'notes'         => 'nullable|string',
        ]);

        $itemsSubtotal = collect($data['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);

        // Use frontend-provided financials when present, otherwise compute
        $subtotal = $data['subtotal'] ?? $itemsSubtotal;
        $taxRate  = $data['tax_rate'] ?? 16;
        $discount = $data['discount_pct'] ?? 0;

        if (isset($data['total'])) {
            $total = $data['total'];
            $tax = $data['tax'] ?? ($subtotal * $taxRate / 100);
        } else {
            $total = $subtotal * (1 - $discount / 100);
            $tax = $data['tax'] ?? ($subtotal * $taxRate / 100);
        }

        // ── Always compute margin (needed for the DB column) ──
        $cost = $itemsSubtotal * 0.5; // cost assumed 50% of subtotal
        $margin = $total > 0 ? (($total - $cost) / $total) * 100 : 0;

        // ── Only enforce the minimum-margin rule for manually created invoices ──
        // (i.e. not tied to a work order, where the total is already agreed/approved).
        if (empty($data['work_order_id']) && $margin < self::MIN_MARGIN) {
            return response()->json([
                'message' => 'Discount too high — profit margin cannot fall below 50%.',
                'margin'  => round($margin, 2),
            ], 422);
        }

        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . strtoupper(Str::random(6)),
            'client_id'      => $data['client_id'],
            'work_order_id'  => $data['work_order_id'] ?? null,
            'subtotal'       => $subtotal,
            'tax'            => $tax,
            'tax_rate'       => $taxRate,
            'discount_pct'   => $discount,
            'total'          => $total,
            'margin_pct'     => round($margin, 2),   // <-- ADDED
            'status'         => $data['status'] ?? 'draft',
            'issue_date'     => $data['issue_date'] ?? now()->toDateString(),
            'due_date'       => $data['due_date'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'created_by'     => $request->user()->id,
            'etims_status'   => 'pending',
        ]);

        foreach ($data['items'] as $item) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'total'       => $item['quantity'] * $item['unit_price'],
            ]);
        }

        // Dispatch the eTIMS sync job
        SyncInvoiceToEtimsJob::dispatch($invoice);

        return response()->json($invoice->load(['client', 'workOrder', 'items']), 201);
    }

    public function show(Invoice $invoice) {
        return response()->json($invoice->load(['client', 'workOrder', 'items', 'payments', 'creator']));
    }

    public function update(Request $request, Invoice $invoice) {
        $data = $request->validate([
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
        ]);
        $invoice->update($data);
        return response()->json($invoice);
    }

    public function destroy(Invoice $invoice) {
        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted']);
    }

    /**
     * Manually retry eTIMS sync for an invoice that failed.
     */
    public function retryEtims(Invoice $invoice)
    {
        if ($invoice->etims_status === 'synced') {
            return response()->json(['message' => 'Invoice is already synced'], 422);
        }

        $invoice->update(['etims_status' => 'pending']);
        SyncInvoiceToEtimsJob::dispatch($invoice);

        return response()->json([
            'message' => 'eTIMS sync re-queued',
            'invoice' => $invoice,
        ]);
    }
}