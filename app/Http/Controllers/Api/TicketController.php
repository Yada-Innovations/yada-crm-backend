<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TicketController extends Controller
{
    public function index() {
        return response()->json(Ticket::with(['client','assignedUser','creator'])->latest()->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'subject'     => 'required|string',
            'description' => 'required|string',
            'client_id'   => 'nullable|exists:clients,id',
            'priority'    => 'nullable|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id',
        ]);
        
        $data['created_by'] = $request->user()->id;
        
        // Generate ticket number: sup-YYYYMMDD-XXX
        $data['ticket_number'] = $this->generateTicketNumber();
        
        $ticket = Ticket::create($data);
        return response()->json($ticket, 201);
    }

    public function show(Ticket $ticket) {
        return response()->json($ticket->load(['client','assignedUser','creator','comments.user']));
    }

    public function update(Request $request, Ticket $ticket) {
        $data = $request->validate([
            'status'      => 'sometimes|in:open,assigned,in_progress,resolved,closed',
            'priority'    => 'sometimes|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id',
        ]);
        $ticket->update($data);
        return response()->json($ticket);
    }

    public function destroy(Ticket $ticket) {
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted']);
    }

    /**
     * Generate a unique ticket number: sup-YYYYMMDD-XXX
     */
    private function generateTicketNumber(): string
    {
        $date = Carbon::now()->format('Ymd'); // e.g., 20261207
        $prefix = "sup-{$date}-";
        
        // Find the latest ticket for today with a matching prefix
        $lastTicket = Ticket::where('ticket_number', 'like', $prefix . '%')
                            ->orderBy('ticket_number', 'desc')
                            ->first();
        
        if ($lastTicket) {
            // Extract the numeric part after the last dash
            $lastSeq = (int) substr($lastTicket->ticket_number, -3);
            $newSeq = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newSeq = '001';
        }
        
        return $prefix . $newSeq;
    }
}