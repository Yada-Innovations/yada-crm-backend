<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeePaymentDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployeePaymentDetailController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'base_salary' => 'required|numeric|min:0',
            'housing_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'medical_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string',
            'bank_account' => 'nullable|string',
            'bank_branch' => 'nullable|string',
            'payment_frequency' => 'nullable|in:monthly,biweekly,weekly',
        ]);

        // Check if payment details already exist
        $existing = EmployeePaymentDetail::where('user_id', $data['user_id'])->first();
        if ($existing) {
            $existing->update($data);
            return response()->json(['message' => 'Payment details updated successfully', 'payment' => $existing]);
        }

        $data['id'] = Str::uuid();
        $payment = EmployeePaymentDetail::create($data);
        return response()->json(['message' => 'Payment details saved successfully', 'payment' => $payment], 201);
    }

    public function update(Request $request, EmployeePaymentDetail $payment)
    {
        $data = $request->validate([
            'base_salary' => 'sometimes|numeric|min:0',
            'housing_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'medical_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string',
            'bank_account' => 'nullable|string',
            'bank_branch' => 'nullable|string',
            'payment_frequency' => 'nullable|in:monthly,biweekly,weekly',
        ]);

        $payment->update($data);
        return response()->json(['message' => 'Payment details updated successfully', 'payment' => $payment]);
    }

    public function show($userId)
    {
        $payment = EmployeePaymentDetail::where('user_id', $userId)->first();
        if (!$payment) {
            return response()->json(['message' => 'No payment details found'], 404);
        }
        return response()->json($payment);
    }
}