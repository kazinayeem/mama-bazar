<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    private function parseConfig($config)
    {
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            return is_array($decoded) ? $decoded : (object)[];
        }
        return $config ?? (object)[];
    }

    public function getActiveMethods(): JsonResponse
    {
        $rows = PaymentMethod::where('enabled', true)
            ->where('maintenance_mode', false)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $data = $rows->map(function ($m) {
            return [
                'id' => $m->id,
                'code' => $m->code,
                'name' => $m->name,
                'type' => $m->type,
                'config' => $this->parseConfig($m->config),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getAll(): JsonResponse
    {
        $rows = PaymentMethod::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();
        $data = $rows->map(function ($m) {
            $arr = $m->toArray();
            $arr['config'] = $this->parseConfig($m->config);
            return $arr;
        });
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getById(int $id): JsonResponse
    {
        $m = PaymentMethod::find($id);
        if (!$m) {
            return response()->json(['success' => false, 'message' => 'Payment method not found'], 404);
        }
        $arr = $m->toArray();
        $arr['config'] = $this->parseConfig($m->config);
        return response()->json(['success' => true, 'data' => $arr]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_methods,code',
            'name' => 'required|string|max:100',
            'type' => 'required|string|max:50',
            'enabled' => 'nullable|boolean',
            'sortOrder' => 'nullable|integer',
            'maintenanceMode' => 'nullable|boolean',
            'config' => 'nullable',
        ]);

        $item = PaymentMethod::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'enabled' => $validated['enabled'] ?? true,
            'sort_order' => $validated['sortOrder'] ?? 0,
            'maintenance_mode' => $validated['maintenanceMode'] ?? false,
            'config' => $validated['config'] ?? [],
        ]);

        return response()->json(['success' => true, 'data' => $item], 201);
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
            'type' => 'sometimes|string|max:50',
            'enabled' => 'sometimes|boolean',
            'sortOrder' => 'sometimes|integer',
            'maintenanceMode' => 'sometimes|boolean',
            'config' => 'nullable',
        ]);

        $updateData = [];
        if ($request->has('code')) $updateData['code'] = $validated['code'];
        if ($request->has('name')) $updateData['name'] = $validated['name'];
        if ($request->has('type')) $updateData['type'] = $validated['type'];
        if ($request->has('enabled')) $updateData['enabled'] = $validated['enabled'];
        if ($request->has('sortOrder')) $updateData['sort_order'] = $validated['sortOrder'];
        if ($request->has('maintenanceMode')) $updateData['maintenance_mode'] = $validated['maintenanceMode'];
        if ($request->has('config')) $updateData['config'] = $validated['config'];

        $method->update($updateData);

        return response()->json(['success' => true, 'data' => $method->fresh()]);
    }

    public function updateStatuses(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'enabled' => 'required|boolean',
        ]);

        $ids = $request->input('ids');
        $enabled = $request->input('enabled');

        PaymentMethod::whereIn('id', $ids)->update(['enabled' => $enabled]);

        return response()->json(['success' => true, 'data' => ['success' => true]]);
    }

    public function remove(int $id): JsonResponse
    {
        PaymentMethod::destroy($id);
        return response()->json(['success' => true, 'message' => 'Payment method deleted']);
    }
}
