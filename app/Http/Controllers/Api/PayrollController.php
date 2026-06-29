<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class PayrollController extends Controller
{
    // ── Tax calculation for Kenya (2026) ──
    private function calculateTax($grossPay)
    {
        // Simplified Kenyan PAYE rates (2026)
        // Personal relief: KES 2,400 per month
        $personalRelief = 2400;
        
        if ($grossPay <= 24000) {
            $tax = 0;
        } elseif ($grossPay <= 32333) {
            $tax = ($grossPay - 24000) * 0.10;
        } elseif ($grossPay <= 40666) {
            $tax = 833.3 + ($grossPay - 32333) * 0.15;
        } elseif ($grossPay <= 49000) {
            $tax = 2083.3 + ($grossPay - 40666) * 0.20;
        } elseif ($grossPay <= 57333) {
            $tax = 3750 + ($grossPay - 49000) * 0.25;
        } else {
            $tax = 5833.3 + ($grossPay - 57333) * 0.30;
        }
        
        return max(0, $tax - $personalRelief);
    }

    // ── NSSF calculation (Kenya 2026) ──
    private function calculateNSSF($grossPay)
    {
        // Tier I: Up to 18,000 KES at 6%
        // Tier II: 18,001 - 36,000 KES at 6%
        $tier1 = min($grossPay, 18000) * 0.06;
        $tier2 = max(0, min($grossPay - 18000, 18000)) * 0.06;
        return round($tier1 + $tier2, 2);
    }

    // ── AHL calculation (Kenya 2026) ──
    private function calculateAHL($grossPay)
    {
        // Affordable Housing Levy: 1.5% of gross pay
        return round($grossPay * 0.015, 2);
    }

    public function calculate($employeeId)
    {
        $employee = Employee::with('paymentDetail')->findOrFail($employeeId);
        $payment = $employee->paymentDetail;
        
        if (!$payment) {
            return response()->json(['error' => 'Employee has no payment details'], 422);
        }

        $basic = $payment->base_salary;
        $allowances = $payment->housing_allowance + $payment->transport_allowance + 
                      $payment->medical_allowance + $payment->other_allowances;
        $bonus = $payment->bonus;
        
        $grossPay = $basic + $allowances + $bonus;
        
        // Calculate deductions
        $tax = $this->calculateTax($grossPay);
        $nssfEmployee = $this->calculateNSSF($grossPay);
        $nssfEmployer = $nssfEmployee; // Employer matches employee contribution
        $ahl = $this->calculateAHL($grossPay);
        
        $netPay = $grossPay - $tax - $nssfEmployee - $ahl;
        $employerCost = $grossPay + $nssfEmployer;

        return response()->json([
            'employee' => $employee->full_name,
            'employee_id' => $employee->id,
            'basic_salary' => $basic,
            'allowances' => $allowances,
            'bonus' => $bonus,
            'gross_pay' => round($grossPay, 2),
            'tax_paye' => round($tax, 2),
            'nssf_employee' => round($nssfEmployee, 2),
            'nssf_employer' => round($nssfEmployer, 2),
            'ahl' => round($ahl, 2),
            'net_pay' => round($netPay, 2),
            'employer_cost' => round($employerCost, 2),
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ]);

        $employee = Employee::with('paymentDetail')->findOrFail($data['employee_id']);
        $payment = $employee->paymentDetail;
        
        if (!$payment) {
            return response()->json(['error' => 'Employee has no payment details'], 422);
        }

        // Calculate payroll
        $basic = $payment->base_salary;
        $allowances = $payment->housing_allowance + $payment->transport_allowance + 
                      $payment->medical_allowance + $payment->other_allowances;
        $bonus = $payment->bonus;
        $grossPay = $basic + $allowances + $bonus;
        
        $tax = $this->calculateTax($grossPay);
        $nssfEmployee = $this->calculateNSSF($grossPay);
        $nssfEmployer = $nssfEmployee;
        $ahl = $this->calculateAHL($grossPay);
        $netPay = $grossPay - $tax - $nssfEmployee - $ahl;
        $employerCost = $grossPay + $nssfEmployer;

        // Create payroll record
        $payroll = Payroll::create([
            'id' => Str::uuid(),
            'employee_id' => $employee->id,
            'period' => Carbon::parse($data['period_start'])->format('F Y'),
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'basic_salary' => $basic,
            'housing_allowance' => $payment->housing_allowance,
            'transport_allowance' => $payment->transport_allowance,
            'medical_allowance' => $payment->medical_allowance,
            'other_allowances' => $payment->other_allowances,
            'bonus' => $bonus,
            'gross_pay' => $grossPay,
            'tax_paye' => $tax,
            'nssf_employee' => $nssfEmployee,
            'nssf_employer' => $nssfEmployer,
            'ahl' => $ahl,
            'other_deductions' => 0,
            'net_pay' => $netPay,
            'employer_cost' => $employerCost,
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        return response()->json($payroll->load('employee'), 201);
    }

    public function index(Request $request)
    {
        $query = Payroll::with(['employee', 'creator'])->latest();

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json($query->limit(100)->get());
    }

    public function show(Payroll $payroll)
    {
        return response()->json($payroll->load(['employee', 'creator']));
    }

    public function approve(Payroll $payroll)
    {
        $payroll->update(['status' => 'approved']);
        return response()->json($payroll);
    }

    public function markPaid(Payroll $payroll)
    {
        $payroll->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        return response()->json($payroll);
    }

    public function summary(Request $request)
    {
        $period = $request->get('period', Carbon::now()->format('F Y'));
        
        $payrolls = Payroll::where('period', $period)->get();
        
        return response()->json([
            'period' => $period,
            'total_employees' => $payrolls->count(),
            'total_gross_pay' => $payrolls->sum('gross_pay'),
            'total_tax' => $payrolls->sum('tax_paye'),
            'total_nssf' => $payrolls->sum('nssf_employee'),
            'total_ahl' => $payrolls->sum('ahl'),
            'total_net_pay' => $payrolls->sum('net_pay'),
            'total_employer_cost' => $payrolls->sum('employer_cost'),
            'payrolls' => $payrolls->load('employee'),
        ]);
    }
}