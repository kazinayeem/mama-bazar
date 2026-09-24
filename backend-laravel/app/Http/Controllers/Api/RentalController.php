<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RentalController extends Controller
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_LIMIT = 20;

    private function selectColumns()
    {
        return [
            'rentals.id',
            'rentals.rental_item as rentalItem',
            'rentals.product_id as productId',
            'products.title as productName',
            'rentals.customer_name as customerName',
            'rentals.phone',
            'rentals.email',
            'rentals.user_id as userId',
            'rentals.quantity',
            'rentals.start_date as startDate',
            'rentals.end_date as endDate',
            'rentals.return_date as returnDate',
            'rentals.rate_type as rateType',
            'rentals.daily_rate as dailyRate',
            'rentals.weekly_rate as weeklyRate',
            'rentals.monthly_rate as monthlyRate',
            'rentals.rate',
            'rentals.duration_units as durationUnits',
            'rentals.security_deposit as securityDeposit',
            'rentals.discount',
            'rentals.additional_charge as additionalCharge',
            'rentals.total_amount as totalAmount',
            'rentals.payment_status as paymentStatus',
            'rentals.status',
            'rentals.notes',
            'rentals.attachment_url as attachmentUrl',
            'rentals.created_by_id as createdById',
            'rentals.created_at as createdAt',
        ];
    }

    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) ($request->query('page') ?: self::DEFAULT_PAGE));
        $limit = max(1, (int) ($request->query('limit') ?: self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $query = DB::table('rentals')
            ->leftJoin('products', 'rentals.product_id', '=', 'products.id');

        if ($request->filled('status')) {
            $query->where('rentals.status', $request->query('status'));
        }
        if ($request->filled('paymentStatus')) {
            $query->where('rentals.payment_status', $request->query('paymentStatus'));
        }
        if ($request->filled('search')) {
            $query->where('rentals.customer_name', 'like', '%' . $request->query('search') . '%');
        }

        $total = $query->count();

        $data = $query->select($this->selectColumns())
            ->orderBy('rentals.created_at', 'desc')
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
        $row = DB::table('rentals')
            ->leftJoin('products', 'rentals.product_id', '=', 'products.id')
            ->where('rentals.id', $id)
            ->select(array_merge($this->selectColumns(), ['rentals.updated_at as updatedAt']))
            ->first();

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Rental not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $row]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rentalItem' => 'required|string|max:200',
            'productId' => 'nullable|integer',
            'customerName' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'userId' => 'nullable|integer',
            'quantity' => 'nullable|integer',
            'startDate' => 'required|string',
            'endDate' => 'required|string',
            'returnDate' => 'nullable|string',
            'rateType' => 'nullable|in:daily,weekly,monthly',
            'dailyRate' => 'nullable|numeric',
            'weeklyRate' => 'nullable|numeric',
            'monthlyRate' => 'nullable|numeric',
            'rate' => 'nullable|numeric',
            'durationUnits' => 'nullable|integer',
            'securityDeposit' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'additionalCharge' => 'nullable|numeric',
            'totalAmount' => 'nullable|numeric',
            'paymentStatus' => 'nullable|in:pending,partial,paid,refunded',
            'status' => 'nullable|in:reserved,rented,returned,overdue,cancelled',
            'notes' => 'nullable|string',
            'attachmentUrl' => 'nullable|string',
        ]);

        $actor = $request->user();

        $rental = Rental::create([
            'rental_item' => $validated['rentalItem'],
            'product_id' => $validated['productId'] ?? null,
            'customer_name' => $validated['customerName'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'user_id' => $validated['userId'] ?? null,
            'quantity' => $validated['quantity'] ?? 1,
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'],
            'return_date' => $validated['returnDate'] ?? null,
            'rate_type' => $validated['rateType'] ?? 'daily',
            'daily_rate' => (string) ($validated['dailyRate'] ?? 0),
            'weekly_rate' => (string) ($validated['weeklyRate'] ?? 0),
            'monthly_rate' => (string) ($validated['monthlyRate'] ?? 0),
            'rate' => (string) ($validated['rate'] ?? 0),
            'duration_units' => $validated['durationUnits'] ?? 0,
            'security_deposit' => (string) ($validated['securityDeposit'] ?? 0),
            'discount' => (string) ($validated['discount'] ?? 0),
            'additional_charge' => (string) ($validated['additionalCharge'] ?? 0),
            'total_amount' => (string) ($validated['totalAmount'] ?? 0),
            'payment_status' => $validated['paymentStatus'] ?? 'pending',
            'status' => $validated['status'] ?? 'reserved',
            'notes' => $validated['notes'] ?? null,
            'attachment_url' => $validated['attachmentUrl'] ?? null,
            'created_by_id' => $actor?->id,
        ]);

        $created = $this->getById($rental->id)->getData()->data;
        return response()->json(['success' => true, 'data' => $created], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rental = Rental::find($id);
        if (!$rental) {
            return response()->json(['success' => false, 'message' => 'Rental not found'], 404);
        }

        $validated = $request->validate([
            'rentalItem' => 'sometimes|string|max:200',
            'productId' => 'nullable|integer',
            'customerName' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:100',
            'userId' => 'nullable|integer',
            'quantity' => 'nullable|integer',
            'startDate' => 'sometimes|string',
            'endDate' => 'sometimes|string',
            'returnDate' => 'nullable|string',
            'rateType' => 'nullable|in:daily,weekly,monthly',
            'dailyRate' => 'nullable|numeric',
            'weeklyRate' => 'nullable|numeric',
            'monthlyRate' => 'nullable|numeric',
            'rate' => 'nullable|numeric',
            'durationUnits' => 'nullable|integer',
            'securityDeposit' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'additionalCharge' => 'nullable|numeric',
            'totalAmount' => 'nullable|numeric',
            'paymentStatus' => 'nullable|in:pending,partial,paid,refunded',
            'status' => 'nullable|in:reserved,rented,returned,overdue,cancelled',
            'notes' => 'nullable|string',
            'attachmentUrl' => 'nullable|string',
        ]);

        $updateData = [];
        if ($request->has('rentalItem')) $updateData['rental_item'] = $validated['rentalItem'];
        if ($request->has('productId')) $updateData['product_id'] = $validated['productId'];
        if ($request->has('customerName')) $updateData['customer_name'] = $validated['customerName'];
        if ($request->has('phone')) $updateData['phone'] = $validated['phone'];
        if ($request->has('email')) $updateData['email'] = $validated['email'];
        if ($request->has('userId')) $updateData['user_id'] = $validated['userId'];
        if ($request->has('quantity')) $updateData['quantity'] = $validated['quantity'];
        if ($request->has('startDate')) $updateData['start_date'] = $validated['startDate'];
        if ($request->has('endDate')) $updateData['end_date'] = $validated['endDate'];
        if ($request->has('returnDate')) $updateData['return_date'] = $validated['returnDate'];
        if ($request->has('rateType')) $updateData['rate_type'] = $validated['rateType'];
        if ($request->has('dailyRate')) $updateData['daily_rate'] = (string) $validated['dailyRate'];
        if ($request->has('weeklyRate')) $updateData['weekly_rate'] = (string) $validated['weeklyRate'];
        if ($request->has('monthlyRate')) $updateData['monthly_rate'] = (string) $validated['monthlyRate'];
        if ($request->has('rate')) $updateData['rate'] = (string) $validated['rate'];
        if ($request->has('durationUnits')) $updateData['duration_units'] = $validated['durationUnits'];
        if ($request->has('securityDeposit')) $updateData['security_deposit'] = (string) $validated['securityDeposit'];
        if ($request->has('discount')) $updateData['discount'] = (string) $validated['discount'];
        if ($request->has('additionalCharge')) $updateData['additional_charge'] = (string) $validated['additionalCharge'];
        if ($request->has('totalAmount')) $updateData['total_amount'] = (string) $validated['totalAmount'];
        if ($request->has('paymentStatus')) $updateData['payment_status'] = $validated['paymentStatus'];
        if ($request->has('status')) $updateData['status'] = $validated['status'];
        if ($request->has('notes')) $updateData['notes'] = $validated['notes'];
        if ($request->has('attachmentUrl')) $updateData['attachment_url'] = $validated['attachmentUrl'];

        $rental->update($updateData);

        $fresh = $this->getById($id)->getData()->data;
        return response()->json(['success' => true, 'data' => $fresh]);
    }

    public function remove(int $id): JsonResponse
    {
        $rental = Rental::find($id);
        if (!$rental) {
            return response()->json(['success' => false, 'message' => 'Rental not found'], 404);
        }

        $rental->delete();
        return response()->json(['success' => true]);
    }
}
