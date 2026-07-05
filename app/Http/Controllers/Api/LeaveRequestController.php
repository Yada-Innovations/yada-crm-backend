<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    public function index()
    {
        return response()->json(Lead::with(['assignedUser', 'client'])->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|max:255',
            'company'         => 'required|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'title'           => 'nullable|string|max:255',
            'status'          => ['nullable', Rule::in(['new', 'contacted', 'qualified', 'disqualified', 'converted'])],
            'priority'        => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'sales_stage'     => 'nullable|string|max:255',
            'score'           => 'nullable|integer|min:0|max:100',
            'estimated_value' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'notes'           => 'nullable|string',
            'source'          => 'nullable|string|max:255',
            'industry'        => 'nullable|string|max:255',
            'company_size'    => 'nullable|string|max:255',
            'website'         => 'nullable|url|max:255',
            'address'         => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:255',
            'state'           => 'nullable|string|max:255',
            'country'         => 'nullable|string|max:255',
            'assigned_to'     => 'nullable|exists:users,id',
        ]);

        // Check if status is qualified - auto create client
        $isQualified = isset($data['status']) && $data['status'] === 'qualified';

        $lead = Lead::create([
            'id'              => Str::uuid(),
            'contact_name'    => $data['name'],
            'email'           => $data['email'],
            'company_name'    => $data['company'],
            'phone'           => $data['phone'] ?? null,
            'title'           => $data['title'] ?? null,
            'status'          => $data['status'] ?? 'new',
            'priority'        => $data['priority'] ?? 'medium',
            'sales_stage'     => $data['sales_stage'] ?? 'prospecting',
            'score'           => $data['score'] ?? 0,
            'estimated_value' => $data['estimated_value'] ?? 0,
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'source'          => $data['source'] ?? null,
            'industry'        => $data['industry'] ?? null,
            'company_size'    => $data['company_size'] ?? null,
            'website'         => $data['website'] ?? null,
            'address'         => $data['address'] ?? null,
            'city'            => $data['city'] ?? null,
            'state'           => $data['state'] ?? null,
            'country'         => $data['country'] ?? 'Kenya',
            'assigned_to'     => $data['assigned_to'] ?? null,
        ]);

        // If lead is qualified, create client automatically
        if ($isQualified) {
            $client = $this->createClientFromLead($lead);
            $lead->update(['client_id' => $client->id]);
            
            // Refresh lead with client data
            $lead = $lead->fresh(['assignedUser', 'client']);
        }

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
            'quotes',
            'quotes.creator',
            'demos',
            'tasks',
            'deal',
            'client',
        ]));
    }

    public function update(Request $request, Lead $lead)
    {
        Log::info('Lead Update Started', ['lead_id' => $lead->id, 'data' => $request->all()]);

        $data = $request->validate([
            'name'            => 'sometimes|string|max:255',
            'email'           => 'sometimes|email|max:255',
            'company'         => 'sometimes|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'title'           => 'nullable|string|max:255',
            'status'          => ['nullable', Rule::in(['new', 'contacted', 'qualified', 'disqualified', 'converted'])],
            'priority'        => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'sales_stage'     => 'nullable|string|max:255',
            'score'           => 'nullable|integer|min:0|max:100',
            'estimated_value' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'notes'           => 'nullable|string',
            'source'          => 'nullable|string|max:255',
            'industry'        => 'nullable|string|max:255',
            'company_size'    => 'nullable|string|max:255',
            'website'         => 'nullable|url|max:255',
            'address'         => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:255',
            'state'           => 'nullable|string|max:255',
            'country'         => 'nullable|string|max:255',
            'assigned_to'     => 'nullable|exists:users,id',
        ]);

        // Check if status is changing to qualified
        $isQualified = isset($data['status']) && $data['status'] === 'qualified';
        $previousStatus = $lead->getOriginal('status');

        Log::info('Lead Update Status Check', [
            'isQualified' => $isQualified,
            'previousStatus' => $previousStatus,
            'hasClient' => $lead->client_id ? 'Yes' : 'No'
        ]);

        // Map frontend fields to database columns
        $updateData = [];
        if (isset($data['name'])) $updateData['contact_name'] = $data['name'];
        if (isset($data['company'])) $updateData['company_name'] = $data['company'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        if (isset($data['phone'])) $updateData['phone'] = $data['phone'];
        if (isset($data['title'])) $updateData['title'] = $data['title'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];
        if (isset($data['priority'])) $updateData['priority'] = $data['priority'];
        if (isset($data['sales_stage'])) $updateData['sales_stage'] = $data['sales_stage'];
        if (isset($data['score'])) $updateData['score'] = $data['score'];
        if (isset($data['estimated_value'])) $updateData['estimated_value'] = $data['estimated_value'];
        if (isset($data['expected_close_date'])) $updateData['expected_close_date'] = $data['expected_close_date'];
        if (isset($data['notes'])) $updateData['notes'] = $data['notes'];
        if (isset($data['source'])) $updateData['source'] = $data['source'];
        if (isset($data['industry'])) $updateData['industry'] = $data['industry'];
        if (isset($data['company_size'])) $updateData['company_size'] = $data['company_size'];
        if (isset($data['website'])) $updateData['website'] = $data['website'];
        if (isset($data['address'])) $updateData['address'] = $data['address'];
        if (isset($data['city'])) $updateData['city'] = $data['city'];
        if (isset($data['state'])) $updateData['state'] = $data['state'];
        if (isset($data['country'])) $updateData['country'] = $data['country'];
        if (isset($data['assigned_to'])) $updateData['assigned_to'] = $data['assigned_to'];

        try {
            // Update the lead
            $lead->update($updateData);
            Log::info('Lead Updated Successfully', ['lead_id' => $lead->id]);

            // ── Check if lead is qualified and doesn't have a client yet ──
            if ($isQualified && $previousStatus !== 'qualified' && !$lead->client_id) {
                Log::info('Creating Client from Lead', ['lead_id' => $lead->id]);
                
                // Create client from lead data
                $client = $this->createClientFromLead($lead);

                // Link client to lead
                $lead->update(['client_id' => $client->id]);
                Log::info('Client Created and Linked', ['client_id' => $client->id]);

                // Create notification for conversion
                Notification::create([
                    'id' => Str::uuid(),
                    'user_id' => $request->user()->id,
                    'type' => 'success',
                    'title' => "Lead Converted to Client!",
                    'message' => "{$lead->company_name} has been automatically converted to a client.",
                    'link' => "/clients/{$client->id}",
                ]);

                return response()->json([
                    'message' => 'Lead qualified and automatically converted to client',
                    'lead' => $lead->fresh(['assignedUser', 'client']),
                    'client' => $client,
                    'converted' => true,
                ]);
            }

            // If status changed to qualified but lead already has a client
            if ($isQualified && $lead->client_id) {
                Log::info('Lead already has a client', ['lead_id' => $lead->id]);
                return response()->json([
                    'message' => 'Lead already has a client',
                    'lead' => $lead->load(['assignedUser', 'client']),
                ]);
            }

            return response()->json($lead->load(['assignedUser', 'client', 'quotes']));
        } catch (\Exception $e) {
            Log::error('Lead Update Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to update lead',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a client from lead data
     */
    private function createClientFromLead(Lead $lead)
    {
        try {
            // Check if client already exists with this email
            $existingClient = Client::where('email', $lead->email)->first();
            if ($existingClient) {
                Log::info('Client already exists with this email', ['email' => $lead->email]);
                return $existingClient;
            }

            $client = Client::create([
                'id' => (string) Str::uuid(),
                'name' => $lead->contact_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company_name,
                'industry' => $lead->industry ?? 'Converted from Lead',
                'status' => 'active',
                'address' => $lead->address,
                'city' => $lead->city,
                'state' => $lead->state,
                'country' => $lead->country ?? 'Kenya',
                'account_manager_id' => $lead->assigned_to,
            ]);
            
            Log::info('Client Created', ['client_id' => $client->id]);
            return $client;
        } catch (\Exception $e) {
            Log::error('Failed to create client from lead', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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

    public function saveSignature(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'signature' => 'required|string',
            'status' => 'nullable|string',
        ]);

        $lead->update([
            'signature' => $data['signature'],
            'signed_at' => now(),
        ]);

        return response()->json($lead);
    }

    public function disqualify(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'disqualification_reason' => 'required|string|max:500',
        ]);

        $lead->update([
            'status' => 'disqualified',
            'disqualification_reason' => $data['disqualification_reason'],
            'disqualified_at' => now(),
            'disqualified_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Lead disqualified successfully',
            'lead' => $lead,
        ]);
    }

    public function byStatus($status)
    {
        $leads = Lead::where('status', $status)
            ->with(['assignedUser', 'client'])
            ->get();
        
        return response()->json($leads);
    }

    public function myLeads(Request $request)
    {
        $leads = Lead::where('assigned_to', $request->user()->id)
            ->with(['assignedUser', 'client'])
            ->latest()
            ->get();
        
        return response()->json($leads);
    }

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