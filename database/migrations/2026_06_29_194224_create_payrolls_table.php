<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->string('period'); // e.g., "June 2026"
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('housing_allowance', 15, 2)->default(0);
            $table->decimal('transport_allowance', 15, 2)->default(0);
            $table->decimal('medical_allowance', 15, 2)->default(0);
            $table->decimal('other_allowances', 15, 2)->default(0);
            $table->decimal('bonus', 15, 2)->default(0);
            $table->decimal('gross_pay', 15, 2);
            $table->decimal('tax_paye', 15, 2);
            $table->decimal('nssf_employee', 15, 2);
            $table->decimal('nssf_employer', 15, 2);
            $table->decimal('ahl', 15, 2);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('net_pay', 15, 2);
            $table->decimal('employer_cost', 15, 2);
            $table->string('status')->default('draft'); // draft, approved, paid
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payrolls'); }
};