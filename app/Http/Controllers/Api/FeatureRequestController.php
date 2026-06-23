<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureRequest;
use Illuminate\Http\Request;

class FeatureRequestController extends Controller
{
    public function index() {
        return response()->json(FeatureRequest::with('submitter')->orderByDesc('votes')->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'title'       => 'required|string',
            'description' => 'required|string',
        ]);
        $data['submitted_by'] = $request->user()->id;
        $fr = FeatureRequest::create($data);
        return response()->json($fr, 201);
    }

    public function show(FeatureRequest $featureRequest) {
        return response()->json($featureRequest->load('submitter'));
    }

    public function update(Request $request, FeatureRequest $featureRequest) {
        $data = $request->validate([
            'status' => 'sometimes|in:backlog,under_review,planned,completed',
        ]);
        $featureRequest->update($data);
        return response()->json($featureRequest);
    }

    public function vote(FeatureRequest $featureRequest) {
        $featureRequest->increment('votes');
        return response()->json(['votes' => $featureRequest->fresh()->votes]);
    }

    public function destroy(FeatureRequest $featureRequest) {
        $featureRequest->delete();
        return response()->json(['message' => 'Feature request deleted']);
    }
}