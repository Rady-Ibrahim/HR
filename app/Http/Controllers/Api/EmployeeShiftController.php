<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use App\Models\EmployeeShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeShiftController
{
    public function index(Request $request): JsonResponse
    {
        $query = EmployeeShift::with(['employee', 'shift']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        if ($request->filled('active')) {
            $query->whereNull('effective_to');
        }

        $assignments = $query->orderByDesc('effective_from')->paginate($request->get('per_page', 50));

        return response()->json(['success' => true, 'data' => $assignments]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        // End any current active assignment for this employee
        EmployeeShift::where('employee_id', $validated['employee_id'])
            ->whereNull('effective_to')
            ->where('effective_from', '<', $validated['effective_from'])
            ->update(['effective_to' => $validated['effective_from']]);

        $assignment = EmployeeShift::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين الوردية للموظف بنجاح',
            'data' => $assignment->load(['employee', 'shift']),
        ], 201);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        $created = [];
        foreach ($validated['employee_ids'] as $empId) {
            EmployeeShift::where('employee_id', $empId)
                ->whereNull('effective_to')
                ->where('effective_from', '<', $validated['effective_from'])
                ->update(['effective_to' => $validated['effective_from']]);

            $created[] = EmployeeShift::create([
                'employee_id' => $empId,
                'shift_id' => $validated['shift_id'],
                'effective_from' => $validated['effective_from'],
                'effective_to' => $validated['effective_to'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين الوردية لـ ' . count($created) . ' موظف بنجاح',
            'data' => $created,
        ], 201);
    }

    public function current(Request $request, $employeeId): JsonResponse
    {
        $assignment = EmployeeShift::with('shift')
            ->where('employee_id', $employeeId)
            ->active(now())
            ->first();

        if (!$assignment) {
            $defaultShift = \App\Models\Shift::where('is_active', true)->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'assignment' => null,
                    'shift' => $defaultShift,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => $assignment,
                'shift' => $assignment->shift,
            ],
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $assignment = EmployeeShift::findOrFail($id);
        $assignment->delete();

        return response()->json(['success' => true, 'message' => 'تم إلغاء تعيين الوردية']);
    }
}
