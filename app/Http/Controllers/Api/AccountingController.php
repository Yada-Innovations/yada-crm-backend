<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AccountingController extends Controller
{
    /**
     * Combined ledger: manual entries + payments (revenue) + purchases & payroll (cost)
     */
    public function ledger(Request $request)
    {
        $from = $request->get('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->get('to', Carbon::now()->endOfMonth()->toDateString());
        $type = $request->get('type'); // optional: revenue|cost

        $rows = collect();

        // Manual entries
        $manual = AccountingEntry::with('creator')
            ->whereBetween('entry_date', [$from, $to])
            ->when($type, fn($q) => $q->where('type', $type))
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'type' => $e->type,
                'category' => $e->category,
                'description' => $e->description,
                'amount' => (float) $e->amount,
                'date' => $e->entry_date->toDateString(),
                'source' => 'manual',
                'source_id' => $e->id,
                'notes' => $e->notes,
                'editable' => true,
            ]);
        $rows = $rows->concat($manual);

        // Revenue from actual payments received
        if (!$type || $type === 'revenue') {
            $payments = Payment::with(['invoice', 'invoice.client'])
                ->whereBetween('payment_date', [$from, $to])
                ->get()
                ->map(fn($p) => [
                    'id' => 'payment-' . $p->id,
                    'type' => 'revenue',
                    'category' => 'Payment',
                    'description' => 'Payment for ' . ($p->invoice->invoice_number ?? 'invoice')
                        . ' — ' . ($p->invoice->client->name ?? 'Unknown client')
                        . ' (' . $p->getPaymentMethodLabelAttribute() . ')',
                    'amount' => (float) $p->amount,
                    'date' => $p->payment_date?->toDateString(),
                    'source' => 'payment',
                    'source_id' => $p->id,
                    'notes' => $p->notes,
                    'editable' => false,
                ]);
            $rows = $rows->concat($payments);
        }

        // Cost from purchases (procurement)
        if (!$type || $type === 'cost') {
            $purchases = Purchase::with('vendor')
                ->whereBetween('purchase_date', [$from, $to])
                ->get()
                ->map(fn($p) => [
                    'id' => 'purchase-' . $p->id,
                    'type' => 'cost',
                    'category' => $p->category ?? 'Procurement',
                    'description' => $p->item_description . ' — ' . ($p->vendor->name ?? 'Unknown vendor'),
                    'amount' => (float) $p->total_cost,
                    'date' => $p->purchase_date?->toDateString(),
                    'source' => 'purchase',
                    'source_id' => $p->id,
                    'notes' => null,
                    'editable' => false,
                ]);
            $rows = $rows->concat($purchases);

            // Cost from paid payroll runs
            $payrolls = Payroll::with('employee')
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->get()
                ->map(fn($p) => [
                    'id' => 'payroll-' . $p->id,
                    'type' => 'cost',
                    'category' => 'Payroll',
                    'description' => 'Payroll (' . $p->period . ') — '
                        . trim(($p->employee->first_name ?? '') . ' ' . ($p->employee->last_name ?? '')) ?: ($p->employee->name ?? 'Employee'),
                    'amount' => (float) $p->employer_cost,
                    'date' => $p->paid_at?->toDateString(),
                    'source' => 'payroll',
                    'source_id' => $p->id,
                    'notes' => null,
                    'editable' => false,
                ]);
            $rows = $rows->concat($payrolls);
        }

        $sorted = $rows->sortByDesc('date')->values();

        return response()->json($sorted);
    }

    /**
     * Profit & Loss summary for a period
     */
    public function summary(Request $request)
    {
        $from = $request->get('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->get('to', Carbon::now()->endOfMonth()->toDateString());

        $manualRevenue = AccountingEntry::revenue()->between($from, $to)->sum('amount');
        $manualCost = AccountingEntry::cost()->between($from, $to)->sum('amount');

        $paymentRevenue = Payment::whereBetween('payment_date', [$from, $to])->sum('amount');

        $purchaseCost = Purchase::whereBetween('purchase_date', [$from, $to])->sum('total_cost');

        $payrollCost = Payroll::where('status', 'paid')
            ->whereBetween('paid_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->sum('employer_cost');

        $totalRevenue = (float) $manualRevenue + (float) $paymentRevenue;
        $totalCost = (float) $manualCost + (float) $purchaseCost + (float) $payrollCost;
        $profit = $totalRevenue - $totalCost;
        $margin = $totalRevenue > 0 ? round(($profit / $totalRevenue) * 100, 2) : 0;

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'revenue' => [
                'manual' => round((float) $manualRevenue, 2),
                'payments' => round((float) $paymentRevenue, 2),
                'total' => round($totalRevenue, 2),
            ],
            'cost' => [
                'manual' => round((float) $manualCost, 2),
                'purchases' => round((float) $purchaseCost, 2),
                'payroll' => round((float) $payrollCost, 2),
                'total' => round($totalCost, 2),
            ],
            'profit' => round($profit, 2),
            'margin_pct' => $margin,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:revenue,cost',
            'category' => 'nullable|string|max:100',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'entry_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $entry = AccountingEntry::create([
            'id' => (string) Str::uuid(),
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($entry->load('creator'), 201);
    }

    public function update(Request $request, AccountingEntry $entry)
    {
        $data = $request->validate([
            'type' => 'sometimes|in:revenue,cost',
            'category' => 'nullable|string|max:100',
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'entry_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        $entry->update($data);
        return response()->json($entry->load('creator'));
    }

    public function destroy(AccountingEntry $entry)
    {
        $entry->delete();
        return response()->json(['message' => 'Entry deleted']);
    }
}