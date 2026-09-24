<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckoutNotice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutNoticeController extends Controller
{
    public function getActiveNotices(): JsonResponse
    {
        $data = CheckoutNotice::where('status', 'active')
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getAll(): JsonResponse
    {
        $data = CheckoutNotice::orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getById(int $id): JsonResponse
    {
        $data = CheckoutNotice::find($id);
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Checkout notice not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string',
            'priority' => 'nullable|integer',
            'backgroundColor' => 'nullable|string|max:20',
            'textColor' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
            'status' => 'nullable|in:active,inactive',
        ]);

        $item = CheckoutNotice::create([
            'text' => $validated['text'],
            'priority' => $validated['priority'] ?? 0,
            'background_color' => $validated['backgroundColor'] ?? '#FFF7ED',
            'text_color' => $validated['textColor'] ?? '#9A3412',
            'icon' => $validated['icon'] ?? 'alert',
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $notice = CheckoutNotice::find($id);
        if (!$notice) {
            return response()->json(['success' => false, 'message' => 'Checkout notice not found'], 404);
        }

        $validated = $request->validate([
            'text' => 'sometimes|string',
            'priority' => 'sometimes|integer',
            'backgroundColor' => 'sometimes|string|max:20',
            'textColor' => 'sometimes|string|max:20',
            'icon' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $updateData = [];
        if ($request->has('text')) $updateData['text'] = $validated['text'];
        if ($request->has('priority')) $updateData['priority'] = $validated['priority'];
        if ($request->has('backgroundColor')) $updateData['background_color'] = $validated['backgroundColor'];
        if ($request->has('textColor')) $updateData['text_color'] = $validated['textColor'];
        if ($request->has('icon')) $updateData['icon'] = $validated['icon'];
        if ($request->has('status')) $updateData['status'] = $validated['status'];

        $notice->update($updateData);

        return response()->json(['success' => true, 'data' => $notice->fresh()]);
    }

    public function remove(int $id): JsonResponse
    {
        CheckoutNotice::destroy($id);
        return response()->json(['success' => true, 'message' => 'Checkout notice deleted']);
    }
}
