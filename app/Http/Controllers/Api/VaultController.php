<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VaultEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VaultController extends Controller
{
    public function index(Request $request)
    {
        $query = VaultEntry::with('creator')->latest();

        if ($request->category) {
            $query->category($request->category);
        }
        if ($request->search) {
            $query->search($request->search);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|in:vps,domain,hosting,database,api_key,email,social_media,other',
            'client_name' => 'nullable|string|max:255',
            'website_url' => 'nullable|string|max:255',
            'vps_ip' => 'nullable|string|max:100',
            'vps_port' => 'nullable|string|max:20',
            'ssh_username' => 'nullable|string|max:100',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:1000',
            'api_key' => 'nullable|string|max:2000',
            'notes' => 'nullable|string',
            'extra_fields' => 'nullable|array',
        ]);

        $entry = VaultEntry::create([
            'id' => (string) Str::uuid(),
            ...$data,
            'category' => $data['category'] ?? 'other',
            'created_by' => $request->user()->id,
        ]);

        return response()->json($entry->load('creator'), 201);
    }

    public function show(VaultEntry $vault)
    {
        return response()->json($vault->load('creator'));
    }

    public function update(Request $request, VaultEntry $vault)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'category' => 'nullable|in:vps,domain,hosting,database,api_key,email,social_media,other',
            'client_name' => 'nullable|string|max:255',
            'website_url' => 'nullable|string|max:255',
            'vps_ip' => 'nullable|string|max:100',
            'vps_port' => 'nullable|string|max:20',
            'ssh_username' => 'nullable|string|max:100',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:1000',
            'api_key' => 'nullable|string|max:2000',
            'notes' => 'nullable|string',
            'extra_fields' => 'nullable|array',
        ]);

        $vault->update($data);
        return response()->json($vault->load('creator'));
    }

    public function destroy(VaultEntry $vault)
    {
        $vault->delete();
        return response()->json(['message' => 'Vault entry deleted']);
    }
}