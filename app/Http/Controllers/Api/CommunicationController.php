<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication;
use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Communication::with(['client', 'lead', 'creator'])
            ->orderBy('created_at', 'desc');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        return response()->json($query->limit(50)->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'lead_id' => 'nullable|exists:leads,id',
            'type' => 'required|in:email,sms,note,call,meeting',
            'direction' => 'required|in:incoming,outgoing',
            'subject' => 'nullable|string',
            'content' => 'required|string',
            'to' => 'nullable|string',
            'from' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $data['created_by'] = $request->user()->id;
        $communication = Communication::create($data);

        // Create notification for the client's account manager
        if ($communication->client_id && $communication->client) {
            $this->createNotification($communication);
        }

        return response()->json($communication->load(['client', 'creator']), 201);
    }

    public function show(Communication $communication)
    {
        return response()->json($communication->load(['client', 'lead', 'creator']));
    }

    public function update(Request $request, Communication $communication)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:sent,delivered,read,failed',
            'read_at' => 'sometimes|date',
        ]);
        $communication->update($data);
        return response()->json($communication);
    }

    public function destroy(Communication $communication)
    {
        $communication->delete();
        return response()->json(['message' => 'Communication deleted']);
    }

    public function timeline($clientId)
    {
        $communications = Communication::where('client_id', $clientId)
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Also get related tickets
        $tickets = Ticket::where('client_id', $clientId)
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($t) {
                return [
                    'id' => $t->id,
                    'type' => 'ticket',
                    'subject' => $t->subject,
                    'content' => $t->description,
                    'created_at' => $t->created_at,
                    'creator' => $t->creator,
                ];
            });

        $invoices = Invoice::where('client_id', $clientId)
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($i) {
                return [
                    'id' => $i->id,
                    'type' => 'invoice',
                    'subject' => $i->invoice_number,
                    'content' => "Total: KES {$i->total}",
                    'created_at' => $i->created_at,
                    'creator' => $i->creator,
                ];
            });

        $timeline = $communications->concat($tickets)->concat($invoices)
            ->sortByDesc('created_at')
            ->values();

        return response()->json($timeline);
    }

    private function createNotification($communication)
    {
        $accountManager = $communication->client->accountManager;
        if ($accountManager) {
            Notification::create([
                'id' => Str::uuid(),
                'user_id' => $accountManager->id,
                'type' => 'info',
                'title' => "New communication from {$communication->client->name}",
                'message' => $communication->subject ?? $communication->content,
                'link' => "/clients/{$communication->client_id}",
                'data' => ['communication_id' => $communication->id],
            ]);
        }
    }

    public function templates()
    {
        return response()->json(EmailTemplate::where('active', true)->get());
    }
}