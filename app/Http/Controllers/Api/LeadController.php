<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index()
    {
        return response()->json(Lead::latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:leads,email',
            'phone' => 'nullable|string|max:20',
            'company' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['new', 'contacted', 'qualified', 'disqualified', 'converted'])],
            'source' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'company_size' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'priority' => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'sales_stage' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'expected_close_date' => 'nullable|date',
        ]);

        $lead = Lead::create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'],
            'title' => $data['title'] ?? null,
            'status' => $data['status'] ?? 'new',
            'source' => $data['source'] ?? null,
            'notes' => $data['notes'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? 'Kenya',
            'industry' => $data['industry'] ?? null,
            'company_size' => $data['company_size'] ?? null,
            'website' => $data['website'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'sales_stage' => $data['sales_stage'] ?? 'prospecting',
            'assigned_to' => $data['assigned_to'] ?? null,
            'expected_close_date' => $data['expected_close_date'] ?? null,
        ]);

        return response()->json($lead, 201);
    }

    public function show(Lead $lead)
    {
        return response()->json($lead);
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:leads,email,' . $lead->id,
            'phone' => 'nullable|string|max:20',
            'company' => 'sometimes|string|max:255',
            'title' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['new', 'contacted', 'qualified', 'disqualified', 'converted'])],
            'source' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'company_size' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'priority' => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'sales_stage' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'expected_close_date' => 'nullable|date',
        ]);

        // Check if lead is being qualified (status changing to qualified)
        $isQualifying = isset($data['status']) && $data['status'] === 'qualified' && $lead->status !== 'qualified';
        $isConverted = false;
        $client = null;

        // If qualifying, auto-convert to client
        if ($isQualifying) {
            // Check if client already exists with this email
            $existingClient = Client::where('email', $lead->email)->first();
            
            if ($existingClient) {
                // Update existing client with lead data
                $client = $existingClient;
                $client->update([
                    'name' => $data['name'] ?? $lead->name,
                    'phone' => $data['phone'] ?? $lead->phone,
                    'company' => $data['company'] ?? $lead->company,
                    'industry' => $data['industry'] ?? $lead->industry,
                    'address' => $data['address'] ?? $lead->address,
                    'city' => $data['city'] ?? $lead->city,
                    'country' => $data['country'] ?? $lead->country,
                    'notes' => $data['notes'] ?? $lead->notes,
                    'status' => 'active',
                ]);
            } else {
                // Create new client from lead data
                $client = Client::create([
                    'id' => (string) Str::uuid(),
                    'name' => $data['name'] ?? $lead->name,
                    'email' => $lead->email,
                    'phone' => $data['phone'] ?? $lead->phone,
                    'company' => $data['company'] ?? $lead->company,
                    'industry' => $data['industry'] ?? $lead->industry,
                    'status' => 'active',
                    'address' => $data['address'] ?? $lead->address,
                    'city' => $data['city'] ?? $lead->city,
                    'country' => $data['country'] ?? $lead->country,
                    'notes' => $data['notes'] ?? $lead->notes,
                    'account_manager_id' => $data['assigned_to'] ?? $lead->assigned_to,
                ]);
            }
            
            // Update lead with client_id and mark as converted
            $lead->client_id = $client->id;
            $lead->status = 'converted';
            $lead->save();
            
            $isConverted = true;
        } else {
            // Regular update
            $lead->update($data);
        }

        $lead->refresh();
        $lead->load(['client']);

        return response()->json([
            'id' => $lead->id,
            'converted' => $isConverted,
            'client_id' => $isConverted ? $client->id : $lead->client_id,
            'lead' => $lead,
            'client' => $client,
            'message' => $isConverted ? 'Lead converted to client successfully' : 'Lead updated successfully'
        ]);
    }

    public function destroy(Lead $lead)
    {
        try {
            $lead->delete();
            return response()->json(['message' => 'Lead deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete lead',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert a lead to a client
     */
    public function convertToClient(Lead $lead)
    {
        try {
            // Check if already converted
            if ($lead->status === 'converted' && $lead->client_id) {
                return response()->json([
                    'error' => 'Lead already converted to a client',
                    'client_id' => $lead->client_id
                ], 422);
            }

            // Check if client already exists with this email
            $existingClient = Client::where('email', $lead->email)->first();
            
            if ($existingClient) {
                // Update existing client with lead data
                $client = $existingClient;
                $client->update([
                    'name' => $lead->name,
                    'phone' => $lead->phone,
                    'company' => $lead->company,
                    'industry' => $lead->industry,
                    'address' => $lead->address,
                    'city' => $lead->city,
                    'country' => $lead->country,
                    'notes' => $lead->notes,
                    'status' => 'active',
                ]);
            } else {
                // Create new client from lead data
                $client = Client::create([
                    'id' => (string) Str::uuid(),
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'company' => $lead->company,
                    'industry' => $lead->industry,
                    'status' => 'active',
                    'address' => $lead->address,
                    'city' => $lead->city,
                    'country' => $lead->country,
                    'notes' => $lead->notes,
                    'account_manager_id' => $lead->assigned_to,
                ]);
            }

            // Update lead
            $lead->client_id = $client->id;
            $lead->status = 'converted';
            $lead->save();

            return response()->json([
                'message' => 'Lead converted to client successfully',
                'lead' => $lead->fresh(),
                'client' => $client,
                'client_id' => $client->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to convert lead to client',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leads by status
     */
    public function byStatus($status)
    {
        $validStatuses = ['new', 'contacted', 'qualified', 'disqualified', 'converted'];
        
        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'error' => 'Invalid status'
            ], 422);
        }

        $leads = Lead::where('status', $status)->get();
        return response()->json($leads);
    }

    /**
     * Get lead statistics
     */
    public function stats()
    {
        $stats = [
            'total' => Lead::count(),
            'new' => Lead::where('status', 'new')->count(),
            'contacted' => Lead::where('status', 'contacted')->count(),
            'qualified' => Lead::where('status', 'qualified')->count(),
            'disqualified' => Lead::where('status', 'disqualified')->count(),
            'converted' => Lead::where('status', 'converted')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Disqualify a lead
     */
    public function disqualify(Lead $lead)
    {
        $lead->update(['status' => 'disqualified']);
        return response()->json($lead);
    }

    /**
     * Save lead signature
     */
    public function saveSignature(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'signature' => 'required|string',
            'signed_at' => 'required|date',
        ]);

        $lead->update([
            'signature' => $data['signature'],
            'signed_at' => $data['signed_at'],
        ]);

        return response()->json($lead);
    }

    /**
     * Bulk update lead status
     */
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:leads,id',
            'status' => ['required', Rule::in(['new', 'contacted', 'qualified', 'disqualified', 'converted'])],
        ]);

        $count = Lead::whereIn('id', $data['ids'])->update(['status' => $data['status']]);

        return response()->json([
            'message' => "{$count} leads updated successfully",
            'updated_count' => $count
        ]);
    }
}