<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAgreement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployeeAgreementController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeAgreement::with(['employee', 'signer'])->latest();
        
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }
        
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:employment_contract,nda,non_compete,salary_agreement,other',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'signed_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'status' => 'nullable|in:draft,signed,expired,terminated',
            'notes' => 'nullable|string',
        ]);

        $data['id'] = Str::uuid();
        $agreement = EmployeeAgreement::create($data);
        
        return response()->json($agreement->load('employee'), 201);
    }

    public function show(EmployeeAgreement $employeeAgreement)
    {
        return response()->json($employeeAgreement->load(['employee', 'signer']));
    }

    public function update(Request $request, EmployeeAgreement $employeeAgreement)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:draft,signed,expired,terminated',
            'signed_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        
        $employeeAgreement->update($data);
        return response()->json($employeeAgreement);
    }

    public function destroy(EmployeeAgreement $employeeAgreement)
    {
        $employeeAgreement->delete();
        return response()->json(['message' => 'Agreement deleted']);
    }
}