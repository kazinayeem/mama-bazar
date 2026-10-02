<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\ActivityLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CustomerAccountController extends Controller
{
    /**
     * Customer Account Dashboard overview.
     */
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        // Order count metrics
        $totalOrders = $user->orders()->count();
        $pendingOrders = $user->orders()->whereIn('status', ['pending', 'payment_pending', 'payment_verification'])->count();
        $processingOrders = $user->orders()->whereIn('status', ['confirmed', 'processing'])->count();
        $shippedOrders = $user->orders()->whereIn('status', ['shipped', 'out_for_delivery'])->count();
        $deliveredOrders = $user->orders()->where('status', 'delivered')->count();
        $cancelledOrders = $user->orders()->whereIn('status', ['cancelled', 'returned'])->count();

        // 5 most recent orders
        $recentOrders = $user->orders()
            ->with(['items.product'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // Default / primary address
        $defaultAddress = $user->addresses()->where('is_default', true)->first()
            ?? $user->addresses()->first();

        return view('web.account.dashboard', [
            'user' => $user,
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'shippedOrders' => $shippedOrders,
            'deliveredOrders' => $deliveredOrders,
            'cancelledOrders' => $cancelledOrders,
            'recentOrders' => $recentOrders,
            'defaultAddress' => $defaultAddress,
        ]);
    }

    /**
     * Customer Orders listing with filters and pagination.
     */
    public function orders(Request $request): View
    {
        $user = $request->user();

        $query = $user->orders()
            ->with(['items.product'])
            ->orderByDesc('id');

        // Status filter
        $status = $request->query('status');
        if ($status && $status !== 'all') {
            if ($status === 'pending') {
                $query->whereIn('status', ['pending', 'payment_pending', 'payment_verification']);
            } elseif ($status === 'processing') {
                $query->whereIn('status', ['confirmed', 'processing']);
            } elseif ($status === 'shipped') {
                $query->whereIn('status', ['shipped', 'out_for_delivery']);
            } elseif ($status === 'cancelled') {
                $query->whereIn('status', ['cancelled', 'returned']);
            } else {
                $query->where('status', $status);
            }
        }

        // Search by Order ID or invoice number
        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('courier_tracking_number', 'like', "%{$search}%");
            });
        }

        // Date filter
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('web.account.orders', [
            'user' => $user,
            'orders' => $orders,
            'currentStatus' => $status ?? 'all',
            'searchQuery' => $search ?? '',
        ]);
    }

    /**
     * Show single order details with ownership verification.
     */
    public function showOrder(Request $request, string $orderId): View
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->where(function ($q) use ($orderId) {
                $q->where('id', $orderId)
                    ->orWhere('order_id', $orderId);
            })
            ->with(['items.product', 'items.variant', 'statusHistory'])
            ->first();

        if (! $order) {
            abort(404, 'Order not found.');
        }

        // Build milestone progress
        $milestones = [
            ['key' => 'pending', 'label' => 'Order Placed'],
            ['key' => 'confirmed', 'label' => 'Confirmed'],
            ['key' => 'processing', 'label' => 'Processing'],
            ['key' => 'shipped', 'label' => 'Shipped'],
            ['key' => 'delivered', 'label' => 'Delivered'],
        ];

        $statusKeys = array_column($milestones, 'key');
        $currentIdx = array_search(strtolower($order->status), $statusKeys, true);
        if ($currentIdx === false) {
            $currentIdx = ($order->status === 'cancelled' || $order->status === 'returned') ? -1 : 0;
        }

        return view('web.account.order-details', [
            'user' => $user,
            'order' => $order,
            'milestones' => $milestones,
            'currentIdx' => $currentIdx,
        ]);
    }

    /**
     * View customer profile.
     */
    public function profile(Request $request): View
    {
        return view('web.account.profile', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update customer personal profile.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'shipping_area' => 'nullable|string|in:inside_dhaka,outside_dhaka',
            'shipping_address' => 'nullable|string|max:500',
        ], [
            'phone.unique' => 'This phone number is already associated with another account.',
        ]);

        $oldValues = [
            'name' => $user->name,
            'phone' => $user->phone,
            'shipping_address' => $user->shipping_address,
        ];

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'shipping_area' => $data['shipping_area'] ?? $user->shipping_area,
            'shipping_address' => $data['shipping_address'] ?? $user->shipping_address,
        ]);

        ActivityLoggerService::logCustomer(
            'customer.profile_updated',
            $user,
            "Customer updated profile: {$user->name}",
            [
                'actor' => $user,
                'source' => 'storefront',
                'oldValues' => $oldValues,
                'newValues' => [
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'shipping_address' => $user->shipping_address,
                ],
            ]
        );

        return back()->with('success', 'Your profile details have been successfully updated.');
    }

    /**
     * Manage customer saved addresses.
     */
    public function addresses(Request $request): View
    {
        $user = $request->user();
        $addresses = $user->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return view('web.account.addresses', [
            'user' => $user,
            'addresses' => $addresses,
        ]);
    }

    /**
     * Store new shipping address.
     */
    public function storeAddress(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->addresses()->count() >= 10) {
            return back()->with('error', 'You have reached the maximum limit of 10 saved addresses.');
        }

        $data = $request->validate([
            'recipient_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'alternative_phone' => 'nullable|string|max:20',
            'division' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'upazila' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:150',
            'shipping_area' => 'nullable|string|in:inside_dhaka,outside_dhaka',
            'address' => 'required|string|max:500',
            'apartment' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = $request->boolean('is_default') || $user->addresses()->count() === 0;

        if ($isDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = UserAddress::create([
            'user_id' => $user->id,
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'alternative_phone' => $data['alternative_phone'] ?? null,
            'email' => $user->email,
            'division' => $data['division'] ?? null,
            'district' => $data['district'] ?? null,
            'upazila' => $data['upazila'] ?? null,
            'area' => $data['area'] ?? null,
            'shipping_area' => $data['shipping_area'] ?? 'inside_dhaka',
            'address' => $data['address'],
            'apartment' => $data['apartment'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'is_default' => $isDefault,
        ]);

        if ($isDefault) {
            $user->update([
                'shipping_address' => $address->address,
                'shipping_area' => $address->shipping_area,
            ]);
        }

        return back()->with('success', 'New shipping address added successfully.');
    }

    /**
     * Update existing shipping address.
     */
    public function updateAddress(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        $data = $request->validate([
            'recipient_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'alternative_phone' => 'nullable|string|max:20',
            'division' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'upazila' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:150',
            'shipping_area' => 'nullable|string|in:inside_dhaka,outside_dhaka',
            'address' => 'required|string|max:500',
            'apartment' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = $request->boolean('is_default');

        if ($isDefault) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update([
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'alternative_phone' => $data['alternative_phone'] ?? null,
            'division' => $data['division'] ?? null,
            'district' => $data['district'] ?? null,
            'upazila' => $data['upazila'] ?? null,
            'area' => $data['area'] ?? null,
            'shipping_area' => $data['shipping_area'] ?? 'inside_dhaka',
            'address' => $data['address'],
            'apartment' => $data['apartment'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'is_default' => $isDefault ? true : $address->is_default,
        ]);

        if ($isDefault) {
            $user->update([
                'shipping_address' => $address->address,
                'shipping_area' => $address->shipping_area,
            ]);
        }

        return back()->with('success', 'Address details updated successfully.');
    }

    /**
     * Delete customer address with fallback default.
     */
    public function deleteAddress(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = $user->addresses()->first();
            if ($next) {
                $next->update(['is_default' => true]);
                $user->update([
                    'shipping_address' => $next->address,
                    'shipping_area' => $next->shipping_area,
                ]);
            }
        }

        return back()->with('success', 'Address removed successfully.');
    }

    /**
     * Mark an address as the default.
     */
    public function setDefaultAddress(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        $user->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        $user->update([
            'shipping_address' => $address->address,
            'shipping_area' => $address->shipping_area,
        ]);

        return back()->with('success', 'Default shipping address updated.');
    }

    /**
     * Account settings & password change.
     */
    public function settings(Request $request): View
    {
        return view('web.account.settings', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update customer account password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Please enter your current password.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The new password must be at least 8 characters.',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.'])->withInput();
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLoggerService::logSecurity(
            'customer.password_changed',
            "Customer {$user->name} changed their account password",
            [
                'actor' => $user,
                'source' => 'storefront',
            ]
        );

        return back()->with('success', 'Your password has been successfully changed.');
    }
}
