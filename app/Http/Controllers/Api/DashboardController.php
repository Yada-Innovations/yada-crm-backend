<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\Subscription;
use App\Models\Invoice;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // MRR — sum of active subscription plan prices
        $mrr = Subscription::where('status', 'active')
            ->with('plan')
            ->get()
            ->sum(fn($s) => $s->plan?->price ?? 0);

        // Leads by stage
        $leadsByStage = Lead::selectRaw('stage, count(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage');

        // Open tickets
        $openTickets = Ticket::whereIn('status', ['open','in_progress','assigned'])->count();

        // Renewals due in 30 days
        $renewalsDue = Subscription::where('status', 'active')
            ->whereBetween('ends_at', [$now, $now->copy()->addDays(30)])
            ->count();

        // Recent activity (last 5 invoices)
        $recentInvoices = Invoice::with('client')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($inv) => [
                'type'   => 'invoice',
                'label'  => "Invoice {$inv->invoice_number} — {$inv->client?->name}",
                'time'   => $inv->created_at->format('H:i'),
                'amount' => $inv->total,
            ]);

        return response()->json([
            'mrr'           => $mrr,
            'arr'           => $mrr * 12,
            'active_clients'=> Client::where('status', 'active')->count(),
            'open_tickets'  => $openTickets,
            'renewals_due'  => $renewalsDue,
            'leads_by_stage'=> $leadsByStage,
            'total_leads'   => Lead::count(),
            'recent_activity' => $recentInvoices,
        ]);
    }
}