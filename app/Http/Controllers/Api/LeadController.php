<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    public function index()
    {
        return response()->json(Lead::with(['assignedUser', 'client'])->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name'    => 'required|string|max:255',
            'contact_name'    => 'required|string|max:255',
            'email'           => 'required|email|max:255',
            'phone'           => 'nullable|string|max:20',
            'estimated_value' => 'nullable|numeric|min:0',
            'assigned_to'     => 'nullable|exists:users,id',
            'notes'           => 'nullable|string',
            'client_id'       => 'nullable|exists:clients,id',
        ]);

        $lead = Lead::create([
            'id'              => Str::uuid(),
            'company_name'    => $data['company_name'],
            'contact_name'    => $data['contact_name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'] ?? null,
            'estimated_value' => $data['estimated_value'] ?? 0,
            'assigned_to'     => $data['assigned_to'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'client_id'       => $data['client_id'] ?? null,
            'stage'           => 'lead',
        ]);

        // Create notification
        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            'type' => 'info',
            'title' => 'New Lead Created',
            'message' => "Lead {$lead->company_name} was created successfully",
            'link' => "/leads",
        ]);

        return response()->json($lead->load(['assignedUser', 'client']), 201);
    }

    public function show(Lead $lead)
    {
        return response()->json($lead->load([
            'assignedUser',
            'quotes',        // Load all quotes for this lead
            'quotes.creator', // Load creator for each quote
            'demos',
            'tasks',
            'deal',
            'client',
        ]));
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'company_name'    => 'sometimes|string|max:255',
            'contact_name'    => 'sometimes|string|max:255',
            'email'           => 'sometimes|email|max:255',
            'phone'           => 'nullable|string|max:20',
            'estimated_value' => 'nullable|numeric|min:0',
            'assigned_to'     => 'nullable|exists:users,id',
            'notes'           => 'nullable|string',
            'client_id'       => 'nullable|exists:clients,id',
            'stage'           => 'sometimes|in:lead,qualified,quote_sent,demo_scheduled,demo_completed,technical_review,proposal_sent,negotiation,closed_won,closed_lost',
        ]);

        // Check if lead is being moved to "closed_won"
        $isClosingWon = isset($data['stage']) && $data['stage'] === 'closed_won' && $lead->stage !== 'closed_won';

        $lead->update($data);

        // If lead is won and doesn't have a client_id, automatically create a client
        if ($isClosingWon && !$lead->client_id) {
            $client = Client::create([
                'id' => Str::uuid(),
                'name' => $lead->company_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company_name,
                'industry' => 'Converted from Lead',
                'status' => 'active',
                'account_manager_id' => $lead->assigned_to ?? $request->user()->id,
            ]);

            $lead->update(['client_id' => $client->id]);

            Notification::create([
                'id' => Str::uuid(),
                'user_id' => $request->user()->id,
                'type' => 'success',
                'title' => "🎉 Lead Converted to Client!",
                'message' => "{$lead->company_name} has been automatically converted to a client.",
                'link' => "/clients/{$client->id}",
            ]);

            return response()->json([
                'message' => 'Lead closed won and automatically converted to client',
                'lead' => $lead->fresh(['assignedUser', 'client']),
                'client' => $client,
            ]);
        }

        // Create notification for stage change
        if (isset($data['stage'])) {
            Notification::create([
                'id' => Str::uuid(),
                'user_id' => $request->user()->id,
                'type' => 'info',
                'title' => 'Lead Stage Updated',
                'message' => "{$lead->company_name} moved to " . str_replace('_', ' ', $data['stage']),
                'link' => "/leads",
            ]);
        }

        return response()->json($lead->load(['assignedUser', 'client', 'quotes']));
    }

    public function destroy(Lead $lead)
    {
        $companyName = $lead->company_name;
        $lead->delete();

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => request()->user()->id,
            'type' => 'warning',
            'title' => 'Lead Deleted',
            'message' => "Lead {$companyName} was deleted",
            'link' => "/leads",
        ]);

        return response()->json(['message' => 'Lead deleted successfully']);
    }

    // ── Save signature ──
    public function saveSignature(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'signature' => 'required|string',
            'stage' => 'sometimes|string|in:lead,qualified,quote_sent,demo_scheduled,demo_completed,technical_review,proposal_sent,negotiation,closed_won,closed_lost',
        ]);

        $lead->update([
            'signature' => $data['signature'],
            'signed_at' => now(),
            'stage' => $data['stage'] ?? $lead->stage,
        ]);

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            'type' => 'success',
            'title' => '📝 Signature Captured',
            'message' => "Signature captured for {$lead->company_name}",
            'link' => "/leads",
        ]);

        return response()->json($lead);
    }

    // ── Disqualify lead ──
    public function disqualify(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'disqualification_reason' => 'required|string|max:500',
        ]);

        $lead->update([
            'stage' => 'closed_lost',
            'disqualification_reason' => $data['disqualification_reason'],
            'disqualified_at' => now(),
            'disqualified_by' => $request->user()->id,
        ]);

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            'type' => 'warning',
            'title' => "Lead Disqualified: {$lead->company_name}",
            'message' => "Lead was disqualified: {$data['disqualification_reason']}",
            'link' => "/leads",
        ]);

        return response()->json([
            'message' => 'Lead disqualified successfully',
            'lead' => $lead,
        ]);
    }

    // ── Get leads by stage ──
    public function byStage($stage)
    {
        $leads = Lead::where('stage', $stage)
            ->with(['assignedUser', 'client'])
            ->get();
        
        return response()->json($leads);
    }

    // ── Get leads assigned to current user ──
    public function myLeads(Request $request)
    {
        $leads = Lead::where('assigned_to', $request->user()->id)
            ->with(['assignedUser', 'client'])
            ->latest()
            ->get();
        
        return response()->json($leads);
    }

    // ── Search leads ──
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        $leads = Lead::where('company_name', 'LIKE', "%{$query}%")
            ->orWhere('contact_name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->with(['assignedUser', 'client'])
            ->limit(20)
            ->get();
        
        return response()->json($leads);
    }
}