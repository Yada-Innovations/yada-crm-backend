<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\Subscription;
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

            // Get open tickets
            $openTickets = Ticket::whereNotIn('status', ['closed', 'resolved'])->count();

            // Get renewals due in the next 30 days
            $renewalsDue = Subscription::where('status', 'active')
                ->where('ends_at', '<=', now()->addDays(30))
                ->count();

            // Calculate MRR
            $mrr = 0;
            $activeSubscriptions = Subscription::with('plan')->where('status', 'active')->get();
            foreach ($activeSubscriptions as $sub) {
                if ($sub->plan) {
                    if (in_array(strtolower($sub->plan->billing_cycle), ['yearly', 'annual', 'annually'])) {
                        $mrr += $sub->plan->price / 12;
                    } else {
                        $mrr += $sub->plan->price;
                    }
                }
            }
            $arr = $mrr * 12;

            // Build response
            $data = [
                'total_leads' => $totalLeads,
                'active_clients' => $activeClients,
                'open_tickets' => $openTickets,
                'renewals_due' => $renewalsDue,
                'mrr' => round($mrr, 2),
                'arr' => round($arr, 2),
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