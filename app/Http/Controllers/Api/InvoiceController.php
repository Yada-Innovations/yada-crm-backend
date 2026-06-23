<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    const MIN_MARGIN = 50;

    public function index() {
        return response()->json(Invoice::with(['client','creator'])->latest()->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'client_id'    => 'required|exists:clients,id',
            'items'        => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'discount_pct' => 'nullable|numeric|min:0|max:50',
            'due_date'     => 'nullable|date',
        ]);

        $subtotal    = collect($data['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);
        $discount    = $data['discount_pct'] ?? 0;
        $total       = $subtotal * (1 - $discount / 100);
        $cost        = $subtotal * 0.5; // cost assumed 50% of subtotal
        $margin      = (($total - $cost) / $total) * 100;

        // Enforce 50% minimum margin
        if ($margin < self::MIN_MARGIN) {
            return response()->json([
                'message' => 'Discount too high — profit margin cannot fall below 50%.',
                'margin'  => round($margin, 2),
            ], 422);
        }

        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . strtoupper(Str::random(6)),
            'client_id'      => $data['client_id'],
            'subtotal'       => $subtotal,
            'discount_pct'   => $discount,
            'total'          => $total,
            'margin_pct'     => round($margin, 2),
            'created_by'     => $request->user()->id,
            'due_date'       => $data['due_date'] ?? null,
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

        // Simulate eTIMS sync
        $invoice->update([
            'etims_status' => 'synced',
            'etims_code'   => 'ETIMS-' . strtoupper(Str::random(8)),
        ]);

        return response()->json($invoice->load(['client','items']), 201);
    }

    public function show(Invoice $invoice) {
        return response()->json($invoice->load(['client','items','payments','creator']));
    }

    public function update(Request $request, Invoice $invoice) {
        $data = $request->validate([
            'status' => 'sometimes|in:draft,sent,paid,overdue',
        ]);
        $invoice->update($data);
        return response()->json($invoice);
    }

    public function destroy(Invoice $invoice) {
        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted']);
    }
}