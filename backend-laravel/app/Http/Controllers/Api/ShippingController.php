<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    private function toNum($v, $fallback = 0)
    {
        return is_numeric($v) ? (float) $v : $fallback;
    }

    public function getActiveMethods(): JsonResponse
    {
        $rows = ShippingMethod::where('status', 'active')
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $data = $rows->map(function ($m) {
            $arr = $m->toArray();
            $arr['charge'] = $this->toNum($m->charge);
            $arr['freeShippingMinAmount'] = $m->free_shipping_min_amount !== null ? $this->toNum($m->free_shipping_min_amount) : null;
            return $arr;
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function estimateShipping(Request $request): JsonResponse
    {
        $request->validate([
            'subtotal' => 'required|numeric',
        ]);

        $subtotal = (float) $request->input('subtotal');

        $rows = ShippingMethod::where('status', 'active')
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $data = $rows->map(function ($m) use ($subtotal) {
            $arr = $m->toArray();
            $charge = $this->toNum($m->charge);
            $freeMin = $m->free_shipping_min_amount !== null ? $this->toNum($m->free_shipping_min_amount) : null;
            $arr['charge'] = $charge;
            $arr['freeShippingMinAmount'] = $freeMin;

            $cost = $charge;
            if ($freeMin !== null && $subtotal >= $freeMin) {
                $cost = 0;
            }
            $arr['estimatedCost'] = $cost;
            return $arr;
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getAll(): JsonResponse
    {
        $data = ShippingMethod::orderBy('priority', 'asc')->orderBy('id', 'asc')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getById(int $id): JsonResponse
    {
        $data = ShippingMethod::find($id);
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Shipping method not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'charge' => 'required|numeric|min:0',
            'estimatedDelivery' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'priority' => 'nullable|integer',
            'freeShippingMinAmount' => 'nullable|numeric|min:0',
            'codAvailable' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        $method = ShippingMethod::create([
            'name' => $validated['name'],
            'charge' => $validated['charge'],
            'estimated_delivery' => $validated['estimatedDelivery'] ?? null,
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'] ?? 0,
            'free_shipping_min_amount' => $validated['freeShippingMinAmount'] ?? null,
            'cod_available' => $validated['codAvailable'] ?? true,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json(['success' => true, 'data' => $method], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $method = ShippingMethod::find($id);
        if (!$method) {
            return response()->json(['success' => false, 'message' => 'Shipping method not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'charge' => 'sometimes|numeric|min:0',
            'estimatedDelivery' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'priority' => 'sometimes|integer',
            'freeShippingMinAmount' => 'nullable|numeric|min:0',
            'codAvailable' => 'sometimes|boolean',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $updateData = [];
        if ($request->has('name')) $updateData['name'] = $validated['name'];
        if ($request->has('charge')) $updateData['charge'] = $validated['charge'];
        if ($request->has('estimatedDelivery')) $updateData['estimated_delivery'] = $validated['estimatedDelivery'];
        if ($request->has('description')) $updateData['description'] = $validated['description'];
        if ($request->has('priority')) $updateData['priority'] = $validated['priority'];
        if ($request->has('freeShippingMinAmount')) $updateData['free_shipping_min_amount'] = $validated['freeShippingMinAmount'];
        if ($request->has('codAvailable')) $updateData['cod_available'] = $validated['codAvailable'];
        if ($request->has('status')) $updateData['status'] = $validated['status'];

        $method->update($updateData);

        return response()->json(['success' => true, 'data' => $method->fresh()]);
    }

    public function remove(int $id): JsonResponse
    {
        ShippingMethod::destroy($id);
        return response()->json(['success' => true, 'message' => 'Shipping method deleted']);
    }
}
