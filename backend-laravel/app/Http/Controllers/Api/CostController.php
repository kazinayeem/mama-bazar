<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CostController extends Controller
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_LIMIT = 20;

    private function selectColumns()
    {
        return [
            'costs.id',
            'costs.title',
            'costs.cost_type as costType',
            'costs.quantity',
            'costs.unit_cost as unitCost',
            'costs.total_cost as totalCost',
            'suppliers.name as supplierName',
            'products.title as productName',
            'costs.order_id as orderOrderId',
            'costs.booking_id as bookingId',
            'costs.cost_date as costDate',
            'costs.payment_method as paymentMethod',
            'costs.notes',
            'costs.attachment_url as attachmentUrl',
            'costs.created_at as createdAt',
        ];
    }

    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) ($request->query('page') ?: self::DEFAULT_PAGE));
        $limit = max(1, (int) ($request->query('limit') ?: self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $query = DB::table('costs')
            ->leftJoin('suppliers', 'costs.supplier_id', '=', 'suppliers.id')
            ->leftJoin('products', 'costs.product_id', '=', 'products.id')
            ->leftJoin('orders', 'costs.order_id', '=', 'orders.id')
            ->leftJoin('bookings', 'costs.booking_id', '=', 'bookings.id');

        if ($request->filled('costType')) {
            $query->where('costs.cost_type', $request->query('costType'));
        }
        if ($request->filled('search')) {
            $query->where('costs.title', 'like', '%' . $request->query('search') . '%');
        }

        $total = $query->count();

        $data = $query->select($this->selectColumns())
            ->orderBy('costs.cost_date', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function getById(int $id): JsonResponse
    {
        $row = DB::table('costs')
            ->leftJoin('suppliers', 'costs.supplier_id', '=', 'suppliers.id')
            ->leftJoin('products', 'costs.product_id', '=', 'products.id')
            ->leftJoin('orders', 'costs.order_id', '=', 'orders.id')
            ->leftJoin('bookings', 'costs.booking_id', '=', 'bookings.id')
            ->where('costs.id', $id)
            ->select([
                'costs.id',
                'costs.title',
                'costs.cost_type as costType',
                'costs.quantity',
                'costs.unit_cost as unitCost',
                'costs.total_cost as totalCost',
                'costs.supplier_id as supplierId',
                'suppliers.name as supplierName',
                'costs.product_id as productId',
                'products.title as productName',
                'costs.order_id as orderId',
                'costs.booking_id as bookingId',
                'costs.cost_date as costDate',
                'costs.payment_method as paymentMethod',
                'costs.notes',
                'costs.attachment_url as attachmentUrl',
                'costs.created_at as createdAt',
                'costs.updated_at as updatedAt',
            ])
            ->first();

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Cost not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $row]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'costType' => 'nullable|string|max:50',
            'quantity' => 'nullable|numeric',
            'unitCost' => 'nullable|numeric',
            'totalCost' => 'nullable|numeric',
            'supplierId' => 'nullable|integer',
            'productId' => 'nullable|integer',
            'orderId' => 'nullable|integer',
            'bookingId' => 'nullable|integer',
            'costDate' => 'required|string',
            'paymentMethod' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'attachmentUrl' => 'nullable|string',
        ]);

        $cost = Cost::create([
            'title' => $validated['title'],
            'cost_type' => $validated['costType'] ?? 'operational',
            'quantity' => (string) ($validated['quantity'] ?? 1),
            'unit_cost' => (string) ($validated['unitCost'] ?? 0),
            'total_cost' => (string) ($validated['totalCost'] ?? 0),
            'supplier_id' => $validated['supplierId'] ?? null,
            'product_id' => $validated['productId'] ?? null,
            'order_id' => $validated['orderId'] ?? null,
            'booking_id' => $validated['bookingId'] ?? null,
            'cost_date' => $validated['costDate'],
            'payment_method' => $validated['paymentMethod'] ?? 'cash',
            'notes' => $validated['notes'] ?? null,
            'attachment_url' => $validated['attachmentUrl'] ?? null,
        ]);

        $created = $this->getById($cost->id)->getData()->data;
        return response()->json(['success' => true, 'data' => $created], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $cost = Cost::find($id);
        if (!$cost) {
            return response()->json(['success' => false, 'message' => 'Cost not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:200',
            'costType' => 'nullable|string|max:50',
            'quantity' => 'nullable|numeric',
            'unitCost' => 'nullable|numeric',
            'totalCost' => 'nullable|numeric',
            'supplierId' => 'nullable|integer',
            'productId' => 'nullable|integer',
            'orderId' => 'nullable|integer',
            'bookingId' => 'nullable|integer',
            'costDate' => 'sometimes|string',
            'paymentMethod' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'attachmentUrl' => 'nullable|string',
        ]);

        $updateData = [];
        if ($request->has('title')) $updateData['title'] = $validated['title'];
        if ($request->has('costType')) $updateData['cost_type'] = $validated['costType'];
        if ($request->has('quantity')) $updateData['quantity'] = (string) $validated['quantity'];
        if ($request->has('unitCost')) $updateData['unit_cost'] = (string) $validated['unitCost'];
        if ($request->has('totalCost')) $updateData['total_cost'] = (string) $validated['totalCost'];
        if ($request->has('supplierId')) $updateData['supplier_id'] = $validated['supplierId'];
        if ($request->has('productId')) $updateData['product_id'] = $validated['productId'];
        if ($request->has('orderId')) $updateData['order_id'] = $validated['orderId'];
        if ($request->has('bookingId')) $updateData['booking_id'] = $validated['bookingId'];
        if ($request->has('costDate')) $updateData['cost_date'] = $validated['costDate'];
        if ($request->has('paymentMethod')) $updateData['payment_method'] = $validated['paymentMethod'];
        if ($request->has('notes')) $updateData['notes'] = $validated['notes'];
        if ($request->has('attachmentUrl')) $updateData['attachment_url'] = $validated['attachmentUrl'];

        $cost->update($updateData);

        $fresh = $this->getById($id)->getData()->data;
        return response()->json(['success' => true, 'data' => $fresh]);
    }

    public function remove(int $id): JsonResponse
    {
        $cost = Cost::find($id);
        if (!$cost) {
            return response()->json(['success' => false, 'message' => 'Cost not found'], 404);
        }

        $cost->delete();
        return response()->json(['success' => true, 'message' => 'Cost deleted']);
    }
}
