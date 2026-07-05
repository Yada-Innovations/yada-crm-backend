<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('createdBy')->latest()->get();
        return response()->json($services);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'profit_margin' => 'nullable|numeric|min:0|max:100',
            'category' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:255',
            'delivery_time' => 'nullable|string|max:255',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'status' => ['nullable', Rule::in(['active', 'inactive', 'draft'])],
            'is_available' => 'nullable|boolean',
            'requires_consultation' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['id'] = (string) Str::uuid();
        $validated['created_by'] = Auth::id();
        $validated['profit_margin'] = $validated['profit_margin'] ?? 50;

        try {
            $service = Service::create($validated);
            return response()->json($service->load('createdBy'), 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create service',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Service $service)
    {
        return response()->json($service->load('createdBy'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'profit_margin' => 'nullable|numeric|min:0|max:100',
            'category' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:255',
            'delivery_time' => 'nullable|string|max:255',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'status' => ['nullable', Rule::in(['active', 'inactive', 'draft'])],
            'is_available' => 'nullable|boolean',
            'requires_consultation' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            $service->update($validated);
            return response()->json($service->load('createdBy'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update service',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Service $service)
    {
        try {
            $service->delete();
            return response()->json(['message' => 'Service deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete service',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, Service $service)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
        ]);

        $service->update(['status' => $validated['status']]);
        return response()->json($service);
    }

    public function byCategory($category)
    {
        $services = Service::where('category', $category)->with('createdBy')->get();
        return response()->json($services);
    }

    public function available()
    {
        $services = Service::available()->with('createdBy')->get();
        return response()->json($services);
    }
}