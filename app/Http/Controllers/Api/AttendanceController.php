<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AttendanceController extends Controller
{
    /**
     * Resolve a users.id into the matching Employee record.
     * Throws a 422 JSON-friendly exception if the user has no employee record.
     */
    protected function resolveEmployeeForUser($userId): Employee
    {
        $employee = Employee::where('user_id', $userId)->first();

        if (!$employee) {
            abort(response()->json([
                'error' => 'No employee record found for this user.',
            ], 422));
        }

        return $employee;
    }

    public function index(Request $request)
    {
        $query = AttendanceRecord::with(['employee.user'])->latest();

        // Frontend may send either user_id or employee_id (backward compatibility)
        $userId = $request->user_id ?? $request->employee_id;
        if ($userId) {
            $employee = Employee::where('user_id', $userId)->first();
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                // Maybe they actually passed an employees.id directly
                $query->where('employee_id', $userId);
            }
        }

        if ($request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        return response()->json($query->limit(100)->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'nullable|in:present,absent,late,half_day,holiday,sick',
            'notes' => 'nullable|string',
        ]);

        $userId = $data['user_id'];
        $employee = $this->resolveEmployeeForUser($userId);

        // Check if attendance already exists for this employee on this date
        $existing = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $data['date'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Attendance already recorded for this date',
                'attendance' => $existing,
            ], 422);
        }

        // Calculate hours worked if both check_in and check_out are provided
        $hoursWorked = 0;
        if (!empty($data['check_in']) && !empty($data['check_out'])) {
            $checkIn = Carbon::parse($data['date'] . ' ' . $data['check_in']);
            $checkOut = Carbon::parse($data['date'] . ' ' . $data['check_out']);
            $hoursWorked = round($checkIn->diffInHours($checkOut), 2);
        }

        $attendance = AttendanceRecord::create([
            'id' => (string) Str::uuid(),
            'employee_id' => $employee->id,
            'date' => $data['date'],
            'check_in' => !empty($data['check_in']) ? Carbon::parse($data['date'] . ' ' . $data['check_in']) : null,
            'check_out' => !empty($data['check_out']) ? Carbon::parse($data['date'] . ' ' . $data['check_out']) : null,
            'status' => $data['status'] ?? 'present',
            'notes' => $data['notes'] ?? null,
            'hours_worked' => $hoursWorked,
        ]);

        $user = User::find($userId);

        // Create notification
        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'type' => 'info',
            'title' => 'Attendance Recorded',
            'message' => "Attendance recorded for {$user->display_name} on " . Carbon::parse($data['date'])->format('M d, Y'),
            'link' => "/employees/{$user->id}",
        ]);

        return response()->json($attendance->load('employee.user'), 201);
    }

    public function show(AttendanceRecord $attendance)
    {
        return response()->json($attendance->load('employee.user'));
    }

    public function update(Request $request, AttendanceRecord $attendance)
    {
        $data = $request->validate([
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'nullable|in:present,absent,late,half_day,holiday,sick',
            'notes' => 'nullable|string',
        ]);

        // Recalculate hours worked
        if (!empty($data['check_in']) && !empty($data['check_out'])) {
            $date = $attendance->date;
            $checkIn = Carbon::parse($date . ' ' . $data['check_in']);
            $checkOut = Carbon::parse($date . ' ' . $data['check_out']);
            $data['hours_worked'] = round($checkIn->diffInHours($checkOut), 2);
        }

        // Set datetime for check_in and check_out
        if (!empty($data['check_in'])) {
            $data['check_in'] = Carbon::parse($attendance->date . ' ' . $data['check_in']);
        }
        if (!empty($data['check_out'])) {
            $data['check_out'] = Carbon::parse($attendance->date . ' ' . $data['check_out']);
        }

        $attendance->update($data);
        return response()->json($attendance->load('employee.user'));
    }

    public function destroy(AttendanceRecord $attendance)
    {
        $attendance->delete();
        return response()->json(['message' => 'Attendance record deleted']);
    }

    // ── Quick Check-in for employee ──
    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $userId = $data['user_id'];
        $employee = $this->resolveEmployeeForUser($userId);
        $today = Carbon::today()->toDateString();

        // Check if already checked in today
        $existing = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing && $existing->check_in) {
            return response()->json([
                'error' => 'Already checked in today',
                'attendance' => $existing
            ], 422);
        }

        $now = Carbon::now();
        $status = $now->format('H:i') > '08:30' ? 'late' : 'present';

        $attendance = AttendanceRecord::create([
            'id' => (string) Str::uuid(),
            'employee_id' => $employee->id,
            'date' => $today,
            'check_in' => $now,
            'status' => $status,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Check-in successful',
            'attendance' => $attendance->load('employee.user'),
            'status' => $status
        ], 201);
    }

    // ── Quick Check-out for employee ──
    public function checkOut(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $userId = $data['user_id'];
        $employee = $this->resolveEmployeeForUser($userId);
        $today = Carbon::today()->toDateString();

        $attendance = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'error' => 'No check-in found for today'
            ], 422);
        }

        if ($attendance->check_out) {
            return response()->json([
                'error' => 'Already checked out today',
                'attendance' => $attendance
            ], 422);
        }

        $now = Carbon::now();
        $attendance->check_out = $now;
        $attendance->hours_worked = round($now->diffInMinutes($attendance->check_in) / 60, 2);
        $attendance->save();

        return response()->json([
            'message' => 'Check-out successful',
            'attendance' => $attendance->load('employee.user'),
            'hours_worked' => $attendance->hours_worked
        ]);
    }

    // ── Today's attendance ──
    public function today()
    {
        $today = Carbon::today()->toDateString();
        $attendance = AttendanceRecord::with('employee.user')
            ->whereDate('date', $today)
            ->get();

        return response()->json([
            'date' => $today,
            'count' => $attendance->count(),
            'attendance' => $attendance
        ]);
    }

    // ── Bulk check-in for all employees ──
    public function bulkCheckIn(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'check_in' => 'required|date_format:H:i',
        ]);

        $employees = Employee::where('status', 'active')->get();

        $count = 0;

        foreach ($employees as $employee) {
            $existing = AttendanceRecord::where('employee_id', $employee->id)
                ->whereDate('date', $data['date'])
                ->first();

            if (!$existing) {
                AttendanceRecord::create([
                    'id' => (string) Str::uuid(),
                    'employee_id' => $employee->id,
                    'date' => $data['date'],
                    'check_in' => Carbon::parse($data['date'] . ' ' . $data['check_in']),
                    'status' => 'present',
                ]);
                $count++;
            }
        }

        return response()->json([
            'message' => "{$count} employees checked in successfully",
            'count' => $count,
        ]);
    }

    // ── Get attendance summary for a user ──
    public function summary($userId)
    {
        $user = User::findOrFail($userId);
        $employee = $this->resolveEmployeeForUser($userId);

        $year = Carbon::now()->year;
        $month = Carbon::now()->month;

        $attendance = AttendanceRecord::where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $summary = [
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'late' => $attendance->where('status', 'late')->count(),
            'half_day' => $attendance->where('status', 'half_day')->count(),
            'holiday' => $attendance->where('status', 'holiday')->count(),
            'sick' => $attendance->where('status', 'sick')->count(),
            'total_hours' => $attendance->sum('hours_worked'),
            'total_days' => $attendance->count(),
        ];

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->display_name,
                'employee_id' => $user->employee_id,
            ],
            'year' => $year,
            'month' => $month,
            'summary' => $summary,
        ]);
    }

    // ── Get user's attendance by date range ──
    public function byDateRange(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $employee = $this->resolveEmployeeForUser($data['user_id']);

        $attendance = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('date', [$data['start_date'], $data['end_date']])
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($attendance);
    }
}