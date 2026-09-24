<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOrderWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->with('items.product');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('order_id', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['items.product', 'statusHistory.user'])->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    public function invoice($id)
    {
        $order = Order::with(['items.product'])->findOrFail($id);
        return view('admin.orders.invoice', compact('order'));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'note' => 'nullable|string|max:500',
        ]);

        $newStatus = $request->input('status');
        $note = $request->input('note');

        $order->status = $newStatus;
        if ($newStatus === 'delivered') {
            $order->payment_status = 'paid';
        }
        $order->save();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $newStatus,
            'note' => $note ?: "Status updated to {$newStatus}",
            'created_by_user_id' => Auth::id(),
        ]);

        return back()->with('success', "Order #{$order->order_id} status updated to {$newStatus}.");
    }
}
