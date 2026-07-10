<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index()
    {
        // Get all users with employee_id (or all users)
        $users = User::with(['paymentDetail', 'roles'])
            ->latest()
            ->get()
            ->map(function($user) {
                // Format the user to match what the frontend expects
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name ?? explode(' ', $user->name)[0] ?? 'User',
                    'last_name' => $user->last_name ?? (count(explode(' ', $user->name)) > 1 ? explode(' ', $user->name)[1] : ''),
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'position' => $user->position,
                    'department' => $user->department,
                    'employee_id' => $user->employee_id,
                    'employment_type' => $user->employment_type,
                    'hire_date' => $user->hire_date,
                    'status' => $user->status,
                    'address' => $user->address,
                    'city' => $user->city,
                    'state' => $user->state,
                    'country' => $user->country,
                    'emergency_contact_name' => $user->emergency_contact_name,
                    'emergency_contact_phone' => $user->emergency_contact_phone,
                    'emergency_contact_relation' => $user->emergency_contact_relation,
                    'role' => $user->roles->first()?->name ?? 'sales_agent',
                    'roles' => $user->roles,
                    'payment_detail' => $user->paymentDetail,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });
        
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:8',
            'password_confirmation' => 'nullable|same:password',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'employment_type' => 'nullable|in:full_time,part_time,contract,internship',
            'hire_date' => 'nullable|date',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'emergency_contact_relation' => 'nullable|string',
            'role' => 'nullable|in:admin,sales_agent,support_agent',
            'status' => 'nullable|in:active,on_leave,terminated,suspended',
        ]);

        // Generate employee ID
        $count = User::count() + 1;
        $employeeId = 'EMP-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'] ? bcrypt($data['password']) : bcrypt('password'),
            'phone' => $data['phone'] ?? null,
            'position' => $data['position'] ?? null,
            'department' => $data['department'] ?? null,
            'employee_id' => $employeeId,
            'employment_type' => $data['employment_type'] ?? 'full_time',
            'hire_date' => $data['hire_date'] ?? now(),
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? 'Kenya',
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'emergency_contact_relation' => $data['emergency_contact_relation'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        // Assign role
        $roleName = $data['role'] ?? 'sales_agent';
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $user->assignRole($role);
        }

        return response()->json($user->load(['paymentDetail', 'roles']), 201);
    }

    public function show($id)
    {
        $user = User::with(['paymentDetail', 'roles', 'attendance', 'leaveRequests', 'agreements'])
            ->findOrFail($id);
        
        // Format the user to match what the frontend expects
        $formatted = [
            'id' => $user->id,
            'first_name' => $user->first_name ?? explode(' ', $user->name)[0],
            'last_name' => $user->last_name ?? (count(explode(' ', $user->name)) > 1 ? explode(' ', $user->name)[1] : ''),
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'position' => $user->position,
            'department' => $user->department,
            'employee_id' => $user->employee_id,
            'employment_type' => $user->employment_type,
            'hire_date' => $user->hire_date,
            'status' => $user->status,
            'address' => $user->address,
            'city' => $user->city,
            'state' => $user->state,
            'country' => $user->country,
            'emergency_contact_name' => $user->emergency_contact_name,
            'emergency_contact_phone' => $user->emergency_contact_phone,
            'emergency_contact_relation' => $user->emergency_contact_relation,
            'role' => $user->roles->first()?->name ?? 'sales_agent',
            'roles' => $user->roles,
            'payment_detail' => $user->paymentDetail,
            'attendance' => $user->attendance,
            'leave_requests' => $user->leaveRequests,
            'agreements' => $user->agreements,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
        
        return response()->json($formatted);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $data = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'password_confirmation' => 'nullable|same:password',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'employment_type' => 'nullable|in:full_time,part_time,contract,internship',
            'status' => 'nullable|in:active,on_leave,terminated,suspended',
            'hire_date' => 'nullable|date',
            'termination_date' => 'nullable|date',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'emergency_contact_relation' => 'nullable|string',
            'role' => 'nullable|in:admin,sales_agent,support_agent',
        ]);

        // Update name if first_name or last_name changed
        if (isset($data['first_name']) || isset($data['last_name'])) {
            $firstName = $data['first_name'] ?? $user->first_name;
            $lastName = $data['last_name'] ?? $user->last_name;
            $data['name'] = $firstName . ' ' . $lastName;
        }

        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
            unset($data['password_confirmation']);
        }

        $user->update($data);

        // Update role if provided
        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return response()->json($user->load(['paymentDetail', 'roles']));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $name = $user->full_name ?? $user->name;
        $user->delete();
        return response()->json(['message' => "Employee {$name} deleted"]);
    }

    public function stats()
    {
        $users = User::all();
        
        return response()->json([
            'total' => $users->count(),
            'active' => $users->where('status', 'active')->count(),
            'on_leave' => $users->where('status', 'on_leave')->count(),
            'terminated' => $users->where('status', 'terminated')->count(),
            'departments' => $users->pluck('department')->filter()->unique()->values(),
        ]);
    }
}