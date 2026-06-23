<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index() {
        return response()->json(Lead::with('assignedUser')->latest()->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'company_name'    => 'required|string',
            'contact_name'    => 'required|string',
            'email'           => 'required|email',
            'phone'           => 'nullable|string',
            'estimated_value' => 'nullable|numeric',
            'assigned_to'     => 'nullable|exists:users,id',
            'notes'           => 'nullable|string',
        ]);
        $lead = Lead::create($data);
        return response()->json($lead, 201);
    }

    public function show(Lead $lead) {
        return response()->json($lead->load(['assignedUser','quotes','demos','tasks','deal']));
    }

    public function update(Request $request, Lead $lead) {
        $data = $request->validate([
            'stage'           => 'sometimes|in:lead,quote,demo,technical_review,closed_won,closed_lost',
            'estimated_value' => 'sometimes|numeric',
            'assigned_to'     => 'nullable|exists:users,id',
            'notes'           => 'nullable|string',
        ]);
        $lead->update($data);
        return response()->json($lead);
    }

    public function destroy(Lead $lead) {
        $lead->delete();
        return response()->json(['message' => 'Lead deleted']);
    }
}