<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index() {
        return response()->json(Subscription::with(['client','plan'])->latest()->get());
    }

    public function plans() {
        return response()->json(SubscriptionPlan::where('active', true)->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'client_id'  => 'required|exists:clients,id',
            'plan_id'    => 'required|exists:subscription_plans,id',
            'starts_at'  => 'required|date',
            'ends_at'    => 'required|date|after:starts_at',
        ]);
        $subscription = Subscription::create($data);
        return response()->json($subscription->load(['client','plan']), 201);
    }

    public function show(Subscription $subscription) {
        return response()->json($subscription->load(['client','plan','licenses']));
    }

    public function update(Request $request, Subscription $subscription) {
        $data = $request->validate([
            'seats_used' => 'sometimes|integer|min:0',
            'status'     => 'sometimes|in:active,expired,cancelled',
        ]);
        $subscription->update($data);
        return response()->json($subscription);
    }

    public function destroy(Subscription $subscription) {
        $subscription->delete();
        return response()->json(['message' => 'Subscription cancelled']);
    }
}