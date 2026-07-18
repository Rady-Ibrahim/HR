<?php

namespace App\Http\Controllers\Api;

use App\Models\Collection;
use App\Models\CollectionDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionController
{
    public function index(Request $request): JsonResponse
    {
        $query = Collection::with(['delivery.request.customer', 'driver']);

        if ($request->filled('status'))          $query->where('collection_status', $request->status);
        if ($request->filled('driver_id'))       $query->where('driver_id', $request->driver_id);
        if ($request->filled('payment_method'))  $query->where('payment_method', $request->payment_method);
        if ($request->filled('date'))            $query->whereDate('collected_date', $request->date);
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('collected_date', $request->month)
                  ->whereYear('collected_date', $request->year);
        }

        $collections = $query->orderByDesc('created_at')->paginate($request->get('per_page', 15));

        $totalAmount = $query->sum('total_amount');

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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل التحصيل بنجاح',
                'data'    => $collection->load(['delivery.request.customer', 'details']),
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
        $collection = Collection::findOrFail($id);
        $validated  = $request->validate([
            'actual_amount' => 'required|numeric|min:0',
            'notes'         => 'nullable|string',
        ]);

        $difference = $validated['actual_amount'] - $collection->total_amount;
        $matched    = abs($difference) < 0.01;

        $collection->update([
            'collection_status' => 'deposited',
            'deposited_date'    => now(),
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'تم اعتماد التحصيل بنجاح',
            'data'       => $collection,
            'difference' => $difference,
            'matched'    => $matched,
        ]);
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $collection = Collection::findOrFail($id);
        $validated  = $request->validate(['reason' => 'required|string']);

        $collection->update([
            'collection_status' => 'rejected',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض التحصيل',
            'data'    => $collection,
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
}
