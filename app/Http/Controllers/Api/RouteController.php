<?php

namespace App\Http\Controllers\Api;

use App\Models\Delivery;
use App\Models\Route as RouteModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $routes = $query->withCount('deliveries')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $routes]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'route_name'              => 'nullable|string|max:255',
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
        $route = RouteModel::with(['deliveries.request.customer', 'deliveries.driver'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $route]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $route     = RouteModel::findOrFail($id);
        $validated = $request->validate([
            'route_name'             => 'nullable|string|max:255',
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
