<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    const MIN_MARGIN = 50;

    public function index() {
        return response()->json(Quote::with(['lead','creator'])->latest()->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'lead_id'      => 'required|exists:leads,id',
            'base_amount'  => 'required|numeric|min:0',
            'discount_pct' => 'nullable|numeric|min:0|max:50',
            'valid_until'  => 'nullable|date',
        ]);

        $base        = $data['base_amount'];
        $discount    = $data['discount_pct'] ?? 0;
        $final       = $base * (1 - $discount / 100);
        $margin      = 100 - ($base * 0.5 / $final * 100); // cost assumed 50% of base

        // Enforce minimum 50% margin
        if ($margin < self::MIN_MARGIN) {
            return response()->json([
                'message' => 'Discount too high — margin would fall below 50%. Maximum discount is 50%.',
            ], 422);
        }

        $quote = Quote::create([
            'lead_id'      => $data['lead_id'],
            'base_amount'  => $base,
            'discount_pct' => $discount,
            'final_amount' => $final,
            'margin_pct'   => round($margin, 2),
            'created_by'   => $request->user()->id,
            'valid_until'  => $data['valid_until'] ?? null,
        ]);

        return response()->json($quote, 201);
    }

    public function show(Quote $quote) {
        return response()->json($quote->load(['lead','creator']));
    }

    public function update(Request $request, Quote $quote) {
        $data = $request->validate([
            'status' => 'sometimes|in:draft,sent,accepted,rejected',
        ]);
        $quote->update($data);
        return response()->json($quote);
    }

    public function destroy(Quote $quote) {
        $quote->delete();
        return response()->json(['message' => 'Quote deleted']);
    }
}