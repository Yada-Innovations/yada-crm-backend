<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePaymentDetail;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index()
    {
        return response()->json(Employee::with(['user', 'paymentDetail'])->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'employment_type' => 'nullable|in:full_time,part_time,contract,internship',
            'hire_date' => 'nullable|date',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $data['employee_number'] = 'EMP-' . strtoupper(Str::random(6));
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'active';

        $employee = Employee::create($data);

        // Create default payment details
        EmployeePaymentDetail::create([
            'employee_id' => $employee->id,
            'base_salary' => 0,
            'payment_frequency' => 'monthly',
        ]);

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            'type' => 'success',
            'title' => 'Employee Added',
            'message' => "{$employee->full_name} has been added as an employee",
            'link' => "/employees/{$employee->id}",
        ]);

        return response()->json($employee->load(['user', 'paymentDetail']), 201);
    }

    public function show(Employee $employee)
    {
        return response()->json($employee->load([
            'user', 'paymentDetail', 'attendance' => function($q) {
                $q->latest()->limit(30);
            }, 'leaveRequests' => function($q) {
                $q->where('status', 'pending');
            }, 'agreements'
        ]));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'employment_type' => 'nullable|in:full_time,part_time,contract,internship',
            'status' => 'nullable|in:active,on_leave,terminated,suspended',
            'hire_date' => 'nullable|date',
            'termination_date' => 'nullable|date',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
        ]);

        $employee->update($data);
        return response()->json($employee->load(['user', 'paymentDetail']));
    }

    public function destroy(Employee $employee)
    {
        $name = $employee->full_name;
        $employee->delete();
        return response()->json(['message' => "Employee {$name} deleted"]);
    }

    public function stats()
    {
        return response()->json([
            'total' => Employee::count(),
            'active' => Employee::where('status', 'active')->count(),
            'on_leave' => Employee::where('status', 'on_leave')->count(),
            'terminated' => Employee::where('status', 'terminated')->count(),
            'departments' => Employee::select('department')->distinct()->get()->pluck('department'),
        ]);
    }
}