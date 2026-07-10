<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $leaveRequests = LeaveRequest::with(['employee', 'employee.user'])->latest()->get();
        return response()->json($leaveRequests);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => ['required', Rule::in(['annual', 'sick', 'maternity', 'paternity', 'bereavement', 'study', 'unpaid', 'other'])],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        // Calculate days
        $start = new \DateTime($data['start_date']);
        $end = new \DateTime($data['end_date']);
        $days = $start->diff($end)->days + 1;

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $data['employee_id'],
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($leaveRequest->load('employee'), 201);
    }

    public function show(LeaveRequest $leaveRequest)
    {
        return response()->json($leaveRequest->load(['employee', 'employee.user']));
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate([
            'type' => ['sometimes', Rule::in(['annual', 'sick', 'maternity', 'paternity', 'bereavement', 'study', 'unpaid', 'other'])],
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'cancelled'])],
        ]);

        // Recalculate days if dates changed
        if (isset($data['start_date']) && isset($data['end_date'])) {
            $start = new \DateTime($data['start_date']);
            $end = new \DateTime($data['end_date']);
            $data['days'] = $start->diff($end)->days + 1;
        }

        $leaveRequest->update($data);
        return response()->json($leaveRequest->load('employee'));
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        $leaveRequest->update(['status' => 'approved']);
        return response()->json($leaveRequest->load('employee'));
    }

    public function reject(LeaveRequest $leaveRequest)
    {
        $leaveRequest->update(['status' => 'rejected']);
        return response()->json($leaveRequest->load('employee'));
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        $leaveRequest->delete();
        return response()->json(['message' => 'Leave request deleted successfully']);
    }

    public function balance($employeeId)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $totalDays = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->sum('days');

        // Default annual leave: 21 days
        $annualLeaveEntitlement = 21;
        $remaining = $annualLeaveEntitlement - $totalDays;

        return response()->json([
            'employee_id' => $employeeId,
            'employee_name' => $employee->first_name . ' ' . $employee->last_name,
            'total_taken' => $totalDays,
            'remaining' => $remaining,
            'entitlement' => $annualLeaveEntitlement,
        ]);
    }
}