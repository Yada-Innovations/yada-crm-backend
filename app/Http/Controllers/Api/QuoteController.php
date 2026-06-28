<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Lead;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    const MIN_MARGIN = 50;

    public function index()
    {
        return response()->json(Quote::with(['lead', 'creator'])->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id'      => 'required|exists:leads,id',
            'base_amount'  => 'required|numeric|min:0',
            'discount_pct' => 'nullable|numeric|min:0|max:50',
            'valid_until'  => 'nullable|date',
        ]);

        $lead = Lead::find($data['lead_id']);
        if (!$lead) {
            return response()->json(['message' => 'Lead not found'], 404);
        }

        // Check if lead already has a quote
        if ($lead->quotes()->exists()) {
            return response()->json([
                'message' => 'This lead already has a quote. You can edit the existing quote.',
            ], 422);
        }

        $base        = $data['base_amount'];
        $discount    = $data['discount_pct'] ?? 0;
        $final       = $base * (1 - $discount / 100);
        $cost        = $base * 0.5; // cost assumed 50% of base
        $margin      = $final > 0 ? (($final - $cost) / $final) * 100 : 0;

        // Enforce minimum 50% margin
        if ($margin < self::MIN_MARGIN) {
            return response()->json([
                'message' => 'Discount too high — margin would fall below 50%. Maximum discount is 50%.',
                'margin' => round($margin, 2),
            ], 422);
        }

        // Create the quote
        $quote = Quote::create([
            'id'           => Str::uuid(),
            'lead_id'      => $data['lead_id'],
            'base_amount'  => $base,
            'discount_pct' => $discount,
            'final_amount' => $final,
            'margin_pct'   => round($margin, 2),
            'created_by'   => $request->user()->id,
            'valid_until'  => $data['valid_until'] ?? null,
            'status'       => 'draft',
        ]);

        // Update lead stage to 'quote_sent'
        $lead->update(['stage' => 'quote_sent']);

        // Create notification
        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            'type' => 'success',
            'title' => '📄 Quote Created',
            'message' => "Quote for {$lead->company_name} created successfully (KES " . number_format($final) . ")",
            'link' => "/quotes",
        ]);

        return response()->json($quote->load(['lead', 'creator']), 201);
    }

    public function show(Quote $quote)
    {
        return response()->json($quote->load(['lead', 'creator']));
    }

    public function update(Request $request, Quote $quote)
    {
        $data = $request->validate([
            'status'       => 'sometimes|in:draft,sent,viewed,negotiating,accepted,rejected,expired',
            'discount_pct' => 'sometimes|numeric|min:0|max:50',
            'base_amount'  => 'sometimes|numeric|min:0',
            'valid_until'  => 'sometimes|date',
        ]);

        // If updating discount, recalculate margin
        if (isset($data['discount_pct']) || isset($data['base_amount'])) {
            $base = $data['base_amount'] ?? $quote->base_amount;
            $discount = $data['discount_pct'] ?? $quote->discount_pct;
            $final = $base * (1 - $discount / 100);
            $cost = $base * 0.5;
            $margin = $final > 0 ? (($final - $cost) / $final) * 100 : 0;

            if ($margin < self::MIN_MARGIN) {
                return response()->json([
                    'message' => 'Discount too high — margin would fall below 50%.',
                    'margin' => round($margin, 2),
                ], 422);
            }

            $data['final_amount'] = $final;
            $data['margin_pct'] = round($margin, 2);
        }

        // If changing status, handle lead stage updates
        if (isset($data['status'])) {
            $lead = $quote->lead;
            
            switch ($data['status']) {
                case 'sent':
                    $lead->update(['stage' => 'quote_sent']);
                    break;
                case 'viewed':
                    $lead->update(['stage' => 'quote_sent']);
                    break;
                case 'negotiating':
                    $lead->update(['stage' => 'negotiation']);
                    break;
                case 'accepted':
                    $lead->update(['stage' => 'negotiation']);
                    Notification::create([
                        'id' => Str::uuid(),
                        'user_id' => $request->user()->id,
                        'type' => 'success',
                        'title' => '✅ Quote Accepted!',
                        'message' => "Quote for {$lead->company_name} has been accepted",
                        'link' => "/quotes",
                    ]);
                    break;
                case 'rejected':
                    Notification::create([
                        'id' => Str::uuid(),
                        'user_id' => $request->user()->id,
                        'type' => 'warning',
                        'title' => '❌ Quote Rejected',
                        'message' => "Quote for {$lead->company_name} was rejected",
                        'link' => "/quotes",
                    ]);
                    break;
            }
        }

        $quote->update($data);
        return response()->json($quote->load(['lead', 'creator']));
    }

    public function destroy(Quote $quote)
    {
        $lead = $quote->lead;
        $lead->update(['stage' => 'lead']);
        
        $quote->delete();
        
        return response()->json(['message' => 'Quote deleted and lead returned to lead stage']);
    }
}