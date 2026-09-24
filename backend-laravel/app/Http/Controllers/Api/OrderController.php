<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\OrderService;
use App\Services\JwtService;
use Exception;

class OrderController extends Controller
{
    private function extractUserId(Request $request): ?int
    {
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $decoded = JwtService::verify($token);
            if ($decoded && isset($decoded['id'])) {
                return (int) $decoded['id'];
            }
        }
        return null;
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'required|string',
            'items' => 'required|array|min:1',
        ]);

        $input = $request->all();
        $input['userId'] = $this->extractUserId($request) ?: ($request->input('userId') ? (int) $request->input('userId') : null);

        $result = OrderService::createOrder($input);

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Order placed successfully',
        ], 201);
    }

    public function createOrder(Request $request)
    {
        return $this->create($request);
    }

    public function getAllOrders(Request $request)
    {
        return $this->getAll($request);
    }

    public function getOrderStats()
    {
        return $this->getStats();
    }

    public function getOrderById($id)
    {
        return $this->getById($id);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        return $this->updateStatus($request, $id);
    }

    public function updateCourierTracking(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }
        $order->courier_tracking_number = $request->input('trackingNumber');
        $order->save();
        return response()->json(['success' => true, 'data' => OrderService::formatOrder($order)]);
    }

    public function trackOrder(Request $request)
    {
        $orderId = $request->input('orderId');
        $phone = $request->input('phone');

        $query = Order::query();
        if ($orderId) {
            $query->where('order_id', trim($orderId));
        }
        if ($phone) {
            $query->where('phone', trim($phone));
        }

        $orders = $query->orderBy('created_at', 'desc')->take(10)->get();
        $formatted = $orders->map(fn($o) => OrderService::formatOrder($o))->toArray();

        return response()->json([
            'success' => true,
            'data' => ['orders' => $formatted],
        ]);
    }

    public function getMyOrders(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please sign in to view your orders'], 401);
        }

        $orders = Order::where('user_id', $user['id'])
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $orders->map(fn($o) => OrderService::formatOrder($o))->toArray();
        return response()->json(['success' => true, 'data' => $formatted]);
    }

    public function getCustomerInvoice(Request $request, $id)
    {
        $user = $request->attributes->get('auth_user');
        $order = Order::where('id', $id)->where('user_id', $user['id'])->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        return response()->json(['success' => true, 'data' => OrderService::formatOrder($order)]);
    }

    public function getAll(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $status = $request->input('status');
        $search = $request->input('search');

        $query = Order::query();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('order_id', 'like', $term)
                  ->orWhere('customer_name', 'like', $term)
                  ->orWhere('phone', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere('address', 'like', $term);
            });
        }

        $total = $query->count();
        $orders = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $formatted = $orders->map(fn($o) => OrderService::formatOrder($o))->toArray();

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function getStats()
    {
        $totalOrders = Order::count();
        $totalRevenue = (float) Order::where('status', 'delivered')->sum('total_price');
        $pendingOrders = Order::where('status', 'pending')->count();
        $pendingPayments = Order::where('payment_status', 'payment_verification')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'totalOrders' => $totalOrders,
                'totalRevenue' => $totalRevenue,
                'pendingOrders' => $pendingOrders,
                'pendingPayments' => $pendingPayments,
            ],
        ]);
    }

    public function getById($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        return response()->json(['success' => true, 'data' => OrderService::formatOrder($order)]);
    }

    public function getInvoice($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        return response()->json(['success' => true, 'data' => OrderService::formatOrder($order)]);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $user = $request->attributes->get('auth_user');

        $order->status = $validated['status'];
        if ($request->filled('trackingNumber')) {
            $order->courier_tracking_number = $request->input('trackingNumber');
        }
        $order->save();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'note' => $request->input('note'),
            'created_by_user_id' => $user['id'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => OrderService::formatOrder($order)]);
    }

    public function verifyPayment(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $action = $request->input('action'); // verify or reject
        $user = $request->attributes->get('auth_user');

        if ($action === 'verify') {
            $order->payment_status = 'verified';
            $order->status = 'confirmed';
        } else {
            $order->payment_status = 'rejected';
        }
        $order->save();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => $request->input('note', "Payment {$action}ed"),
            'created_by_user_id' => $user['id'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => OrderService::formatOrder($order)]);
    }

    public function addAdminNote(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $order->admin_notes = $request->input('note');
        $order->save();

        return response()->json(['success' => true, 'data' => OrderService::formatOrder($order)]);
    }

    public function remove($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $order->delete();
        return response()->json(['success' => true, 'message' => 'Order deleted']);
    }
}
