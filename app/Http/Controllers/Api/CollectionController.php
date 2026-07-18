<?php

namespace App\Http\Controllers\Api;

use App\Models\Collection;
use App\Models\CollectionDetail;
use App\Models\Employee;
use App\Models\Request as RequestModel;
use App\Services\CollectionCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionController
{
    public function index(Request $request): JsonResponse
    {
        $query = Collection::with(['delivery.request.customer', 'driver.manager']);

        if ($request->filled('status'))          $query->where('collection_status', $request->status);
        if ($request->filled('driver_id'))       $query->where('driver_id', $request->driver_id);
        if ($request->filled('payment_method'))  $query->where('payment_method', $request->payment_method);
        if ($request->filled('date'))            $query->whereDate('collected_date', $request->date);
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('collected_date', $request->month)
                  ->whereYear('collected_date', $request->year);
        }

        $this->scopeForApprover($query);

        $collections = $query->orderByDesc('created_at')->paginate($request->get('per_page', 15));

        $totalAmount = (clone $query)->sum('total_amount');

        return response()->json([
            'success'      => true,
            'data'         => $collections,
            'total_amount' => $totalAmount,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delivery_id'    => 'required|exists:deliveries,id',
            'total_amount'   => 'required|numeric|min:0',
            'driver_id'      => 'nullable|exists:employees,id',
            'payment_method' => 'required|in:cash,bank_transfer,check,instapay,fawry',
            'collected_date' => 'required|date',
            'notes'          => 'nullable|string',
            'check_number'   => 'nullable|string|required_if:payment_method,check',
            'check_due_date' => 'nullable|date|required_if:payment_method,check',
            'requests'       => 'nullable|array',
            'requests.*.request_id' => 'exists:requests,id',
            'requests.*.amount'     => 'numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $validated['collection_number'] = 'COL-' . now()->format('YmdHis');
            $validated['collection_status'] = 'pending';
            $validated['driver_id']         = $validated['driver_id'] ?? $request->get('driver_id', auth()->user()->employee_id ?? 1);

            $collection = Collection::create($validated);

            // Store per-request breakdown
            if (!empty($validated['requests'])) {
                foreach ($validated['requests'] as $detail) {
                    CollectionDetail::create([
                        'collection_id' => $collection->id,
                        'request_id'    => $detail['request_id'],
                        'amount'        => $detail['amount'],
                    ]);
                }
            }

            $commission = app(CollectionCommissionService::class)
                ->createFromCollection($collection->load('driver'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل التحصيل بنجاح',
                'data'    => $collection->load(['delivery.request.customer', 'details', 'commission']),
                'commission' => $commission,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'فشل تسجيل التحصيل: ' . $e->getMessage()], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $collection = Collection::with([
            'delivery.request.customer',
            'driver',
            'details.request',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $collection]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $collection = Collection::findOrFail($id);
        $validated = $request->validate([
            'delivery_id'    => 'sometimes|exists:deliveries,id',
            'total_amount'   => 'sometimes|numeric|min:0',
            'driver_id'      => 'nullable|exists:employees,id',
            'payment_method' => 'sometimes|in:cash,bank_transfer,check,instapay,fawry',
            'collected_date' => 'sometimes|date',
            'notes'          => 'nullable|string',
            'check_number'   => 'nullable|string|required_if:payment_method,check',
            'check_due_date' => 'nullable|date|required_if:payment_method,check',
        ]);

        $collection->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التحصيل بنجاح',
            'data' => $collection->fresh(['delivery.request.customer', 'driver']),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        Collection::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف التحصيل بنجاح']);
    }

    public function approve(Request $request, $id): JsonResponse
    {
        $collection = Collection::with(['driver', 'delivery.request', 'details'])->findOrFail($id);
        $employee = $this->currentEmployee();
        $user = $request->user();

        if (!$collection->canBeApprovedBy($employee, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'فقط المدير المباشر للسائق/المندوب يمكنه اعتماد هذا التحصيل',
            ], 403);
        }

        if ($collection->collection_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذا التحصيل ليس في انتظار الموافقة',
            ], 422);
        }

        $validated = $request->validate([
            'actual_amount' => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string',
        ]);

        $actualAmount = $validated['actual_amount'] ?? $collection->total_amount;
        $difference = (float) $actualAmount - (float) $collection->total_amount;
        $matched = abs($difference) < 0.01;

        $collection->update([
            'collection_status' => 'deposited',
            'deposited_date' => now(),
            'notes' => $validated['notes'] ?? $collection->notes,
        ]);

        if ($collection->delivery?->request) {
            $collection->delivery->request->update(['status' => 'collected']);
        } elseif ($collection->details()->whereNotNull('request_id')->exists()) {
            $requestIds = $collection->details()->whereNotNull('request_id')->pluck('request_id');
            RequestModel::whereIn('id', $requestIds)->update(['status' => 'collected']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد التحصيل بنجاح',
            'data' => $collection->fresh(['delivery.request.customer', 'driver.manager']),
            'difference' => $difference,
            'matched' => $matched,
            'approved_by' => $employee?->only(['id', 'name', 'employee_code']),
        ]);
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $collection = Collection::with(['driver', 'delivery.request', 'details'])->findOrFail($id);
        $employee = $this->currentEmployee();
        $user = $request->user();

        if (!$collection->canBeApprovedBy($employee, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'فقط المدير المباشر للسائق/المندوب يمكنه رفض هذا التحصيل',
            ], 403);
        }

        if ($collection->collection_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذا التحصيل ليس في انتظار الموافقة',
            ], 422);
        }

        $validated = $request->validate(['reason' => 'required|string']);

        $collection->update([
            'collection_status' => 'rejected',
            'notes' => trim(($collection->notes ? $collection->notes . "\n" : '') . 'رفض: ' . $validated['reason']),
        ]);

        app(CollectionCommissionService::class)->syncOnCollectionRejected($collection);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض التحصيل',
            'data' => $collection->fresh(['delivery.request.customer', 'driver.manager']),
        ]);
    }

    public function dailySummary(Request $request): JsonResponse
    {
        $date = $request->get('date', today()->toDateString());

        $summary = Collection::whereDate('collected_date', $date)
            ->selectRaw('payment_method, collection_status, SUM(total_amount) as total, COUNT(*) as count')
            ->groupBy('payment_method', 'collection_status')
            ->get();

        $total = Collection::whereDate('collected_date', $date)->sum('total_amount');

        return response()->json([
            'success' => true,
            'data'    => $summary,
            'total'   => $total,
            'date'    => $date,
        ]);
    }

    public function driverSummary(Request $request, $driverId): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $collections = Collection::where('driver_id', $driverId)
            ->whereMonth('collected_date', $month)
            ->whereYear('collected_date', $year)
            ->with(['delivery.request.customer'])
            ->get();

        $summary = [
            'total_amount'   => $collections->sum('total_amount'),
            'count'          => $collections->count(),
            'by_method'      => $collections->groupBy('payment_method')->map->sum('total_amount'),
            'pending'        => $collections->where('collection_status', 'pending')->sum('total_amount'),
            'deposited'      => $collections->where('collection_status', 'deposited')->sum('total_amount'),
        ];

        return response()->json(['success' => true, 'data' => $collections, 'summary' => $summary]);
    }

    /**
     * Managers only see collections of their drivers/representatives.
     * Drivers see their own. HR / super_admin see all.
     */
    private function scopeForApprover($query): void
    {
        $user = auth()->user();
        if (!$user || $user->hasAnyRole(['super_admin', 'hr_manager'])) {
            return;
        }

        $employee = $this->currentEmployee();
        if (!$employee) {
            $query->whereRaw('1 = 0');
            return;
        }

        if ($user->hasRole('manager') || $employee->is_manager) {
            $query->whereHas('driver', function ($q) use ($employee) {
                $q->where('reporting_manager_id', $employee->id);
            });
            return;
        }

        $query->where('driver_id', $employee->id);
    }

    private function currentEmployee(): ?Employee
    {
        if (!auth()->id()) {
            return null;
        }

        return Employee::where('user_id', auth()->id())->first();
    }
}
