<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

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
}