<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceRecord::with(['employee'])->latest();

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
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
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'nullable|in:present,absent,late,half_day,holiday,sick',
            'notes' => 'nullable|string',
        ]);

        // Check if attendance already exists for this employee on this date
        $existing = AttendanceRecord::where('employee_id', $data['employee_id'])
            ->where('date', $data['date'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Attendance already recorded for this date',
                'attendance' => $existing,
            ], 422);
        }

        // Calculate hours worked if both check_in and check_out are provided
        if (!empty($data['check_in']) && !empty($data['check_out'])) {
            $checkIn = Carbon::parse($data['date'] . ' ' . $data['check_in']);
            $checkOut = Carbon::parse($data['date'] . ' ' . $data['check_out']);
            $data['hours_worked'] = round($checkIn->diffInHours($checkOut), 2);
        } else {
            $data['hours_worked'] = 0;
        }

        $data['status'] = $data['status'] ?? 'present';
        $attendance = AttendanceRecord::create($data);

        $employee = Employee::find($data['employee_id']);

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $request->user()->id,
            'type' => 'info',
            'title' => 'Attendance Recorded',
            'message' => "Attendance recorded for {$employee->full_name} on " . Carbon::parse($data['date'])->format('M d, Y'),
            'link' => "/employees/{$employee->id}",
        ]);

        return response()->json($attendance->load('employee'), 201);
    }

    public function show(AttendanceRecord $attendance)
    {
        return response()->json($attendance->load('employee'));
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

        $attendance->update($data);
        return response()->json($attendance->load('employee'));
    }

    public function destroy(AttendanceRecord $attendance)
    {
        $attendance->delete();
        return response()->json(['message' => 'Attendance record deleted']);
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
                ->where('date', $data['date'])
                ->first();

            if (!$existing) {
                AttendanceRecord::create([
                    'employee_id' => $employee->id,
                    'date' => $data['date'],
                    'check_in' => $data['check_in'],
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

    // ── Get attendance summary for an employee ──
    public function summary($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;

        $attendance = AttendanceRecord::where('employee_id', $employeeId)
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
            'employee' => $employee->full_name,
            'year' => $year,
            'month' => $month,
            'summary' => $summary,
        ]);
    }
}