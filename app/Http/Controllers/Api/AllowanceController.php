<?php

namespace App\Http\Controllers\Api;

use App\Models\Allowance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllowanceController
{
    public function index(Request $request): JsonResponse
    {
        $query = Allowance::with('employee');

        if ($request->filled('employee_id'))    $query->where('employee_id', $request->employee_id);
        if ($request->filled('status'))         $query->where('status', $request->status);
        if ($request->filled('allowance_type')) $query->where('allowance_type', $request->allowance_type);

        $allowances = $query->orderByDesc('start_date')->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $allowances]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'allowance_type' => 'required|string|max:100',
            'amount'         => 'required|numeric|min:0',
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date|after:start_date',
            'recurring'      => 'boolean',
            'notes'          => 'nullable|string',
        ]);

        $validated['status'] = 'active';
        $allowance = Allowance::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة البدل بنجاح',
            'data'    => $allowance->load('employee'),
        ], 201);
    }

    public function show($id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Allowance::with('employee')->findOrFail($id)]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $allowance = Allowance::findOrFail($id);
        $validated = $request->validate([
            'amount'      => 'sometimes|numeric|min:0',
            'end_date'    => 'nullable|date',
            'status'      => 'sometimes|in:active,inactive,paused',
            'notes'       => 'nullable|string',
        ]);

        $allowance->update($validated);

        return response()->json(['success' => true, 'message' => 'تم تحديث البدل بنجاح', 'data' => $allowance]);
    }

    public function destroy($id): JsonResponse
    {
        Allowance::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف البدل بنجاح']);
    }

    public function employeeAllowances($employeeId): JsonResponse
    {
        $allowances = Allowance::where('employee_id', $employeeId)->where('status', 'active')->get();
        $total      = $allowances->sum('amount');

        return response()->json([
            'success'    => true,
            'data'       => $allowances,
            'total'      => $total,
            'by_type'    => $allowances->groupBy('allowance_type')->map->sum('amount'),
        ]);
    }
}
