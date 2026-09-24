<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_LIMIT = 20;

    private function selectColumns()
    {
        return [
            'bookings.id',
            'bookings.customer_name as customerName',
            'bookings.phone',
            'bookings.email',
            'bookings.user_id as userId',
            'bookings.booking_type as bookingType',
            'bookings.service',
            'bookings.product_id as productId',
            'products.title as productName',
            'bookings.start_date as startDate',
            'bookings.end_date as endDate',
            'bookings.quantity',
            'bookings.price',
            'bookings.discount',
            'bookings.additional_cost as additionalCost',
            'bookings.total_amount as totalAmount',
            'bookings.payment_status as paymentStatus',
            'bookings.status',
            'bookings.notes',
            'bookings.attachment_url as attachmentUrl',
            'bookings.created_at as createdAt',
        ];
    }

    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) ($request->query('page') ?: self::DEFAULT_PAGE));
        $limit = max(1, (int) ($request->query('limit') ?: self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $query = DB::table('bookings')
            ->leftJoin('products', 'bookings.product_id', '=', 'products.id');

        if ($request->filled('status')) {
            $query->where('bookings.status', $request->query('status'));
        }
        if ($request->filled('paymentStatus')) {
            $query->where('bookings.payment_status', $request->query('paymentStatus'));
        }
        if ($request->filled('search')) {
            $query->where('bookings.customer_name', 'like', '%' . $request->query('search') . '%');
        }

        $total = $query->count();

        $data = $query->select($this->selectColumns())
            ->orderBy('bookings.created_at', 'desc')
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
        $row = DB::table('bookings')
            ->leftJoin('products', 'bookings.product_id', '=', 'products.id')
            ->where('bookings.id', $id)
            ->select(array_merge($this->selectColumns(), ['bookings.updated_at as updatedAt']))
            ->first();

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $row]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customerName' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'userId' => 'nullable|integer',
            'bookingType' => 'nullable|string|max:50',
            'service' => 'nullable|string|max:100',
            'productId' => 'nullable|integer',
            'startDate' => 'required|string',
            'endDate' => 'required|string',
            'quantity' => 'nullable|integer',
            'price' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'additionalCost' => 'nullable|numeric',
            'totalAmount' => 'nullable|numeric',
            'paymentStatus' => 'nullable|in:pending,partial,paid,refunded',
            'status' => 'nullable|in:pending,confirmed,active,completed,cancelled',
            'notes' => 'nullable|string',
            'attachmentUrl' => 'nullable|string',
        ]);

        $booking = Booking::create([
            'customer_name' => $validated['customerName'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'user_id' => $validated['userId'] ?? null,
            'booking_type' => $validated['bookingType'] ?? 'service',
            'service' => $validated['service'] ?? null,
            'product_id' => $validated['productId'] ?? null,
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'],
            'quantity' => $validated['quantity'] ?? 1,
            'price' => (string) ($validated['price'] ?? 0),
            'discount' => (string) ($validated['discount'] ?? 0),
            'additional_cost' => (string) ($validated['additionalCost'] ?? 0),
            'total_amount' => (string) ($validated['totalAmount'] ?? 0),
            'payment_status' => $validated['paymentStatus'] ?? 'pending',
            'status' => $validated['status'] ?? 'pending',
            'notes' => $validated['notes'] ?? null,
            'attachment_url' => $validated['attachmentUrl'] ?? null,
        ]);

        $created = $this->getById($booking->id)->getData()->data;
        return response()->json(['success' => true, 'data' => $created], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $validated = $request->validate([
            'customerName' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:100',
            'userId' => 'nullable|integer',
            'bookingType' => 'nullable|string|max:50',
            'service' => 'nullable|string|max:100',
            'productId' => 'nullable|integer',
            'startDate' => 'sometimes|string',
            'endDate' => 'sometimes|string',
            'quantity' => 'nullable|integer',
            'price' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'additionalCost' => 'nullable|numeric',
            'totalAmount' => 'nullable|numeric',
            'paymentStatus' => 'nullable|in:pending,partial,paid,refunded',
            'status' => 'nullable|in:pending,confirmed,active,completed,cancelled',
            'notes' => 'nullable|string',
            'attachmentUrl' => 'nullable|string',
        ]);

        $updateData = [];
        if ($request->has('customerName')) $updateData['customer_name'] = $validated['customerName'];
        if ($request->has('phone')) $updateData['phone'] = $validated['phone'];
        if ($request->has('email')) $updateData['email'] = $validated['email'];
        if ($request->has('userId')) $updateData['user_id'] = $validated['userId'];
        if ($request->has('bookingType')) $updateData['booking_type'] = $validated['bookingType'];
        if ($request->has('service')) $updateData['service'] = $validated['service'];
        if ($request->has('productId')) $updateData['product_id'] = $validated['productId'];
        if ($request->has('startDate')) $updateData['start_date'] = $validated['startDate'];
        if ($request->has('endDate')) $updateData['end_date'] = $validated['endDate'];
        if ($request->has('quantity')) $updateData['quantity'] = $validated['quantity'];
        if ($request->has('price')) $updateData['price'] = (string) $validated['price'];
        if ($request->has('discount')) $updateData['discount'] = (string) $validated['discount'];
        if ($request->has('additionalCost')) $updateData['additional_cost'] = (string) $validated['additionalCost'];
        if ($request->has('totalAmount')) $updateData['total_amount'] = (string) $validated['totalAmount'];
        if ($request->has('paymentStatus')) $updateData['payment_status'] = $validated['paymentStatus'];
        if ($request->has('status')) $updateData['status'] = $validated['status'];
        if ($request->has('notes')) $updateData['notes'] = $validated['notes'];
        if ($request->has('attachmentUrl')) $updateData['attachment_url'] = $validated['attachmentUrl'];

        $booking->update($updateData);

        $fresh = $this->getById($id)->getData()->data;
        return response()->json(['success' => true, 'data' => $fresh]);
    }

    public function remove(int $id): JsonResponse
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $booking->delete();
        return response()->json(['success' => true]);
    }
}
