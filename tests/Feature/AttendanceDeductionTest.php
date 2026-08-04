<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AttendanceController;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Shift;
use App\Models\ShiftEarlyExitRule;
use App\Models\ShiftLateRule;
use App\Services\AttendancePenaltyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class AttendanceDeductionTest extends TestCase
{
    use DatabaseTransactions;

    private static int $seq = 0;

    private function makeEmployee(float $salary = 5000): Employee
    {
        self::$seq++;

        return Employee::create([
            'employee_code' => 'T' . self::$seq . '_' . uniqid(),
            'name'          => 'Test Emp ' . self::$seq,
            'phone'         => '010' . str_pad((string) self::$seq, 8, '0', STR_PAD_LEFT) . substr(uniqid(), -4),
            'position'      => 'Test',
            'department'    => 'Test',
            'joining_date'  => now()->subMonth()->toDateString(),
            'base_salary'   => $salary,
            'status'        => 'active',
        ]);
    }

    private function makeShift(): Shift
    {
        $shift = Shift::create([
            'name'                => 'Test Shift',
            'start_time'          => '08:00:00',
            'end_time'            => '17:00:00',
            'grace_period_minutes' => 15,
            'is_active'           => true,
        ]);

        ShiftLateRule::create(['shift_id' => $shift->id, 'min_delay_minutes' => 1, 'max_delay_minutes' => 119, 'deduction_type' => 'minutes', 'deduction_value' => null]);
        ShiftLateRule::create(['shift_id' => $shift->id, 'min_delay_minutes' => 120, 'max_delay_minutes' => null, 'deduction_type' => 'half_day', 'deduction_value' => null]);

        ShiftEarlyExitRule::create(['shift_id' => $shift->id, 'min_early_minutes' => 1, 'max_early_minutes' => 59, 'deduction_type' => 'minutes', 'deduction_value' => null]);
        ShiftEarlyExitRule::create(['shift_id' => $shift->id, 'min_early_minutes' => 60, 'max_early_minutes' => null, 'deduction_type' => 'half_day', 'deduction_value' => null]);

        return $shift;
    }

    private function assignShift(Employee $employee, Shift $shift): void
    {
        EmployeeShift::create([
            'employee_id'    => $employee->id,
            'shift_id'       => $shift->id,
            'effective_from' => now()->startOfMonth()->toDateString(),
            'effective_to'   => null,
        ]);
    }

    public function test_process_attendance_computes_late_and_early_exit_from_shift_rules(): void
    {
        $emp   = $this->makeEmployee();
        $shift = $this->makeShift();
        $this->assignShift($emp, $shift);

        $att = Attendance::create([
            'employee_id'     => $emp->id,
            'attendance_date' => now()->toDateString(),
            'check_in_time'   => '08:30:00',  // 30 min late → minutes
            'check_out_time'  => '15:00:00',  // 150 min early → half_day
            'status'          => 'present',
        ]);

        $processed = app(AttendancePenaltyService::class)->processAttendance($att);

        $this->assertSame(30, $processed->late_minutes);
        $this->assertSame('minutes', $processed->applied_late_deduction_type);
        $this->assertSame(150, $processed->early_exit_minutes);
        $this->assertSame('half_day', $processed->applied_early_deduction_type);
        $this->assertEquals($shift->id, $processed->shift_id);
    }

    public function test_record_deduction_amount_combines_late_minutes_and_early_half_day(): void
    {
        $emp   = $this->makeEmployee(5000);
        $shift = $this->makeShift();
        $this->assignShift($emp, $shift);

        $att = Attendance::create([
            'employee_id'     => $emp->id,
            'attendance_date' => now()->toDateString(),
            'check_in_time'   => '08:30:00',
            'check_out_time'  => '15:00:00',
            'status'          => 'present',
        ]);

        $processed = app(AttendancePenaltyService::class)->processAttendance($att);
        $result    = app(AttendancePenaltyService::class)->calculateRecordDeduction($processed);

        $dailyRate   = 5000 / $this->workingDays((int) now()->month, (int) now()->year);
        $minuteRate  = $dailyRate / 8 / 60;
        $expected    = round(30 * $minuteRate + ($dailyRate / 2), 2);

        $this->assertEqualsWithDelta($expected, $result['amount'], 0.01);
        $this->assertStringContainsString('تأخير', $result['label']);
        $this->assertStringContainsString('انصراف مبكر', $result['label']);
    }

    private function workingDays(int $month, int $year): int
    {
        $count = 0;
        $start = \Carbon\Carbon::createFromDate($year, $month, 1);
        $end   = $start->copy()->endOfMonth();
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if (!$day->isWeekend()) {
                $count++;
            }
        }

        return $count;
    }

    public function test_process_attendance_falls_back_to_config_hours_when_no_shift(): void
    {
        // ensure no active shift exists so resolveShift() returns null
        \App\Models\Shift::query()->update(['is_active' => false]);

        $emp = $this->makeEmployee(); // no shift assignment

        $att = Attendance::create([
            'employee_id'     => $emp->id,
            'attendance_date' => now()->toDateString(),
            'check_in_time'   => '08:30:00',
            'check_out_time'  => '15:00:00',
            'status'          => 'present',
        ]);

        $processed = app(AttendancePenaltyService::class)->processAttendance($att);

        $this->assertSame(30, $processed->late_minutes);
        $this->assertSame('minutes', $processed->applied_late_deduction_type);
        $this->assertSame(150, $processed->early_exit_minutes);
        $this->assertSame('minutes', $processed->applied_early_deduction_type);
    }

    public function test_checkout_endpoint_persists_checkout_time_and_returns_penalty(): void
    {
        $emp   = $this->makeEmployee();
        $shift = $this->makeShift();
        $this->assignShift($emp, $shift);

        Attendance::create([
            'employee_id'     => $emp->id,
            'attendance_date' => now()->toDateString(),
            'check_in_time'   => '08:00:00',
            'status'          => 'present',
        ]);

        $request = Request::create('/api/attendance/check-out', 'POST', [
            'employee_id' => $emp->id,
            'latitude'    => 30.0,
            'longitude'   => 31.0,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $controller = new AttendanceController(app(AttendancePenaltyService::class));
        $response   = $controller->checkOut($request);
        $payload    = json_decode($response->getContent(), true);

        $this->assertTrue($payload['success']);

        $saved = Attendance::where('employee_id', $emp->id)
            ->where('attendance_date', now()->toDateString())
            ->first();

        $this->assertNotNull($saved->check_out_time);
        $this->assertSame($saved->check_out_time, $payload['data']['check_out_time']);
        $this->assertNotNull($saved->early_exit_minutes);
        $this->assertNotNull($saved->applied_early_deduction_type);
        $this->assertSame($saved->early_exit_minutes, $payload['penalty']['early_exit_minutes']);
    }

    public function test_salary_deduction_includes_early_exit(): void
    {
        $emp   = $this->makeEmployee(5000);
        $shift = $this->makeShift();
        $this->assignShift($emp, $shift);

        Attendance::create([
            'employee_id'     => $emp->id,
            'attendance_date' => now()->toDateString(),
            'check_in_time'   => '08:00:00',
            'check_out_time'  => '15:00:00', // 150 min early → half_day
            'status'          => 'present',
        ]);

        $svc = app(AttendancePenaltyService::class);
        $svc->processAttendance(Attendance::where('employee_id', $emp->id)
            ->where('attendance_date', now()->toDateString())
            ->first());

        $summary = $svc->calculateAttendanceDeductionForSalary($emp, (int) now()->month, (int) now()->year, (float) $emp->base_salary);

        $this->assertGreaterThan(0, $summary['amount']);
        $this->assertSame(1, $summary['half_days']);
        $this->assertStringContainsString('نصف يوم', $summary['label']);
    }
}
