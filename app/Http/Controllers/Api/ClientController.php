<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index() {
        return response()->json(Client::with('accountManager')->latest()->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name'               => 'required|string',
            'email'              => 'required|email|unique:clients,email',
            'phone'              => 'nullable|string',
            'company'            => 'nullable|string',
            'industry'           => 'nullable|string',
            'account_manager_id' => 'nullable|exists:users,id',
        ]);
        $client = Client::create($data);
        return response()->json($client, 201);
    }

    public function show(Client $client) {
        return response()->json($client->load(['accountManager','subscriptions.plan','tickets','invoices']));
    }

    public function update(Request $request, Client $client) {
        $data = $request->validate([
            'name'    => 'sometimes|string',
            'email'   => 'sometimes|email|unique:clients,email,' . $client->id,
            'phone'   => 'nullable|string',
            'company' => 'nullable|string',
            'status'  => 'sometimes|in:active,inactive,churned',
        ]);
        $client->update($data);
        return response()->json($client);
    }

    public function destroy(Client $client) {
        $client->delete();
        return response()->json(['message' => 'Client deleted']);
    }
}