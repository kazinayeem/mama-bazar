<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function getActiveMethods(): JsonResponse
    {
        PaymentMethod::ensureDefaults();

        $data = PaymentMethod::activeCheckout()
            ->get()
            ->map(fn (PaymentMethod $m) => $m->toApiArray(true))
            ->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getAll(): JsonResponse
    {
        PaymentMethod::ensureDefaults();

        $data = PaymentMethod::ordered()
            ->get()
            ->map(fn (PaymentMethod $m) => $m->toApiArray())
            ->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getById(int $id): JsonResponse
    {
        $m = PaymentMethod::find($id);
        if (!$m) {
            return response()->json(['success' => false, 'message' => 'Payment method not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $m->toApiArray()]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_methods,code',
            'name' => 'required|string|max:100',
            'type' => 'required|in:cod,mobile_banking,bank,online',
            'enabled' => 'nullable|boolean',
            'sortOrder' => 'nullable|integer',
            'maintenanceMode' => 'nullable|boolean',
            'config' => 'nullable|array',
        ]);

        $item = PaymentMethod::create([
            'code' => strtolower(trim($validated['code'])),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'enabled' => $validated['enabled'] ?? true,
            'sort_order' => $validated['sortOrder'] ?? 0,
            'maintenance_mode' => $validated['maintenanceMode'] ?? false,
            'config' => $validated['config'] ?? [],
        ]);

        return response()->json(['success' => true, 'data' => $item->fresh()->toApiArray()], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $method = PaymentMethod::find($id);
        if (!$method) {
            return response()->json(['success' => false, 'message' => 'Payment method not found'], 404);
        }

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:payment_methods,code,' . $id,
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|in:cod,mobile_banking,bank,online',
            'enabled' => 'sometimes|boolean',
            'sortOrder' => 'sometimes|integer',
            'maintenanceMode' => 'sometimes|boolean',
            'config' => 'nullable|array',
        ]);

        $updateData = [];
        if ($request->has('code')) {
            $updateData['code'] = strtolower(trim($validated['code']));
        }
        if ($request->has('name')) {
            $updateData['name'] = $validated['name'];
        }
        if ($request->has('type')) {
            $updateData['type'] = $validated['type'];
        }
        if ($request->has('enabled')) {
            $updateData['enabled'] = $validated['enabled'];
        }
        if ($request->has('sortOrder')) {
            $updateData['sort_order'] = $validated['sortOrder'];
        }
        if ($request->has('maintenanceMode')) {
            $updateData['maintenance_mode'] = $validated['maintenanceMode'];
        }
        if ($request->has('config')) {
            $updateData['config'] = $validated['config'] ?? [];
        }

        $method->update($updateData);

        return response()->json(['success' => true, 'data' => $method->fresh()->toApiArray()]);
    }

    public function updateStatuses(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'enabled' => 'required|boolean',
        ]);

        PaymentMethod::whereIn('id', $request->input('ids'))
            ->update(['enabled' => $request->boolean('enabled')]);

        return response()->json(['success' => true, 'data' => ['success' => true]]);
    }

    public function remove(int $id): JsonResponse
    {
        $method = PaymentMethod::find($id);
        if (!$method) {
            return response()->json(['success' => false, 'message' => 'Payment method not found'], 404);
        }

        $method->delete();

        return response()->json(['success' => true, 'message' => 'Payment method deleted']);
    }
}
