<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

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

    // ── Get payroll listing ──
    public function index(Request $request)
    {
        try {
            $query = Payroll::with(['employee', 'creator'])->latest();

            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            return response()->json($query->limit(100)->get());
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch payroll',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ── Calculate payroll for an employee ──
    public function calculate($employeeId)
    {
        try {
            $employee = Employee::with('paymentDetail')->findOrFail($employeeId);
            $payment = $employee->paymentDetail;
            
            if (!$payment) {
                return response()->json(['error' => 'Employee has no payment details'], 422);
            }

            $basic = $payment->base_salary ?? 0;
            $allowances = ($payment->housing_allowance ?? 0) + ($payment->transport_allowance ?? 0) + 
                          ($payment->medical_allowance ?? 0) + ($payment->other_allowances ?? 0);
            $bonus = $payment->bonus ?? 0;
            
            $grossPay = $basic + $allowances + $bonus;
            
            // Calculate deductions
            $tax = $this->calculateTax($grossPay);
            $nssfEmployee = $this->calculateNSSF($grossPay);
            $nssfEmployer = $nssfEmployee; // Employer matches employee contribution
            $ahl = $this->calculateAHL($grossPay);
            
            $netPay = $grossPay - $tax - $nssfEmployee - $ahl;
            $employerCost = $grossPay + $nssfEmployer;

            return response()->json([
                'employee' => $employee->first_name . ' ' . $employee->last_name,
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
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to calculate payroll',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ── Generate a new payroll ──
    public function generate(Request $request)
    {
        try {
            $data = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'period_start' => 'required|date',
                'period_end' => 'required|date|after:period_start',
                'period' => 'nullable|string',
            ]);

            $employee = Employee::with('paymentDetail')->findOrFail($data['employee_id']);
            $payment = $employee->paymentDetail;
            
            if (!$payment) {
                return response()->json(['error' => 'Employee has no payment details'], 422);
            }

            // Check if payroll already exists for this period
            $period = $data['period'] ?? Carbon::parse($data['period_start'])->format('F Y');
            $existing = Payroll::where('employee_id', $employee->id)
                ->where('period', $period)
                ->first();

            if ($existing) {
                return response()->json(['error' => 'Payroll already exists for this period'], 422);
            }

            // Calculate payroll
            $basic = $payment->base_salary ?? 0;
            $allowances = ($payment->housing_allowance ?? 0) + ($payment->transport_allowance ?? 0) + 
                          ($payment->medical_allowance ?? 0) + ($payment->other_allowances ?? 0);
            $bonus = $payment->bonus ?? 0;
            $grossPay = $basic + $allowances + $bonus;
            
            $tax = $this->calculateTax($grossPay);
            $nssfEmployee = $this->calculateNSSF($grossPay);
            $nssfEmployer = $nssfEmployee;
            $ahl = $this->calculateAHL($grossPay);
            $netPay = $grossPay - $tax - $nssfEmployee - $ahl;
            $employerCost = $grossPay + $nssfEmployer;

            // Create payroll record
            $payroll = Payroll::create([
                'id' => (string) Str::uuid(),
                'employee_id' => $employee->id,
                'period' => $period,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'basic_salary' => $basic,
                'housing_allowance' => $payment->housing_allowance ?? 0,
                'transport_allowance' => $payment->transport_allowance ?? 0,
                'medical_allowance' => $payment->medical_allowance ?? 0,
                'other_allowances' => $payment->other_allowances ?? 0,
                'bonus' => $bonus,
                'gross_pay' => $grossPay,
                'tax_paye' => $tax,
                'nssf_employee' => $nssfEmployee,
                'nssf_employer' => $nssfEmployer,
                'ahl' => $ahl,
                'other_deductions' => 0,
                'net_pay' => $netPay,
                'employer_cost' => $employerCost,
                'created_by' => $request->user()?->id,
                'status' => 'draft',
            ]);

            return response()->json($payroll->load(['employee', 'creator']), 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate payroll',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ── Get single payroll ──
    public function show(Payroll $payroll)
    {
        try {
            return response()->json($payroll->load(['employee', 'creator', 'employee.paymentDetail']));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch payroll',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ── Update payroll ──
    public function update(Request $request, Payroll $payroll)
    {
        try {
            $data = $request->validate([
                'gross_pay' => 'nullable|numeric|min:0',
                'tax_paye' => 'nullable|numeric|min:0',
                'nssf_employee' => 'nullable|numeric|min:0',
                'ahl' => 'nullable|numeric|min:0',
                'net_pay' => 'nullable|numeric|min:0',
                'status' => ['nullable', Rule::in(['draft', 'approved', 'paid'])],
            ]);

            // If status is being changed to paid, set paid_at
            if (isset($data['status']) && $data['status'] === 'paid' && $payroll->status !== 'paid') {
                $data['paid_at'] = now();
            }

            // If gross_pay or deductions are updated, recalculate net_pay
            if (isset($data['gross_pay']) || isset($data['tax_paye']) || 
                isset($data['nssf_employee']) || isset($data['ahl'])) {
                
                $grossPay = $data['gross_pay'] ?? $payroll->gross_pay;
                $taxPaye = $data['tax_paye'] ?? $payroll->tax_paye;
                $nssf = $data['nssf_employee'] ?? $payroll->nssf_employee;
                $ahl = $data['ahl'] ?? $payroll->ahl;
                
                $data['net_pay'] = $grossPay - $taxPaye - $nssf - $ahl;
                $data['employer_cost'] = $grossPay + $nssf;
            }

            $payroll->update($data);
            
            return response()->json($payroll->load(['employee', 'creator']));
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update payroll',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ── Approve payroll ──
    public function approve(Payroll $payroll)
    {
        try {
            if ($payroll->status === 'paid') {
                return response()->json(['error' => 'Cannot approve a paid payroll'], 422);
            }
            
            $payroll->update(['status' => 'approved']);
            return response()->json($payroll->load(['employee', 'creator']));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to approve payroll',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ── Mark payroll as paid ──
    public function markPaid(Payroll $payroll)
    {
        try {
            if ($payroll->status === 'paid') {
                return response()->json(['error' => 'Payroll is already marked as paid'], 422);
            }
            
            $payroll->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            
            return response()->json($payroll->load(['employee', 'creator']));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to mark payroll as paid',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ── Delete payroll ──
    public function destroy(Payroll $payroll)
    {
        try {
            // Check if payroll is already paid
            if ($payroll->status === 'paid') {
                return response()->json([
                    'error' => 'Cannot delete a payroll that has been marked as paid'
                ], 422);
            }
            
            $payroll->delete();
            return response()->json(['message' => 'Payroll deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete payroll',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ── Get payroll summary ──
    public function summary(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ── Get payroll statistics ──
    public function stats()
    {
        try {
            $stats = [
                'total' => Payroll::count(),
                'draft' => Payroll::where('status', 'draft')->count(),
                'approved' => Payroll::where('status', 'approved')->count(),
                'paid' => Payroll::where('status', 'paid')->count(),
                'total_amount' => Payroll::sum('net_pay'),
                'total_gross' => Payroll::sum('gross_pay'),
                'total_tax' => Payroll::sum('tax_paye'),
            ];
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ── Bulk generate payroll ──
    public function bulkGenerate(Request $request)
    {
        try {
            $data = $request->validate([
                'employee_ids' => 'required|array|min:1',
                'employee_ids.*' => 'exists:employees,id',
                'period_start' => 'required|date',
                'period_end' => 'required|date|after:period_start',
            ]);

            $results = [];
            $errors = [];

            foreach ($data['employee_ids'] as $employeeId) {
                try {
                    $employee = Employee::with('paymentDetail')->findOrFail($employeeId);
                    $payment = $employee->paymentDetail;
                    
                    if (!$payment) {
                        $errors[] = "Employee {$employee->first_name} {$employee->last_name} has no payment details";
                        continue;
                    }

                    $period = Carbon::parse($data['period_start'])->format('F Y');
                    $existing = Payroll::where('employee_id', $employee->id)
                        ->where('period', $period)
                        ->first();

                    if ($existing) {
                        $errors[] = "Payroll already exists for {$employee->first_name} {$employee->last_name}";
                        continue;
                    }

                    // Calculate payroll
                    $basic = $payment->base_salary ?? 0;
                    $allowances = ($payment->housing_allowance ?? 0) + ($payment->transport_allowance ?? 0) + 
                                  ($payment->medical_allowance ?? 0) + ($payment->other_allowances ?? 0);
                    $bonus = $payment->bonus ?? 0;
                    $grossPay = $basic + $allowances + $bonus;
                    
                    $tax = $this->calculateTax($grossPay);
                    $nssfEmployee = $this->calculateNSSF($grossPay);
                    $nssfEmployer = $nssfEmployee;
                    $ahl = $this->calculateAHL($grossPay);
                    $netPay = $grossPay - $tax - $nssfEmployee - $ahl;
                    $employerCost = $grossPay + $nssfEmployer;

                    // Create payroll record
                    $payroll = Payroll::create([
                        'id' => (string) Str::uuid(),
                        'employee_id' => $employee->id,
                        'period' => $period,
                        'period_start' => $data['period_start'],
                        'period_end' => $data['period_end'],
                        'basic_salary' => $basic,
                        'housing_allowance' => $payment->housing_allowance ?? 0,
                        'transport_allowance' => $payment->transport_allowance ?? 0,
                        'medical_allowance' => $payment->medical_allowance ?? 0,
                        'other_allowances' => $payment->other_allowances ?? 0,
                        'bonus' => $bonus,
                        'gross_pay' => $grossPay,
                        'tax_paye' => $tax,
                        'nssf_employee' => $nssfEmployee,
                        'nssf_employer' => $nssfEmployer,
                        'ahl' => $ahl,
                        'other_deductions' => 0,
                        'net_pay' => $netPay,
                        'employer_cost' => $employerCost,
                        'created_by' => $request->user()?->id,
                        'status' => 'draft',
                    ]);

                    $results[] = $payroll->load('employee');
                    
                } catch (\Exception $e) {
                    $errors[] = "Error for employee ID {$employeeId}: " . $e->getMessage();
                }
            }

            return response()->json([
                'message' => 'Bulk generation completed',
                'generated' => count($results),
                'errors' => $errors,
                'payrolls' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to bulk generate payroll',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}