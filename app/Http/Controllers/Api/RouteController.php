<?php

namespace App\Http\Controllers\Api;

use App\Models\Delivery;
use App\Models\Request as RequestModel;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController
{
    public function index(Request $request): JsonResponse
    {
        $query = RouteModel::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('route_name', 'like', "%$s%")
                    ->orWhere('route_code', 'like', "%$s%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $routes = $query->with(['driver', 'salesRep'])
            ->withCount(['deliveries', 'stops'])
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $routes]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'route_name'              => 'nullable|string|max:255',
            'driver_id'               => 'nullable|exists:employees,id',
            'sales_rep_id'            => 'nullable|exists:employees,id',
            'vehicle_number'          => 'nullable|string|max:50',
            'start_point'             => 'nullable|string|max:255',
            'end_point'               => 'nullable|string|max:255',
            'distance_km'             => 'nullable|numeric|min:0',
            'estimated_time_minutes'  => 'nullable|integer|min:0',
            'waypoints'               => 'nullable|array',
            'requests'                => 'nullable|array',
            'requests.*'              => 'exists:requests,id',
        ]);

        $validated['route_code'] = 'RT-' . now()->format('Ymd') . '-' . str_pad(RouteModel::whereDate('created_at', today())->count() + 1, 3, '0', STR_PAD_LEFT);

        $route = RouteModel::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء خط السير بنجاح',
            'data'    => $route,
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $route = RouteModel::with([
            'driver',
            'salesRep',
            'stops.customer',
            'deliveries.request.customer',
            'deliveries.driver',
        ])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $route]);
    }

    public function storeWithStops(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'route_name' => 'nullable|string|max:255',
            'driver_id' => 'nullable|exists:employees,id',
            'sales_rep_id' => 'nullable|exists:employees,id',
            'vehicle_number' => 'nullable|string|max:50',
            'start_point' => 'nullable|string|max:255',
            'end_point' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,archived',
            'stops' => 'required|array|min:1',
            'stops.*.customer_id' => 'required|exists:customers,id',
            'stops.*.request_ids' => 'nullable|array',
            'stops.*.request_ids.*' => 'exists:requests,id',
            'stops.*.packages_count' => 'nullable|integer|min:0',
            'stops.*.expected_amount' => 'nullable|numeric|min:0',
            'stops.*.goods_notes' => 'nullable|string',
            'stops.*.delivery_status' => 'nullable|in:pending,delivered,not_delivered',
            'stops.*.notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $route = RouteModel::create([
                'route_code' => 'RT-' . now()->format('Ymd') . '-' . str_pad(RouteModel::whereDate('created_at', today())->count() + 1, 3, '0', STR_PAD_LEFT),
                'route_name' => $validated['route_name'] ?? null,
                'driver_id' => $validated['driver_id'] ?? null,
                'sales_rep_id' => $validated['sales_rep_id'] ?? null,
                'vehicle_number' => $validated['vehicle_number'] ?? null,
                'start_point' => $validated['start_point'] ?? null,
                'end_point' => $validated['end_point'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);

            foreach ($validated['stops'] as $index => $stop) {
                RouteStop::create([
                    'route_id' => $route->id,
                    'customer_id' => $stop['customer_id'],
                    'stop_order' => $index + 1,
                    'request_ids' => $stop['request_ids'] ?? [],
                    'packages_count' => $stop['packages_count'] ?? 0,
                    'expected_amount' => $stop['expected_amount'] ?? null,
                    'goods_notes' => $stop['goods_notes'] ?? null,
                    'delivery_status' => $stop['delivery_status'] ?? 'pending',
                    'notes' => $stop['notes'] ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء خط السير بالعملاء بالترتيب',
                'data' => $route->load(['driver', 'salesRep', 'stops.customer']),
            ], 201);
        });
    }

    public function stops($id): JsonResponse
    {
        $route = RouteModel::with(['stops.customer'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $route->stops,
        ]);
    }

    public function updateWithStops(Request $request, $id): JsonResponse
    {
        $route = RouteModel::findOrFail($id);
        $validated = $request->validate([
            'route_name' => 'nullable|string|max:255',
            'driver_id' => 'nullable|exists:employees,id',
            'sales_rep_id' => 'nullable|exists:employees,id',
            'vehicle_number' => 'nullable|string|max:50',
            'start_point' => 'nullable|string|max:255',
            'end_point' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,archived',
            'stops' => 'required|array|min:1',
            'stops.*.customer_id' => 'required|exists:customers,id',
            'stops.*.request_ids' => 'nullable|array',
            'stops.*.request_ids.*' => 'exists:requests,id',
            'stops.*.packages_count' => 'nullable|integer|min:0',
            'stops.*.expected_amount' => 'nullable|numeric|min:0',
            'stops.*.goods_notes' => 'nullable|string',
            'stops.*.delivery_status' => 'nullable|in:pending,delivered,not_delivered',
            'stops.*.notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($route, $validated) {
            $route->update([
                'route_name' => $validated['route_name'] ?? null,
                'driver_id' => $validated['driver_id'] ?? null,
                'sales_rep_id' => $validated['sales_rep_id'] ?? null,
                'vehicle_number' => $validated['vehicle_number'] ?? null,
                'start_point' => $validated['start_point'] ?? null,
                'end_point' => $validated['end_point'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);

            $route->stops()->delete();
            foreach ($validated['stops'] as $index => $stop) {
                RouteStop::create([
                    'route_id' => $route->id,
                    'customer_id' => $stop['customer_id'],
                    'stop_order' => $index + 1,
                    'request_ids' => $stop['request_ids'] ?? [],
                    'packages_count' => $stop['packages_count'] ?? 0,
                    'expected_amount' => $stop['expected_amount'] ?? null,
                    'goods_notes' => $stop['goods_notes'] ?? null,
                    'delivery_status' => $stop['delivery_status'] ?? 'pending',
                    'notes' => $stop['notes'] ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث خط السير والعملاء بالترتيب',
                'data' => $route->fresh(['driver', 'salesRep', 'stops.customer']),
            ]);
        });
    }

    public function dispatch(Request $request, $id): JsonResponse
    {
        $route = RouteModel::with('stops')->findOrFail($id);
        $validated = $request->validate([
            'driver_id' => 'nullable|exists:employees,id',
            'sales_rep_id' => 'nullable|exists:employees,id',
            'vehicle_number' => 'nullable|string|max:50',
            'notify_employee_id' => 'nullable|exists:employees,id',
        ]);

        $driverId = $validated['driver_id'] ?? $route->driver_id;
        if (!$driverId) {
            return response()->json(['success' => false, 'message' => 'يجب تحديد السائق قبل ترحيل خط السير'], 422);
        }

        $deliveries = DB::transaction(function () use ($route, $validated, $driverId) {
            $created = collect();

            foreach ($route->stops as $stop) {
                $requestIds = $stop->request_ids ?: [];
                if (empty($requestIds)) {
                    $requestIds = RequestModel::where('customer_id', $stop->customer_id)
                        ->whereIn('status', ['approved', 'ready_for_delivery'])
                        ->limit(3)
                        ->pluck('id')
                        ->all();
                }

                foreach ($requestIds as $requestId) {
                    $req = RequestModel::find($requestId);
                    if (!$req) continue;

                    $delivery = Delivery::create([
                        'delivery_number' => 'DEL-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
                        'request_id' => $req->id,
                        'route_id' => $route->id,
                        'route_stop_id' => $stop->id,
                        'driver_id' => $driverId,
                        'sales_rep_id' => $validated['sales_rep_id'] ?? $route->sales_rep_id,
                        'vehicle_number' => $validated['vehicle_number'] ?? $route->vehicle_number,
                        'expected_collection_amount' => $stop->expected_amount,
                        'packages_count' => $stop->packages_count,
                        'collection_notify_employee_id' => $validated['notify_employee_id'] ?? null,
                        'delivery_items' => $stop->goods_notes ? ['goods_notes' => $stop->goods_notes] : null,
                        'status' => 'pending',
                    ]);

                    $req->update(['status' => 'in_delivery']);
                    $created->push($delivery);
                }
            }

            return $created;
        });

        return response()->json([
            'success' => true,
            'message' => 'تم ترحيل خط السير إلى التسليمات',
            'data' => $deliveries->load(['request.customer', 'driver', 'salesRep', 'routeStop.customer']),
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $route     = RouteModel::findOrFail($id);
        $validated = $request->validate([
            'route_name'             => 'nullable|string|max:255',
            'driver_id'              => 'nullable|exists:employees,id',
            'sales_rep_id'           => 'nullable|exists:employees,id',
            'vehicle_number'         => 'nullable|string|max:50',
            'start_point'            => 'nullable|string|max:255',
            'end_point'              => 'nullable|string|max:255',
            'distance_km'            => 'nullable|numeric|min:0',
            'estimated_time_minutes' => 'nullable|integer|min:0',
            'waypoints'              => 'nullable|array',
            'status'                 => 'sometimes|in:active,inactive,archived',
        ]);

        $route->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث خط السير بنجاح',
            'data'    => $route,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        RouteModel::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف خط السير بنجاح']);
    }

    public function daily(Request $request): JsonResponse
    {
        $date   = $request->get('date', today()->toDateString());
        $routes = RouteModel::whereDate('created_at', $date)
            ->with(['deliveries.request.customer', 'deliveries.driver'])
            ->get();

        $summary = [
            'total_routes'    => $routes->count(),
            'total_deliveries'=> $routes->sum(fn($r) => $r->deliveries->count()),
            'completed'       => $routes->sum(fn($r) => $r->deliveries->where('status', 'completed')->count()),
            'pending'         => $routes->sum(fn($r) => $r->deliveries->where('status', 'pending')->count()),
        ];

        return response()->json(['success' => true, 'data' => $routes, 'summary' => $summary]);
    }
}
