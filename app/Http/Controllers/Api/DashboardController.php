<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Get counts
            $totalLeads = Lead::count();
            $activeClients = Client::where('status', 'active')->count();
            
            // Get leads by status
            $leadsByStatus = [
                'lead' => Lead::where('status', 'new')->count(),
                'quote' => Lead::where('status', 'contacted')->count(),
                'demo' => Lead::where('status', 'qualified')->count(),
                'technical_review' => Lead::where('status', 'converted')->count(),
                'closed_won' => Lead::where('status', 'converted')->count(),
            ];

            // Get recent activity
            $recentActivity = [];
            $recentLeads = Lead::latest()->take(5)->get();
            foreach ($recentLeads as $lead) {
                $name = $lead->contact_name ?? $lead->name ?? 'Unknown';
                $recentActivity[] = [
                    'time' => $lead->created_at->format('H:i'),
                    'label' => 'New lead: ' . $name,
                ];
            }

            // Build response
            $data = [
                'total_leads' => $totalLeads,
                'active_clients' => $activeClients,
                'open_tickets' => 0,
                'renewals_due' => 0,
                'mrr' => 0,
                'arr' => 0,
                'leads_by_stage' => $leadsByStatus,
                'recent_activity' => $recentActivity,
            ];

            return response()->json($data, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load dashboard',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}