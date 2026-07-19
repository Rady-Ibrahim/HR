<?php

namespace App\Http\Controllers\Api;

use App\Models\Advance;
use App\Models\Allowance;
use App\Models\CarViolation;
use App\Models\Commission;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Incentive;
use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialController
{
    /**
     * Mobile: financial transactions for the logged-in employee.
     * GET /api/me/financials?month=7&year=2026
     */
    public function myFinancials(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد ملف موظف مرتبط بهذا الحساب',
            ], 404);
        }

        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $monthStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $salary = Salary::with('components')
            ->where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('id')
            ->first();

        $incentives = Incentive::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('created_at')
            ->get();

        $allowances = Allowance::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where('start_date', '<=', $monthEnd)
            ->where(function ($q) use ($monthStart) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $monthStart);
            })
            ->orderByDesc('start_date')
            ->get();

        $commissions = Commission::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with('collection:id,collection_number,total_amount')
            ->orderByDesc('created_at')
            ->get();

        $deductions = Deduction::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->orderByDesc('created_at')
            ->get();

        $advances = Advance::where('employee_id', $employee->id)
            ->whereIn('status', ['active', 'partially_paid', 'pending', 'approved'])
            ->orderByDesc('created_at')
            ->get();

        $violations = CarViolation::where('employee_id', $employee->id)
            ->whereMonth('violation_date', $month)
            ->whereYear('violation_date', $year)
            ->orderByDesc('violation_date')
            ->get();

        $summary = [
            'base_salary' => (float) $employee->base_salary,
            'incentives_total' => (float) $incentives->where('status', 'approved')->sum('amount'),
            'allowances_total' => (float) $allowances->sum('amount'),
            'commissions_total' => (float) $commissions->where('status', 'approved')->sum('amount'),
            'commissions_pending_total' => (float) $commissions->where('status', 'pending')->sum('amount'),
            'deductions_total' => (float) $deductions->where('status', 'approved')->sum('amount'),
            'advances_installment_total' => (float) $advances
                ->whereIn('status', ['active', 'partially_paid'])
                ->where('remaining_installments', '>', 0)
                ->sum('installment_amount'),
            'violations_total' => (float) $violations->where('status', 'pending')->sum('fine_amount'),
            'salary_net' => $salary ? (float) $salary->net_salary : null,
            'salary_gross' => $salary ? (float) $salary->gross_salary : null,
            'salary_status' => $salary?->status,
        ];

        // Estimated net if no salary calculated yet (same formula spirit as SalaryCalculationService)
        if (!$salary) {
            $gross = $summary['base_salary']
                + $summary['incentives_total']
                + $summary['allowances_total']
                + $summary['commissions_total'];
            $estimatedNet = $gross
                - $summary['deductions_total']
                - $summary['advances_installment_total']
                - $summary['violations_total'];
            $summary['estimated_net'] = max(0, round($estimatedNet, 2));
        } else {
            $summary['estimated_net'] = (float) $salary->net_salary;
        }

        return response()->json([
            'success' => true,
            'month' => $month,
            'year' => $year,
            'employee' => $employee->only([
                'id', 'name', 'employee_code', 'position', 'department',
                'base_salary', 'collection_commission_rate',
            ]),
            'summary' => $summary,
            'data' => [
                'salary' => $salary,
                'incentives' => $incentives,
                'allowances' => $allowances,
                'commissions' => $commissions,
                'deductions' => $deductions,
                'advances' => $advances,
                'violations' => $violations,
            ],
        ]);
    }
}
