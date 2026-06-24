<?php

namespace App\Http\Controllers\Api;

use App\Models\Commission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionController
{
    public function index(Request $request): JsonResponse
    {
        $query = Commission::with('employee');

        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('month'))       $query->where('month', $request->month);
        if ($request->filled('year'))        $query->where('year', $request->year);

        $commissions = $query->orderByDesc('created_at')->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $commissions]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'month'           => 'required|integer|min:1|max:12',
            'year'            => 'required|integer|min:2020',
            'amount'          => 'required|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'total_sales'     => 'nullable|numeric|min:0',
            'description'     => 'nullable|string',
        ]);

        $validated['status'] = 'pending';
        $commission = Commission::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل العمولة بنجاح',
            'data'    => $commission->load('employee'),
        ], 201);
    }

    public function show($id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Commission::with(['employee', 'approver'])->findOrFail($id)]);
    }

    public function approve($id): JsonResponse
    {
        Commission::findOrFail($id)->update([
            'status'         => 'approved',
            'approved_by_id' => auth()->user()->employee_id ?? 1,
        ]);
        return response()->json(['success' => true, 'message' => 'تم اعتماد العمولة بنجاح']);
    }

    public function reject($id): JsonResponse
    {
        Commission::findOrFail($id)->update(['status' => 'rejected']);
        return response()->json(['success' => true, 'message' => 'تم رفض العمولة']);
    }

    public function destroy($id): JsonResponse
    {
        Commission::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف العمولة بنجاح']);
    }

    public function monthlySummary(Request $request): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $commissions = Commission::where('month', $month)->where('year', $year)
            ->with('employee')->get();

        $summary = [
            'total'      => $commissions->sum('amount'),
            'approved'   => $commissions->where('status', 'approved')->sum('amount'),
            'pending'    => $commissions->where('status', 'pending')->sum('amount'),
            'count'      => $commissions->count(),
        ];

        return response()->json(['success' => true, 'data' => $commissions, 'summary' => $summary]);
    }
}
