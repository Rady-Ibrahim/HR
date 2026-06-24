<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class AttendanceController
{
    public function index(Request $request): JsonResponse
    {
        $query = Attendance::with('employee');

        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('date'))        $query->where('attendance_date', $request->date);
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('attendance_date', $request->month)
                  ->whereYear('attendance_date', $request->year);
        }

        $records = $query->orderByDesc('attendance_date')->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $records]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
            'photo'       => 'nullable|image|max:3072',
        ]);

        $today  = today()->toDateString();
        $exists = Attendance::where('employee_id', $validated['employee_id'])
                            ->where('attendance_date', $today)
                            ->whereNotNull('check_in_time')
                            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'تم تسجيل الحضور مسبقاً لهذا اليوم'], 422);
        }

        $now            = now();
        $workStartTime  = Config::get('hr.working_hours.check_in_time', '08:00');
        $lateThreshold  = Config::get('hr.working_hours.late_threshold_minutes', 15);

        $scheduled  = Carbon::parse(today()->toDateString() . ' ' . $workStartTime);
        $lateMinutes = max(0, (int) $now->diffInMinutes($scheduled, false) * -1);
        $status      = $lateMinutes > $lateThreshold ? 'late' : 'present';

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance/checkin', 'public');
        }

        $record = Attendance::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'attendance_date' => $today],
            [
                'check_in_time'       => $now->toTimeString(),
                'check_in_latitude'   => $validated['latitude'],
                'check_in_longitude'  => $validated['longitude'],
                'check_in_photo'      => $photoPath,
                'status'              => $status,
                'late_minutes'        => $lateMinutes,
            ]
        );

        return response()->json([
            'success'      => true,
            'message'      => 'تم تسجيل الحضور بنجاح',
            'data'         => $record,
            'late_minutes' => $lateMinutes,
            'status'       => $status,
        ]);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
            'photo'       => 'nullable|image|max:3072',
        ]);

        $today  = today()->toDateString();
        $record = Attendance::where('employee_id', $validated['employee_id'])
                            ->where('attendance_date', $today)
                            ->first();

        if (!$record || !$record->check_in_time) {
            return response()->json(['success' => false, 'message' => 'لم يتم تسجيل الحضور بعد'], 422);
        }

        if ($record->check_out_time) {
            return response()->json(['success' => false, 'message' => 'تم تسجيل الانصراف مسبقاً'], 422);
        }

        $checkIn       = Carbon::parse($today . ' ' . $record->check_in_time);
        $checkOut      = now();
        $workingHours  = (int) $checkIn->diffInHours($checkOut);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance/checkout', 'public');
        }

        $record->update([
            'check_out_time'      => $checkOut->toTimeString(),
            'check_out_latitude'  => $validated['latitude'],
            'check_out_longitude' => $validated['longitude'],
            'check_out_photo'     => $photoPath,
            'working_hours'       => $workingHours,
        ]);

        return response()->json([
            'success'       => true,
            'message'       => 'تم تسجيل الانصراف بنجاح',
            'data'          => $record,
            'working_hours' => $workingHours,
        ]);
    }

    public function myRecords(Request $request): JsonResponse
    {
        $employeeId = auth()->user()->employee_id ?? 1;
        $month      = $request->get('month', now()->month);
        $year       = $request->get('year', now()->year);

        $records = Attendance::where('employee_id', $employeeId)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->orderBy('attendance_date')
            ->get();

        $stats = [
            'present'     => $records->where('status', 'present')->count(),
            'absent'      => $records->where('status', 'absent')->count(),
            'late'        => $records->where('status', 'late')->count(),
            'on_leave'    => $records->where('status', 'on_leave')->count(),
            'total_hours' => $records->sum('working_hours'),
            'total_late_minutes' => $records->sum('late_minutes'),
        ];

        return response()->json([
            'success'    => true,
            'data'       => $records,
            'statistics' => $stats,
        ]);
    }

    public function todaySummary(): JsonResponse
    {
        $today = today()->toDateString();
        $total = Employee::where('status', 'active')->count();

        $summary = [
            'total_employees' => $total,
            'present'         => Attendance::where('attendance_date', $today)->where('status', 'present')->count(),
            'late'            => Attendance::where('attendance_date', $today)->where('status', 'late')->count(),
            'absent'          => $total - Attendance::where('attendance_date', $today)->count(),
            'no_checkout'     => Attendance::where('attendance_date', $today)->whereNotNull('check_in_time')->whereNull('check_out_time')->count(),
        ];

        return response()->json(['success' => true, 'data' => $summary]);
    }

    public function requestLeave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id'  => 'required|exists:employees,id',
            'request_type' => 'required|in:sick,leave,late,early,excuse',
            'from_date'    => 'required|date',
            'to_date'      => 'required|date|after_or_equal:from_date',
            'reason'       => 'required|string',
        ]);

        $from = Carbon::parse($validated['from_date']);
        $to   = Carbon::parse($validated['to_date']);
        $validated['days_count'] = $from->diffInDays($to) + 1;

        $leaveRequest = AttendanceRequest::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب الإجازة بنجاح وهو في انتظار الموافقة',
            'data'    => $leaveRequest,
        ], 201);
    }

    public function approveLeave(Request $request, $id): JsonResponse
    {
        $leaveRequest = AttendanceRequest::findOrFail($id);
        $validated    = $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes'  => 'nullable|string',
        ]);

        $leaveRequest->update([
            'approval_status' => $validated['status'],
            'approved_by_id'  => auth()->user()->employee_id ?? 1,
            'approval_notes'  => $validated['notes'] ?? null,
        ]);

        if ($validated['status'] === 'approved') {
            $from = Carbon::parse($leaveRequest->from_date);
            $to   = Carbon::parse($leaveRequest->to_date);

            for ($date = $from; $date->lte($to); $date->addDay()) {
                Attendance::updateOrCreate(
                    ['employee_id' => $leaveRequest->employee_id, 'attendance_date' => $date->toDateString()],
                    ['status' => 'on_leave']
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => $validated['status'] === 'approved' ? 'تمت الموافقة على الإجازة' : 'تم رفض الإجازة',
            'data'    => $leaveRequest,
        ]);
    }

    public function leaveRequests(Request $request): JsonResponse
    {
        $query = AttendanceRequest::with('employee');

        if ($request->filled('status'))      $query->where('approval_status', $request->status);
        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);

        $requests = $query->orderByDesc('created_at')->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function monthlyReport(Request $request, $employeeId): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $records = Attendance::where('employee_id', $employeeId)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->orderBy('attendance_date')
            ->get();

        $employee     = Employee::findOrFail($employeeId);
        $workingDays  = $this->getWorkingDaysInMonth($month, $year);

        $stats = [
            'employee'          => $employee->only(['name', 'employee_code', 'position', 'department']),
            'month'             => $month,
            'year'              => $year,
            'working_days'      => $workingDays,
            'present'           => $records->where('status', 'present')->count(),
            'absent'            => $records->where('status', 'absent')->count(),
            'late'              => $records->where('status', 'late')->count(),
            'on_leave'          => $records->where('status', 'on_leave')->count(),
            'total_hours'       => $records->sum('working_hours'),
            'total_late_minutes'=> $records->sum('late_minutes'),
            'attendance_rate'   => $workingDays > 0 ? round(($records->where('status', 'present')->count() / $workingDays) * 100, 1) : 0,
        ];

        return response()->json(['success' => true, 'data' => $records, 'statistics' => $stats]);
    }

    private function getWorkingDaysInMonth(int $month, int $year): int
    {
        $start = Carbon::createFromDate($year, $month, 1);
        $end   = $start->copy()->endOfMonth();
        $count = 0;
        for ($day = $start; $day->lte($end); $day->addDay()) {
            if (!$day->isWeekend()) $count++;
        }
        return $count;
    }
}
