<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use App\Models\Salary;
use App\Models\Attendance;
use Illuminate\Http\Request;

class EmployeeController
{
    public function index(Request $request)
    {
        $query = Employee::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        $employees = $query->with('manager')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'required|string|unique:employees,phone',
            'employee_code' => 'required|string|unique:employees,employee_code',
            'position' => 'required|string',
            'department' => 'required|string',
            'joining_date' => 'required|date',
            'base_salary' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,suspended,resigned,on_leave',
        ]);

        $employee = Employee::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الموظف بنجاح',
            'data' => $employee,
        ], 201);
    }

    public function show($id)
    {
        $employee = Employee::with([
            'manager',
            'subordinates',
            'salaries',
            'attendances',
            'incentives',
            'deductions',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:employees,email,' . $id,
            'phone' => 'sometimes|string|unique:employees,phone,' . $id,
            'position' => 'sometimes|string',
            'department' => 'sometimes|string',
            'base_salary' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive,suspended,resigned,on_leave',
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الموظف بنجاح',
            'data' => $employee,
        ]);
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الموظف بنجاح',
        ]);
    }

    public function getSalaryHistory($id)
    {
        $salaries = Salary::where('employee_id', $id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $salaries,
        ]);
    }

    public function getAttendanceRecords($id, Request $request)
    {
        $query = Attendance::where('employee_id', $id);

        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('attendance_date', $request->month)
                ->whereYear('attendance_date', $request->year);
        } else {
            $query->whereMonth('attendance_date', now()->month)
                ->whereYear('attendance_date', now()->year);
        }

        $records = $query->orderBy('attendance_date')->get();

        $stats = [
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'on_leave' => $records->where('status', 'on_leave')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $records,
            'statistics' => $stats,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:active,inactive,suspended,resigned,on_leave',
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الموظف بنجاح',
            'data' => $employee,
        ]);
    }
}
