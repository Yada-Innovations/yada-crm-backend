<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index()
    {
        return response()->json(Client::latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'company' => 'required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $client = Client::create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'],
            'industry' => $data['industry'] ?? null,
            'status' => $data['status'] ?? 'active',
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? 'Kenya',
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json($client, 201);
    }

    public function show(Client $client)
    {
        return response()->json($client);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:clients,email,' . $client->id,
            'phone' => 'nullable|string|max:20',
            'company' => 'sometimes|string|max:255',
            'industry' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        if (isset($data['phone'])) $updateData['phone'] = $data['phone'];
        if (isset($data['company'])) $updateData['company'] = $data['company'];
        if (isset($data['industry'])) $updateData['industry'] = $data['industry'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];
        if (isset($data['address'])) $updateData['address'] = $data['address'];
        if (isset($data['city'])) $updateData['city'] = $data['city'];
        if (isset($data['country'])) $updateData['country'] = $data['country'];
        if (isset($data['notes'])) $updateData['notes'] = $data['notes'];

        $client->update($updateData);
        return response()->json($client);
    }

    public function destroy(Client $client)
    {
        try {
            $client->delete();
            return response()->json(['message' => 'Client deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete client',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}