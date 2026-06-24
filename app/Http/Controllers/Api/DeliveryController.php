<?php

namespace App\Http\Controllers\Api;

use App\Models\Delivery;
use App\Models\Request as RequestModel;
use App\Models\Route as RouteModel;
use App\Models\VehicleTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DeliveryController
{
    public function index(Request $request): JsonResponse
    {
        $query = Delivery::with(['request.customer', 'driver', 'route']);

        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $deliveries = $query->orderByDesc('created_at')->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $deliveries]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'request_id'     => 'required|exists:requests,id',
            'driver_id'      => 'required|exists:employees,id',
            'route_id'       => 'nullable|exists:routes,id',
            'vehicle_number' => 'nullable|string|max:50',
        ]);

        $req = RequestModel::findOrFail($validated['request_id']);

        if (! in_array($req->status, ['approved', 'ready_for_delivery'])) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير جاهز للتسليم. يجب أن يكون معتمداً أولاً.',
            ], 422);
        }

        $validated['delivery_number'] = 'DEL-' . now()->format('YmdHis');
        $validated['status']          = 'pending';

        $delivery = Delivery::create($validated);

        $req->update(['status' => 'in_delivery']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء عملية التسليم بنجاح',
            'data'    => $delivery->load(['request.customer', 'driver']),
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $delivery = Delivery::with([
            'request.customer',
            'request.items.item',
            'driver',
            'route',
            'checkpoints',
            'collections',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $delivery]);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $delivery  = Delivery::with('request')->findOrFail($id);
        $validated = $request->validate([
            'status'           => 'required|in:pending,in_transit,completed,failed,partially_delivered',
            'delivery_notes'   => 'nullable|string',
            'start_latitude'   => 'nullable|numeric',
            'start_longitude'  => 'nullable|numeric',
            'end_latitude'     => 'nullable|numeric',
            'end_longitude'    => 'nullable|numeric',
        ]);

        $data = $validated;

        if ($validated['status'] === 'in_transit') {
            $data['start_time'] = now();
        }

        if (in_array($validated['status'], ['completed', 'failed', 'partially_delivered'])) {
            $data['end_time'] = now();
        }

        $delivery->update($data);

        // Sync request status
        $requestStatus = match ($validated['status']) {
            'completed'            => 'delivered',
            'partially_delivered'  => 'delivered',
            'in_transit'           => 'in_delivery',
            default                => $delivery->request->status,
        };

        if ($delivery->request) {
            $delivery->request->update(['status' => $requestStatus]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة التسليم بنجاح',
            'data'    => $delivery,
        ]);
    }

    public function uploadProof(Request $request, $id): JsonResponse
    {
        $delivery  = Delivery::findOrFail($id);
        $validated = $request->validate([
            'delivery_photo' => 'nullable|image|max:5120',
            'signature'      => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        if ($request->hasFile('delivery_photo')) {
            $path = $request->file('delivery_photo')->store('deliveries/photos', 'public');
            $delivery->update(['delivery_photo' => $path]);
        }

        if ($request->filled('signature')) {
            $delivery->update(['signature_proof' => $validated['signature']]);
        }

        if ($request->filled('notes')) {
            $delivery->update(['delivery_notes' => $validated['notes']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفع إثبات التسليم بنجاح',
            'data'    => $delivery,
        ]);
    }

    public function addTracking(Request $request, $id): JsonResponse
    {
        Delivery::findOrFail($id);

        $validated = $request->validate([
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
            'speed'       => 'nullable|numeric',
            'direction'   => 'nullable|string',
            'captured_at' => 'nullable|date',
        ]);

        $tracking = VehicleTracking::create(array_merge(
            $validated,
            [
                'delivery_id' => $id,
                'captured_at' => $validated['captured_at'] ?? now(),
            ]
        ));

        return response()->json(['success' => true, 'data' => $tracking]);
    }

    public function tracking($id): JsonResponse
    {
        $points = VehicleTracking::where('delivery_id', $id)
            ->orderBy('captured_at')
            ->get(['latitude', 'longitude', 'speed', 'direction', 'captured_at']);

        return response()->json(['success' => true, 'data' => $points]);
    }

    public function driverDeliveries(Request $request): JsonResponse
    {
        $employeeId = auth()->user()->employee_id ?? 1;

        $deliveries = Delivery::with(['request.customer'])
            ->where('driver_id', $employeeId)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('date'), fn($q) => $q->whereDate('created_at', $request->date))
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $deliveries]);
    }
}
