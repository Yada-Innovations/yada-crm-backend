<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeePaymentDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user (employee)
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            // Personal Information
            'name' => 'nullable|string|max:255',
            'first_name' => 'required_without:name|string|max:255',
            'last_name' => 'required_without:name|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'prohibited', // Role is never accepted from public registration
            
            // Contact Information
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id',
            'employment_type' => 'nullable|in:full_time,part_time,contract,internship',
            'hire_date' => 'nullable|date',
            
            // Address
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            
            // Emergency Contact
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relation' => 'nullable|string|max:255',
            
            // Account Settings
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended', 'terminated'])],
            'timezone' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:10',
        ]);

        // Build full name if provided as first_name/last_name
        $name = $validated['name'] ?? trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? ''));

        // Generate employee ID if not provided
        $employeeId = $validated['employee_id'] ?? 'EMP-' . str_pad(User::count() + 1, 4, '0', STR_PAD_LEFT);

        $user = User::create([
            // Personal Information
            'name' => $name,
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            
            // Contact Information
            'phone' => $validated['phone'] ?? null,
            'department' => $validated['department'] ?? null,
            'position' => $validated['position'] ?? null,
            'employee_id' => $employeeId,
            'employment_type' => $validated['employment_type'] ?? 'full_time',
            'hire_date' => $validated['hire_date'] ?? now(),
            
            // Address
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? 'Kenya',
            
            // Emergency Contact
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'emergency_contact_relation' => $validated['emergency_contact_relation'] ?? null,
            
            // Account Settings
            'status' => $validated['status'] ?? 'active',
            'timezone' => $validated['timezone'] ?? 'Africa/Nairobi',
            'language' => $validated['language'] ?? 'en',
        ]);

        // Assign default role — never trust a client-supplied role name
        $role = Role::where('name', 'sales_agent')->first();
        if ($role) {
            $user->assignRole($role);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'user' => $this->formatUserResponse($user),
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active
        if ($user->status === 'inactive' || $user->status === 'suspended') {
            throw ValidationException::withMessages([
                'email' => ['Your account is ' . $user->status . '. Please contact support.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Set the token in an httpOnly cookie so JavaScript cannot access it.
        // The SetBearerTokenFromCookie middleware reads this cookie and injects
        // it as a bearer token for Sanctum on every subsequent API request.
        $cookie = cookie(
            name: 'auth_token',
            value: $token,
            minutes: 60 * 24,          // 24 hours
            path: '/',
            domain: null,              // current domain only
            secure: app()->isProduction(), // HTTPS only in production
            httpOnly: true,            // not accessible via JavaScript
            raw: false,
            sameSite: 'Lax',           // CSRF protection while allowing normal navigation
        );

        return response()->json([
            'message' => 'Login successful',
            // Still return token in body for non-browser clients (mobile / Postman)
            'token' => $token,
            'user' => $this->formatUserResponse($user),
        ])->withCookie($cookie);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        // Expire the httpOnly auth cookie
        $expiredCookie = cookie(
            name: 'auth_token',
            value: '',
            minutes: -1,
            path: '/',
            domain: null,
            secure: app()->isProduction(),
            httpOnly: true,
            raw: false,
            sameSite: 'Lax',
        );

        return response()->json([
            'message' => 'Logged out successfully',
        ])->withCookie($expiredCookie);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json($this->formatUserResponse($user));
    }

    /**
     * Format user response with all employee data
     */
    private function formatUserResponse($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'department' => $user->department,
            'position' => $user->position,
            'employee_id' => $user->employee_id,
            'employment_type' => $user->employment_type ?? 'full_time',
            'hire_date' => $user->hire_date,
            'termination_date' => $user->termination_date,
            'address' => $user->address,
            'city' => $user->city,
            'state' => $user->state,
            'country' => $user->country,
            'emergency_contact_name' => $user->emergency_contact_name,
            'emergency_contact_phone' => $user->emergency_contact_phone,
            'emergency_contact_relation' => $user->emergency_contact_relation,
            'profile_picture' => $user->profile_picture,
            'status' => $user->status,
            'timezone' => $user->timezone,
            'language' => $user->language,
            'role' => $user->getRoleNames()->first(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relation' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:10',
        ]);

        // Update name if first_name and last_name are provided
        if (isset($validated['first_name']) && isset($validated['last_name'])) {
            $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Upload / replace profile avatar
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = $request->user();

        // Delete old avatar if one exists
        if ($user->profile_picture) {
            $oldPath = str_replace('/storage/', '', parse_url($user->profile_picture, PHP_URL_PATH) ?? '');
            if ($oldPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $url = \Illuminate\Support\Facades\Storage::url($path);

        $user->update(['profile_picture' => $url]);

        return response()->json([
            'message' => 'Avatar updated successfully',
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Get user permissions
     */
    public function permissions(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'roles' => $user->getRoleNames(),
        ]);
    }

    /**
     * Check if email exists
     */
    public function checkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $exists = User::where('email', $validated['email'])->exists();

        return response()->json([
            'exists' => $exists,
        ]);
    }

    /**
     * Get user by employee ID
     */
    public function getByEmployeeId($employeeId)
    {
        $user = User::where('employee_id', $employeeId)->first();

        if (!$user) {
            return response()->json([
                'error' => 'User not found',
            ], 404);
        }

        return response()->json($this->formatUserResponse($user));
    }

    /**
     * Get all employees (users with employee data)
     */
    public function getEmployees(Request $request)
    {
        $users = User::with('roles')
            ->whereNotNull('employee_id')
            ->orWhereNotNull('first_name')
            ->orWhereNotNull('last_name')
            ->get();

        return response()->json($users->map(function ($user) {
            return $this->formatUserResponse($user);
        }));
    }

    /**
     * Get user statistics
     */
    public function stats()
    {
        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'on_leave' => User::where('status', 'on_leave')->count(),
            'terminated' => User::where('status', 'terminated')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'by_role' => [],
        ];

        $roles = Role::withCount('users')->get();
        foreach ($roles as $role) {
            $stats['by_role'][$role->name] = $role->users_count;
        }

        return response()->json($stats);
    }
}