<?php

namespace App\Services;

use App\Models\Advance;
use App\Models\Allowance;
use App\Models\Attendance;
use App\Models\CarViolation;
use App\Models\Commission;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Incentive;
use App\Models\Salary;
use App\Models\SalaryComponentLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SalaryCalculationService
{
    public function calculate(Employee $employee, int $month, int $year): Salary
    {
        DB::beginTransaction();

        try {
            // Delete existing draft salary
            Salary::where('employee_id', $employee->id)
                  ->where('month', $month)
                  ->where('year', $year)
                  ->where('status', 'draft')
                  ->delete();

            $baseSalary      = (float) $employee->base_salary;
            $components      = [];

            // 1. Incentives
            $incentives = Incentive::where('employee_id', $employee->id)
                ->where('month', $month)->where('year', $year)
                ->where('status', 'approved')->get();
            $totalIncentives = $incentives->sum('amount');
            foreach ($incentives as $inc) {
                $components[] = ['type' => 'incentive', 'name' => $inc->incentive_type, 'id' => $inc->id, 'amount' => $inc->amount];
            }

            // 2. Allowances
            $allowances = Allowance::where('employee_id', $employee->id)
                ->where('status', 'active')
                ->where('start_date', '<=', Carbon::createFromDate($year, $month, 1)->endOfMonth())
                ->where(function ($q) use ($month, $year) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', Carbon::createFromDate($year, $month, 1));
                })->get();
            $totalAllowances = $allowances->sum('amount');
            foreach ($allowances as $all) {
                $components[] = ['type' => 'allowance', 'name' => $all->allowance_type, 'id' => $all->id, 'amount' => $all->amount];
            }

            // 3. Commissions
            $commissions = Commission::where('employee_id', $employee->id)
                ->where('month', $month)->where('year', $year)
                ->whereIn('status', ['pending', 'approved'])->get();
            $totalCommissions = $commissions->sum('amount');
            foreach ($commissions as $com) {
                $components[] = ['type' => 'commission', 'name' => 'عمولة مبيعات', 'id' => $com->id, 'amount' => $com->amount];
            }

            // Gross salary
            $grossSalary = $baseSalary + $totalIncentives + $totalAllowances + $totalCommissions;

            // 4. Deductions
            $deductions = Deduction::where('employee_id', $employee->id)
                ->where('month', $month)->where('year', $year)
                ->where('status', 'approved')->get();
            $totalDeductions = $deductions->sum('amount');
            foreach ($deductions as $ded) {
                $components[] = ['type' => 'deduction', 'name' => $ded->deduction_type, 'id' => $ded->id, 'amount' => -$ded->amount];
            }

            // 5. Attendance deductions (late, half-day late & absence)
            $attendanceSummary = $this->calculateAttendanceDeduction($employee, $month, $year, $baseSalary);
            $attendanceDeduction = $attendanceSummary['amount'];
            if ($attendanceDeduction > 0) {
                $totalDeductions += $attendanceDeduction;
                $components[]     = [
                    'type' => 'attendance_deduction',
                    'name' => $attendanceSummary['label'],
                    'id' => null,
                    'amount' => -$attendanceDeduction,
                ];
            }

            // 6. Advances
            $activeAdvances = Advance::where('employee_id', $employee->id)
                ->whereIn('status', ['active', 'partially_paid'])
                ->where('remaining_installments', '>', 0)->get();
            $totalAdvances = $activeAdvances->sum('installment_amount');
            foreach ($activeAdvances as $adv) {
                $components[] = ['type' => 'advance', 'name' => 'قسط سلفة', 'id' => $adv->id, 'amount' => -$adv->installment_amount];
            }

            // 7. Car violations
            $violations = CarViolation::where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->whereMonth('violation_date', $month)
                ->whereYear('violation_date', $year)->get();
            $totalViolations = $violations->sum('fine_amount');
            foreach ($violations as $vio) {
                $components[] = ['type' => 'violation', 'name' => $vio->violation_type, 'id' => $vio->id, 'amount' => -$vio->fine_amount];
            }

            $netSalary = $grossSalary - $totalDeductions - $totalAdvances - $totalViolations;
            $netSalary = max(0, $netSalary);

            // Create salary record
            $salary = Salary::create([
                'employee_id'       => $employee->id,
                'month'             => $month,
                'year'              => $year,
                'base_salary'       => $baseSalary,
                'gross_salary'      => $grossSalary,
                'total_incentives'  => $totalIncentives,
                'total_allowances'  => $totalAllowances,
                'total_commissions' => $totalCommissions,
                'total_deductions'  => $totalDeductions,
                'total_advances'    => $totalAdvances,
                'total_violations'  => $totalViolations,
                'net_salary'        => $netSalary,
                'status'            => 'draft',
            ]);

            // Log components
            foreach ($components as $comp) {
                SalaryComponentLog::create([
                    'salary_id'      => $salary->id,
                    'component_type' => $comp['type'],
                    'component_name' => $comp['name'],
                    'component_id'   => $comp['id'],
                    'amount'         => $comp['amount'],
                ]);
            }

            // Mark advances as paid for this month
            foreach ($activeAdvances as $adv) {
                $paid      = $adv->paid_installments + 1;
                $remaining = $adv->remaining_installments - 1;
                $remAmount = max(0, $adv->remaining_amount - $adv->installment_amount);
                $status    = $remaining <= 0 ? 'paid' : 'partially_paid';
                $adv->update([
                    'paid_installments'      => $paid,
                    'remaining_installments' => $remaining,
                    'remaining_amount'       => $remAmount,
                    'status'                 => $status,
                ]);
            }

            DB::commit();

            return $salary->load('components');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function calculateAttendanceDeduction(Employee $employee, int $month, int $year, float $baseSalary): array
    {
        $workingDays = $this->getWorkingDaysInMonth($month, $year);
        if ($workingDays === 0) {
            return ['amount' => 0, 'label' => 'خصم تأخير/غياب'];
        }

        $dailyRate   = $baseSalary / $workingDays;
        $hourlyRate  = $dailyRate / 8;
        $minuteRate  = $hourlyRate / 60;
        $halfDayAfterMinutes = (int) Config::get('hr.working_hours.half_day_deduction_after_minutes', 120);

        $records = Attendance::where('employee_id', $employee->id)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->get();

        $absentDays   = $records->where('status', 'absent')->count();
        $halfDayLateRecords = $records->filter(fn ($record) => (int) $record->late_minutes >= $halfDayAfterMinutes);
        $regularLateMinutes = $records
            ->reject(fn ($record) => (int) $record->late_minutes >= $halfDayAfterMinutes)
            ->sum('late_minutes');

        $absentDeduction = $absentDays * $dailyRate;
        $halfDayDeduction = $halfDayLateRecords->count() * ($dailyRate / 2);
        $lateDeduction   = $regularLateMinutes * $minuteRate;

        return [
            'amount' => round($absentDeduction + $halfDayDeduction + $lateDeduction, 2),
            'label' => sprintf(
                'خصم حضور: %d غياب، %d نصف يوم، %d دقيقة تأخير',
                $absentDays,
                $halfDayLateRecords->count(),
                $regularLateMinutes
            ),
        ];
    }

    private function getWorkingDaysInMonth(int $month, int $year): int
    {
        $start = Carbon::createFromDate($year, $month, 1);
        $end   = $start->copy()->endOfMonth();
        $count = 0;
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if (!$day->isWeekend()) $count++;
        }
        return $count;
    }

    public function calculateBulk(int $month, int $year, ?array $employeeIds = null): array
    {
        $query = Employee::where('status', 'active');
        if ($employeeIds) $query->whereIn('id', $employeeIds);

        $employees = $query->get();
        $results   = [];

        foreach ($employees as $employee) {
            try {
                $salary    = $this->calculate($employee, $month, $year);
                $results[] = ['employee_id' => $employee->id, 'name' => $employee->name, 'salary_id' => $salary->id, 'net_salary' => $salary->net_salary, 'status' => 'success'];
            } catch (\Exception $e) {
                $results[] = ['employee_id' => $employee->id, 'name' => $employee->name, 'status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }
}
