<?php

namespace App\Http\Controllers\Api;

use App\Models\Approval;
use App\Models\Collection;
use App\Models\Request as RequestModel;
use App\Models\Salary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController
{
    public function pending(Request $request): JsonResponse
    {
        $pendingRequests = RequestModel::where('status', 'under_review')
            ->with('customer', 'createdBy', 'items')
            ->orderByDesc('created_at')
            ->get();

        $pendingCollections = Collection::where('collection_status', 'pending')
            ->with('delivery.request.customer', 'driver')
            ->orderByDesc('created_at')
            ->get();

        $pendingSalaries = Salary::where('status', 'pending_approval')
            ->with('employee')
            ->orderByDesc('created_at')
            ->get();

        $summary = [
            'total_pending'      => $pendingRequests->count() + $pendingCollections->count() + $pendingSalaries->count(),
            'pending_requests'   => $pendingRequests->count(),
            'pending_collections'=> $pendingCollections->count(),
            'pending_salaries'   => $pendingSalaries->count(),
        ];

        return response()->json([
            'success'     => true,
            'summary'     => $summary,
            'data'        => [
                'requests'    => $pendingRequests,
                'collections' => $pendingCollections,
                'salaries'    => $pendingSalaries,
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $query = Approval::with('approver');

        if ($request->filled('type'))        $query->where('approvable_type', $request->type);
        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('approver_id')) $query->where('approved_by_id', $request->approver_id);

        $approvals = $query->orderByDesc('created_at')->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $approvals]);
    }

    public function approve(Request $request, $id): JsonResponse
    {
        $approval  = Approval::findOrFail($id);
        $validated = $request->validate(['notes' => 'nullable|string']);

        $approval->update([
            'status'         => 'approved',
            'approved_by_id' => auth()->user()->employee_id ?? 1,
            'notes'          => $validated['notes'] ?? null,
            'approved_at'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة بنجاح',
            'data'    => $approval,
        ]);
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $approval  = Approval::findOrFail($id);
        $validated = $request->validate(['rejection_reason' => 'required|string']);

        $approval->update([
            'status'           => 'rejected',
            'approved_by_id'   => auth()->user()->employee_id ?? 1,
            'rejection_reason' => $validated['rejection_reason'],
            'approved_at'      => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم الرفض',
            'data'    => $approval,
        ]);
    }
}
