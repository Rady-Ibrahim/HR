<?php

namespace App\Http\Controllers\Api;

use App\Models\Advance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvanceController
{
    public function index(Request $request): JsonResponse
    {
        $query = Advance::with('employee');

        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        if ($request->filled('status'))      $query->where('status', $request->status);

        $advances = $query->orderByDesc('advance_date')->paginate($request->get('per_page', 15));

        $totals = [
            'total_amount'    => $query->sum('amount'),
            'total_remaining' => $query->sum('remaining_amount'),
        ];

        return response()->json(['success' => true, 'data' => $advances, 'totals' => $totals]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id'        => 'required|exists:employees,id',
            'amount'             => 'required|numeric|min:0',
            'advance_date'       => 'required|date',
            'installments_count' => 'required|integer|min:1',
            'notes'              => 'nullable|string',
        ]);

        $installmentAmount = round($validated['amount'] / $validated['installments_count'], 2);

        $advance = Advance::create(array_merge($validated, [
            'installment_amount'      => $installmentAmount,
            'paid_installments'       => 0,
            'remaining_installments'  => $validated['installments_count'],
            'remaining_amount'        => $validated['amount'],
            'status'                  => 'pending',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل السلفة بنجاح',
            'data'    => $advance->load('employee'),
        ], 201);
    }

    public function show($id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Advance::with('employee')->findOrFail($id)]);
    }

    public function approve($id): JsonResponse
    {
        Advance::findOrFail($id)->update(['status' => 'active']);
        return response()->json(['success' => true, 'message' => 'تم اعتماد السلفة وبدء الخصم']);
    }

    public function reject($id): JsonResponse
    {
        Advance::findOrFail($id)->update(['status' => 'paid']);
        return response()->json(['success' => true, 'message' => 'تم رفض السلفة']);
    }

    public function employeeSummary($employeeId): JsonResponse
    {
        $advances = Advance::where('employee_id', $employeeId)->get();

        $summary = [
            'total_advances'   => $advances->sum('amount'),
            'total_remaining'  => $advances->whereIn('status', ['active', 'partially_paid'])->sum('remaining_amount'),
            'total_paid'       => $advances->where('status', 'paid')->sum('amount'),
            'active_count'     => $advances->whereIn('status', ['active', 'partially_paid'])->count(),
        ];

        return response()->json(['success' => true, 'data' => $advances, 'summary' => $summary]);
    }
}
