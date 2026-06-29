<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['employee', 'approver', 'creator'])->latest();

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        return response()->json($query->limit(100)->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:annual,sick,maternity,paternity,bereavement,study,unpaid,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Calculate days
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $data['days'] = $start->diffInDays($end) + 1;

        $data['created_by'] = $request->user()->id;
        $data['status'] = 'pending';

        $leave = LeaveRequest::create($data);

        $employee = Employee::find($data['employee_id']);

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            'type' => 'info',
            'title' => 'Leave Request Submitted',
            'message' => "{$employee->full_name} has requested {$data['days']} days of {$data['type']} leave",
            'link' => "/employees/{$employee->id}",
        ]);

        return response()->json($leave->load(['employee', 'creator']), 201);
    }

    public function show(LeaveRequest $leaveRequest)
    {
        return response()->json($leaveRequest->load(['employee', 'approver', 'creator']));
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate([
            'type' => 'sometimes|in:annual,sick,maternity,paternity,bereavement,study,unpaid,other',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Recalculate days if dates changed
        if (isset($data['start_date']) && isset($data['end_date'])) {
            $start = Carbon::parse($data['start_date']);
            $end = Carbon::parse($data['end_date']);
            $data['days'] = $start->diffInDays($end) + 1;
        }

        $leaveRequest->update($data);
        return response()->json($leaveRequest->load(['employee', 'approver', 'creator']));
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        $leaveRequest->delete();
        return response()->json(['message' => 'Leave request deleted']);
    }

    // ── Approve a leave request ──
    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return response()->json(['message' => 'This request has already been processed'], 422);
        }

        $data = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'notes' => $data['notes'] ?? $leaveRequest->notes,
        ]);

        // Update employee status to on_leave if currently active
        $employee = $leaveRequest->employee;
        if ($employee->status === 'active') {
            $employee->update(['status' => 'on_leave']);
        }

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            'type' => 'success',
            'title' => 'Leave Request Approved',
            'message' => "{$employee->full_name}'s leave request has been approved",
            'link' => "/employees/{$employee->id}",
        ]);

        return response()->json($leaveRequest->load(['employee', 'approver', 'creator']));
    }

    // ── Reject a leave request ──
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return response()->json(['message' => 'This request has already been processed'], 422);
        }

        $data = $request->validate([
            'notes' => 'required|string',
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'notes' => $data['notes'],
        ]);

        $employee = $leaveRequest->employee;

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            'type' => 'warning',
            'title' => 'Leave Request Rejected',
            'message' => "{$employee->full_name}'s leave request was rejected: {$data['notes']}",
            'link' => "/employees/{$employee->id}",
        ]);

        return response()->json($leaveRequest->load(['employee', 'approver', 'creator']));
    }

    // ── Get leave balance for an employee ──
    public function balance($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        
        // This is a simplified balance calculation
        // In a real system, you'd have a more complex calculation based on policy
        $year = Carbon::now()->year;
        $totalAnnualLeave = 21; // 21 days per year
        $totalSickLeave = 14; // 14 days per year

        $usedAnnual = LeaveRequest::where('employee_id', $employeeId)
            ->where('type', 'annual')
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days');

        $usedSick = LeaveRequest::where('employee_id', $employeeId)
            ->where('type', 'sick')
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days');

        $pendingRequests = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'employee' => $employee->full_name,
            'year' => $year,
            'annual_leave' => [
                'total' => $totalAnnualLeave,
                'used' => round($usedAnnual, 1),
                'remaining' => round($totalAnnualLeave - $usedAnnual, 1),
            ],
            'sick_leave' => [
                'total' => $totalSickLeave,
                'used' => round($usedSick, 1),
                'remaining' => round($totalSickLeave - $usedSick, 1),
            ],
            'pending_requests' => $pendingRequests->map(function($request) {
                return [
                    'id' => $request->id,
                    'type' => $request->type,
                    'days' => $request->days,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ];
            }),
        ]);
    }
}