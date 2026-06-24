<?php

namespace App\Http\Controllers\Api;

use App\Models\Request as RequestModel;
use App\Models\RequestItem;
use App\Models\Item;
use Illuminate\Http\Request;

class RequestController
{
    public function index(Request $request)
    {
        $query = RequestModel::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('request_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%");
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $requests = $query->with(['customer', 'items', 'createdBy'])
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse' => 'sometimes|string',
            'assigned_employee_id' => 'sometimes|exists:employees,id',
            'estimated_delivery_date' => 'sometimes|date',
            'notes' => 'sometimes|string',
        ]);

        $requestNumber = 'REQ-' . now()->format('YmdHis');
        $validated['request_number'] = $requestNumber;
        $validated['status'] = 'draft';
        $validated['created_by_id'] = auth()->user()->employee_id ?? 1;

        $requestModel = RequestModel::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الطلب بنجاح',
            'data' => $requestModel,
        ], 201);
    }

    public function show($id)
    {
        $request = RequestModel::with([
            'customer',
            'items',
            'items.item',
            'createdBy',
            'approvals',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $request,
        ]);
    }

    public function addItems(Request $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $totalAmount = 0;
        $totalQuantity = 0;

        foreach ($validated['items'] as $item) {
            $dbItem = Item::findOrFail($item['item_id']);
            $totalPrice = $item['quantity'] * $item['unit_price'];

            RequestItem::create([
                'request_id' => $id,
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'unit' => $dbItem->unit,
                'unit_price' => $item['unit_price'],
                'total_price' => $totalPrice,
            ]);

            $totalAmount += $totalPrice;
            $totalQuantity += $item['quantity'];
        }

        $requestModel->update([
            'total_amount' => $requestModel->total_amount + $totalAmount,
            'total_quantity' => $requestModel->total_quantity + $totalQuantity,
            'items_count' => $requestModel->items()->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة العناصر بنجاح',
            'data' => $requestModel,
        ]);
    }

    public function submitForReview($id)
    {
        $request = RequestModel::findOrFail($id);

        if ($request->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن مراجعة الطلب في هذه الحالة',
            ], 422);
        }

        $request->update([
            'status' => 'under_review',
            'prepared_at' => now(),
            'prepared_by_id' => auth()->user()->employee_id ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الطلب للمراجعة',
            'data' => $request,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'sometimes|string',
        ]);

        $requestModel->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_id' => auth()->user()->employee_id ?? 1,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد الطلب بنجاح',
            'data' => $requestModel,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $requestModel->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الطلب',
            'data' => $requestModel,
        ]);
    }
}
