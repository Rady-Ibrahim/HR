<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                    ->orWhere('company_name', 'like', "%$s%")
                    ->orWhere('phone', 'like', "%$s%")
                    ->orWhere('customer_code', 'like', "%$s%");
            });
        }

        if ($request->filled('city'))   $query->where('city', $request->city);
        if ($request->filled('region')) $query->where('region', $request->region);
        if ($request->filled('status')) $query->where('status', $request->status);

        $customers = $query->withCount('requests')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $customers]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'company_name'      => 'nullable|string|max:255',
            'phone'             => 'required|string|max:20',
            'phone_alternative' => 'nullable|string|max:20',
            'email'             => 'nullable|email',
            'city'              => 'nullable|string',
            'region'            => 'nullable|string',
            'address'           => 'nullable|string',
            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',
            'notes'             => 'nullable|string',
        ]);

        $validated['customer_code'] = 'CUS-' . str_pad(Customer::count() + 1, 5, '0', STR_PAD_LEFT);

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء العميل بنجاح',
            'data'    => $customer,
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $customer = Customer::with([
            'requests' => function ($q) {
                $q->latest()->limit(10);
            },
            'employees',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $customer]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $customer  = Customer::findOrFail($id);
        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone'        => 'sometimes|string|max:20',
            'email'        => 'nullable|email',
            'city'         => 'nullable|string',
            'region'       => 'nullable|string',
            'address'      => 'nullable|string',
            'latitude'     => 'nullable|numeric',
            'longitude'    => 'nullable|numeric',
            'notes'        => 'nullable|string',
            'status'       => 'sometimes|in:active,inactive,blacklisted',
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث العميل بنجاح',
            'data'    => $customer,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        Customer::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف العميل بنجاح']);
    }

    public function assignEmployee(Request $request, $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $request->validate(['employee_id' => 'required|exists:employees,id']);
        $customer->employees()->syncWithoutDetaching([$request->employee_id]);
        return response()->json(['success' => true, 'message' => 'تم ربط المندوب بالعميل']);
    }

    public function removeEmployee($id, $employeeId): JsonResponse
    {
        Customer::findOrFail($id)->employees()->detach($employeeId);
        return response()->json(['success' => true, 'message' => 'تم إلغاء ربط المندوب']);
    }

    public function requests($id, Request $request): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $requests = $customer->requests()
            ->with('createdBy')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $requests]);
    }
}
