<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class AttendancePenaltyService
{
    public function resolveShift(Employee $employee, Carbon $date): ?Shift
    {
        $assignment = EmployeeShift::where('employee_id', $employee->id)
            ->active($date)
            ->first();

        if ($assignment) {
            return $assignment->shift;
        }

        if ($defaultShift = Shift::where('is_active', true)->first()) {
            return $defaultShift;
        }

        return null;
    }

    public function calculateLatePenalty(Shift $shift, Carbon $checkInTime, Carbon $date): array
    {
        $scheduledStart = Carbon::parse($date->toDateString() . ' ' . $shift->start_time);
        $actualDelayMinutes = (int) $scheduledStart->diffInMinutes($checkInTime, false);
        $effectiveDelay = max(0, $actualDelayMinutes - $shift->grace_period_minutes);

        if ($effectiveDelay <= 0) {
            return [
                'late_minutes' => max(0, $actualDelayMinutes),
                'effective_delay' => 0,
                'deduction_type' => null,
                'deduction_amount' => 0.0,
            ];
        }

        $rules = $shift->lateRules()->orderBy('min_delay_minutes')->get();
        $matchedRule = null;

        foreach ($rules as $rule) {
            if ($effectiveDelay >= $rule->min_delay_minutes) {
                if ($rule->max_delay_minutes === null || $effectiveDelay <= $rule->max_delay_minutes) {
                    $matchedRule = $rule;
                    break;
                }
            }
        }

        if (!$matchedRule) {
            return [
                'late_minutes' => $actualDelayMinutes,
                'effective_delay' => $effectiveDelay,
                'deduction_type' => 'minutes',
                'deduction_amount' => 0.0,
            ];
        }

        $amount = $this->resolveAmount($matchedRule->deduction_type, $matchedRule->deduction_value, 0);

        return [
            'late_minutes' => $actualDelayMinutes,
            'effective_delay' => $effectiveDelay,
            'deduction_type' => $matchedRule->deduction_type,
            'deduction_amount' => $amount,
        ];
    }

    public function calculateEarlyExitPenalty(Shift $shift, Carbon $checkInTime, Carbon $checkOutTime, Carbon $date): array
    {
        if ($shift->end_time === null) {
            return [
                'early_exit_minutes' => 0,
                'actual_worked_hours' => round((int) $checkInTime->diffInMinutes($checkOutTime) / 60, 2),
                'deduction_type' => null,
                'deduction_amount' => 0.0,
            ];
        }

        $expectedEnd = Carbon::parse($date->toDateString() . ' ' . $shift->end_time);
        $workedMinutes = (int) $checkInTime->diffInMinutes($checkOutTime);
        $earlyMinutes = max(0, (int) $checkOutTime->diffInMinutes($expectedEnd, false));

        $actualWorkedHours = round($workedMinutes / 60, 2);

        if ($earlyMinutes <= 0) {
            return [
                'early_exit_minutes' => 0,
                'actual_worked_hours' => $actualWorkedHours,
                'deduction_type' => null,
                'deduction_amount' => 0.0,
            ];
        }

        $rules = $shift->earlyExitRules()->orderBy('min_early_minutes')->get();
        $matchedRule = null;

        foreach ($rules as $rule) {
            if ($earlyMinutes >= $rule->min_early_minutes) {
                if ($rule->max_early_minutes === null || $earlyMinutes <= $rule->max_early_minutes) {
                    $matchedRule = $rule;
                    break;
                }
            }
        }

        if (!$matchedRule) {
            return [
                'early_exit_minutes' => $earlyMinutes,
                'actual_worked_hours' => $actualWorkedHours,
                'deduction_type' => 'minutes',
                'deduction_amount' => 0.0,
            ];
        }

        $amount = $this->resolveAmount($matchedRule->deduction_type, $matchedRule->deduction_value, 0);

        return [
            'early_exit_minutes' => $earlyMinutes,
            'actual_worked_hours' => $actualWorkedHours,
            'deduction_type' => $matchedRule->deduction_type,
            'deduction_amount' => $amount,
        ];
    }

    public function calculateLateFromConfig(Carbon $checkInTime, Carbon $date): array
    {
        $start = Carbon::parse($date->toDateString() . ' ' . Config::get('hr.working_hours.check_in_time', '08:00'));
        $lateMinutes = max(0, (int) $start->diffInMinutes($checkInTime, false));

        return [
            'late_minutes' => $lateMinutes,
            'deduction_type' => $lateMinutes > 0 ? 'minutes' : null,
            'deduction_amount' => 0.0,
        ];
    }

    public function calculateEarlyExitFromConfig(Carbon $checkInTime, Carbon $checkOutTime, Carbon $date): array
    {
        $end = Carbon::parse($date->toDateString() . ' ' . Config::get('hr.working_hours.check_out_time', '17:00'));

        $workedMinutes = (int) $checkInTime->diffInMinutes($checkOutTime);
        $earlyMinutes = max(0, (int) $checkOutTime->diffInMinutes($end, false));

        return [
            'early_exit_minutes' => $earlyMinutes,
            'actual_worked_hours' => round($workedMinutes / 60, 2),
            'deduction_type' => $earlyMinutes > 0 ? 'minutes' : null,
            'deduction_amount' => 0.0,
        ];
    }

    public function processAttendance(Attendance $attendance): Attendance
    {
        $date = $attendance->attendance_date instanceof Carbon
            ? $attendance->attendance_date
            : Carbon::parse($attendance->attendance_date);

        $employee = $attendance->employee;

        if (!$employee) {
            return $attendance;
        }

        $shift = $attendance->shift ?? $this->resolveShift($employee, $date);

        if ($shift) {
            $attendance->shift_id = $shift->id;
        }

        $lateResult = ['late_minutes' => 0, 'deduction_type' => null, 'deduction_amount' => 0.0];
        $earlyResult = ['early_exit_minutes' => 0, 'actual_worked_hours' => 0.0, 'deduction_type' => null, 'deduction_amount' => 0.0];

        if ($attendance->check_in_time) {
            $checkIn = $attendance->check_in_time instanceof Carbon
                ? $attendance->check_in_time
                : Carbon::parse($date->toDateString() . ' ' . $attendance->check_in_time);

            $lateResult = $shift
                ? $this->calculateLatePenalty($shift, $checkIn, $date)
                : $this->calculateLateFromConfig($checkIn, $date);

            if ($attendance->check_out_time) {
                $checkOut = $attendance->check_out_time instanceof Carbon
                    ? $attendance->check_out_time
                    : Carbon::parse($date->toDateString() . ' ' . $attendance->check_out_time);

                $earlyResult = $shift
                    ? $this->calculateEarlyExitPenalty($shift, $checkIn, $checkOut, $date)
                    : $this->calculateEarlyExitFromConfig($checkIn, $checkOut, $date);
            }
        }

        $attendance->late_minutes = $lateResult['late_minutes'];
        $attendance->applied_late_deduction_type = $lateResult['deduction_type'];

        $attendance->early_exit_minutes = $earlyResult['early_exit_minutes'];
        $attendance->actual_worked_hours = $earlyResult['actual_worked_hours'];
        $attendance->applied_early_deduction_type = $earlyResult['deduction_type'];

        $totalDeduction = ($lateResult['deduction_amount'] ?? 0) + ($earlyResult['deduction_amount'] ?? 0);
        $attendance->deduction_amount = $totalDeduction;

        $attendance->save();

        return $attendance->fresh();
    }

    public function calculateAttendanceDeductionForSalary(Employee $employee, int $month, int $year, float $baseSalary): array
    {
        $halfDayAfterMinutes = (int) Config::get('hr.working_hours.half_day_deduction_after_minutes', 120);
        $workingDays = $this->getWorkingDaysInMonth($month, $year);

        if ($workingDays === 0) {
            return ['amount' => 0, 'label' => 'خصم تأخير/غياب', 'absent' => 0, 'half_days' => 0, 'late_minutes' => 0];
        }

        $dailyRate = $baseSalary / $workingDays;
        $hourlyRate = $dailyRate / 8;
        $minuteRate = $hourlyRate / 60;

        $records = Attendance::where('employee_id', $employee->id)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->get();

        $absentDays = $records->where('status', 'absent')->count();
        $absentDeduction = $absentDays * $dailyRate;

        $halfDayCount = 0;
        $regularLateMinutes = 0;
        $regularEarlyMinutes = 0;
        $fixedAmountDeduction = 0.0;

        foreach ($records as $record) {
            $deductionType = $record->applied_late_deduction_type;

            if ($deductionType === 'half_day') {
                $halfDayCount++;
            } elseif ($deductionType === 'full_day') {
                $absentDeduction += $dailyRate;
            } elseif ($deductionType === 'quarter_day') {
                $halfDayCount += 0.5;
            } elseif ($deductionType === 'fixed_amount') {
                $fixedAmountDeduction += (float) ($record->deduction_amount ?? 0);
            } else {
                $regularLateMinutes += $record->late_minutes ?? 0;
            }

            $earlyType = $record->applied_early_deduction_type;

            if ($earlyType === 'half_day') {
                $halfDayCount++;
            } elseif ($earlyType === 'full_day') {
                $absentDeduction += $dailyRate;
            } elseif ($earlyType === 'quarter_day') {
                $halfDayCount += 0.5;
            } elseif ($earlyType === 'fixed_amount') {
                if ($deductionType !== 'fixed_amount') {
                    $fixedAmountDeduction += (float) ($record->deduction_amount ?? 0);
                }
            } else {
                $regularEarlyMinutes += $record->early_exit_minutes ?? 0;
            }
        }

        $halfDayDeduction = $halfDayCount * ($dailyRate / 2);
        $lateDeduction = ($regularLateMinutes + $regularEarlyMinutes) * $minuteRate;

        $totalAmount = round($absentDeduction + $halfDayDeduction + $lateDeduction + $fixedAmountDeduction, 2);

        return [
            'amount' => $totalAmount,
            'label' => sprintf(
                'خصم حضور: %d غياب، %d نصف يوم، %d دقيقة تأخير/انصراف مبكر',
                $absentDays,
                (int) $halfDayCount,
                $regularLateMinutes + $regularEarlyMinutes
            ),
            'absent' => $absentDays,
            'half_days' => (int) $halfDayCount,
            'late_minutes' => $regularLateMinutes + $regularEarlyMinutes,
        ];
    }

    private function resolveAmount(string $deductionType, ?float $deductionValue, float $baseSalary): float
    {
        return match ($deductionType) {
            'quarter_day', 'half_day', 'full_day' => 0.0,
            'percentage' => $baseSalary * ($deductionValue ?? 0) / 100,
            'fixed_amount' => $deductionValue ?? 0,
            default => 0.0,
        };
    }

    public function calculateRecordDeduction(Attendance $attendance): array
    {
        $date = $attendance->attendance_date instanceof Carbon
            ? $attendance->attendance_date
            : Carbon::parse($attendance->attendance_date);

        $baseSalary = (float) ($attendance->employee?->base_salary ?? 0);
        $workingDays = $this->getWorkingDaysInMonth((int) $date->month, (int) $date->year);

        if ($baseSalary <= 0 || $workingDays === 0) {
            return ['amount' => 0.0, 'label' => '-'];
        }

        $dailyRate = $baseSalary / $workingDays;
        $hourlyRate = $dailyRate / 8;
        $minuteRate = $hourlyRate / 60;

        $amount = 0.0;
        $halfDays = 0;
        $lateMinutes = (int) ($attendance->late_minutes ?? 0);
        $earlyMinutes = (int) ($attendance->early_exit_minutes ?? 0);

        $lateType = $attendance->applied_late_deduction_type;
        if ($lateType === 'half_day') {
            $halfDays += 1;
        } elseif ($lateType === 'full_day') {
            $amount += $dailyRate;
        } elseif ($lateType === 'quarter_day') {
            $halfDays += 0.5;
        } elseif ($lateType === 'fixed_amount') {
            $amount += (float) ($attendance->deduction_amount ?? 0);
        } else {
            $amount += $lateMinutes * $minuteRate;
        }

        $earlyType = $attendance->applied_early_deduction_type;
        if ($earlyType === 'half_day') {
            $halfDays += 1;
        } elseif ($earlyType === 'full_day') {
            $amount += $dailyRate;
        } elseif ($earlyType === 'quarter_day') {
            $halfDays += 0.5;
        } elseif ($earlyType === 'fixed_amount') {
            if ($lateType !== 'fixed_amount') {
                $amount += (float) ($attendance->deduction_amount ?? 0);
            }
        } else {
            $amount += $earlyMinutes * $minuteRate;
        }

        $amount += $halfDays * ($dailyRate / 2);

        $label = [];
        if ($lateMinutes > 0) {
            $label[] = "{$lateMinutes} دقيقة تأخير";
        }
        if ($earlyMinutes > 0) {
            $label[] = "{$earlyMinutes} دقيقة انصراف مبكر";
        }
        if (empty($label)) {
            $label[] = $amount > 0 ? 'خصم تأخير/انصراف مبكر' : '-';
        }

        return [
            'amount' => round($amount, 2),
            'label' => implode('، ', $label),
        ];
    }


    private function getWorkingDaysInMonth(int $month, int $year): int
    {
        $start = Carbon::createFromDate($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $count = 0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if (!$day->isWeekend()) {
                $count++;
            }
        }

        return $count;
    }
}
