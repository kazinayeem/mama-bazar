<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Order;
use App\Services\JwtService;
use App\Services\RbacService;
use App\Services\OrderService;
use Exception;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        if (User::where('phone', $validated['phone'])->exists()) {
            return response()->json(['success' => false, 'message' => 'Phone number already registered'], 409);
        }

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $identifier = trim($validated['phone']);
        $user = User::where('phone', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        if ($user->status === 'inactive') {
            return response()->json(['success' => false, 'message' => 'Account is inactive'], 403);
        }

        $user->update(['last_login_at' => now()]);

        $resolved = RbacService::resolveUserPermissions($user->id, $user->role, $user->custom_role);
        $permissions = $resolved['permissions'];
        $customRole = $resolved['customRole'];

        $token = JwtService::sign([
            'id' => $user->id,
            'phone' => $user->phone,
            'role' => $user->role,
            'customRole' => $customRole,
            'permissions' => $permissions,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'role' => $user->role,
                    'customRole' => $customRole,
                    'permissions' => $permissions,
                    'status' => $user->status,
                    'lastLoginAt' => now()->toIso8601String(),
                ],
            ],
        ]);
    }

    public function devLogin(Request $request)
    {
        if (config('app.env') === 'production') {
            return response()->json(['success' => false, 'message' => 'Development login is unavailable'], 404);
        }

        $role = $request->input('role');
        if ($role === 'SUPER_ADMIN' || $role === 'admin') {
            $user = User::where('phone', '01711111111')
                ->orWhere('phone', '01943124215')
                ->orWhere('custom_role', 'SUPER_ADMIN')
                ->orWhere('role', 'admin')
                ->first();
        } else {
            $user = User::where('phone', '01700000000')->orWhere('role', 'user')->first();
            if (!$user) {
                $user = User::where('phone', '01711111111')
                    ->orWhere('phone', '01943124215')
                    ->orWhere('custom_role', 'SUPER_ADMIN')
                    ->orWhere('role', 'admin')
                    ->first();
            }
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Development account not found in database'], 404);
        }

        if ($user->status === 'inactive') {
            return response()->json(['success' => false, 'message' => 'Account is inactive'], 403);
        }

        $user->update(['last_login_at' => now()]);

        $resolved = RbacService::resolveUserPermissions($user->id, $user->role, $user->custom_role);
        $permissions = $resolved['permissions'];
        $customRole = $resolved['customRole'];

        $token = JwtService::sign([
            'id' => $user->id,
            'phone' => $user->phone,
            'role' => $user->role,
            'customRole' => $customRole,
            'permissions' => $permissions,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'role' => $user->role,
                    'customRole' => $customRole,
                    'permissions' => $permissions,
                    'status' => $user->status,
                    'lastLoginAt' => now()->toIso8601String(),
                ],
            ],
        ]);
    }

    public function createAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,manager',
        ]);

        if (User::where('phone', $validated['phone'])->exists()) {
            return response()->json(['success' => false, 'message' => 'An account with this phone number already exists'], 409);
        }
        if (User::where('email', $validated['email'])->exists()) {
            return response()->json(['success' => false, 'message' => 'An admin with this email already exists'], 409);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'custom_role' => strtoupper($validated['role']),
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin created successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ], 201);
    }

    public function requestPasswordReset(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
        return response()->json(['success' => true, 'message' => 'Password reset link sent to phone']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'newPassword' => 'required|string|min:6',
        ]);
        return response()->json(['success' => true, 'message' => 'Password reset successfully']);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'oldPassword' => 'required|string',
            'newPassword' => 'required|string|min:6',
        ]);

        $auth = $request->attributes->get('auth_user');
        $user = User::find($auth['id']);

        if (!$user || !Hash::check($validated['oldPassword'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect'], 400);
        }

        $user->update(['password' => Hash::make($validated['newPassword'])]);

        return response()->json(['success' => true, 'message' => 'Password changed successfully']);
    }

    public function getProfile(Request $request)
    {
        $auth = $request->attributes->get('auth_user');
        $user = User::find($auth['id']);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $resolved = RbacService::resolveUserPermissions($user->id, $user->role, $user->custom_role);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'shippingArea' => $user->shipping_area,
                'shippingAddress' => $user->shipping_address,
                'role' => $user->role,
                'customRole' => $resolved['customRole'],
                'permissions' => $resolved['permissions'],
                'status' => $user->status,
                'createdAt' => $user->created_at ? $user->created_at->toIso8601String() : null,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $auth = $request->attributes->get('auth_user');
        $user = User::find($auth['id']);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $updateData = [];
        if ($request->has('name')) $updateData['name'] = $request->input('name');
        if ($request->has('phone')) $updateData['phone'] = $request->input('phone');
        if ($request->has('shippingArea')) $updateData['shipping_area'] = $request->input('shippingArea');
        if ($request->has('shippingAddress')) $updateData['shipping_address'] = $request->input('shippingAddress');

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'shippingArea' => $user->shipping_area,
                'shippingAddress' => $user->shipping_address,
            ],
        ]);
    }

    public function getOrderHistory(Request $request)
    {
        $auth = $request->attributes->get('auth_user');
        $orders = Order::where('user_id', $auth['id'])
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $orders->map(fn($o) => OrderService::formatOrder($o))->toArray();

        return response()->json(['success' => true, 'data' => $formatted]);
    }

    public function getAddresses(Request $request)
    {
        $auth = $request->attributes->get('auth_user');
        $addresses = UserAddress::where('user_id', $auth['id'])
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $addresses->map(fn($a) => [
            'id' => $a->id,
            'userId' => $a->user_id,
            'recipientName' => $a->recipient_name,
            'phone' => $a->phone,
            'alternativePhone' => $a->alternative_phone,
            'email' => $a->email,
            'country' => $a->country,
            'division' => $a->division,
            'district' => $a->district,
            'upazila' => $a->upazila,
            'area' => $a->area,
            'shippingArea' => $a->shipping_area,
            'address' => $a->address,
            'apartment' => $a->apartment,
            'postalCode' => $a->postal_code,
            'isDefault' => $a->is_default,
            'createdAt' => $a->created_at ? $a->created_at->toIso8601String() : null,
        ])->toArray();

        return response()->json(['success' => true, 'data' => $formatted]);
    }

    public function createAddress(Request $request)
    {
        $auth = $request->attributes->get('auth_user');
        $validated = $request->validate([
            'recipientName' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'shippingArea' => 'required|string|max:100',
            'address' => 'required|string',
        ]);

        $existing = UserAddress::where('user_id', $auth['id'])->count();
        if ($existing >= 5) {
            return response()->json(['success' => false, 'message' => 'Maximum 5 addresses allowed'], 400);
        }

        $address = UserAddress::create([
            'user_id' => $auth['id'],
            'recipient_name' => $validated['recipientName'],
            'phone' => $validated['phone'],
            'alternative_phone' => $request->input('alternativePhone'),
            'email' => $request->input('email'),
            'country' => $request->input('country'),
            'division' => $request->input('division'),
            'district' => $request->input('district'),
            'upazila' => $request->input('upazila'),
            'area' => $request->input('area'),
            'shipping_area' => $validated['shippingArea'],
            'address' => $validated['address'],
            'apartment' => $request->input('apartment'),
            'postal_code' => $request->input('postalCode'),
            'is_default' => $existing === 0 || $request->boolean('isDefault'),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $address->id,
                'recipientName' => $address->recipient_name,
                'phone' => $address->phone,
                'shippingArea' => $address->shipping_area,
                'address' => $address->address,
            ],
        ], 201);
    }

    public function updateAddress(Request $request, $id)
    {
        $auth = $request->attributes->get('auth_user');
        $address = UserAddress::where('id', $id)->where('user_id', $auth['id'])->first();
        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Address not found'], 404);
        }

        $address->update($request->all());

        return response()->json(['success' => true, 'data' => $address]);
    }

    public function deleteAddress(Request $request, $id)
    {
        $auth = $request->attributes->get('auth_user');
        $address = UserAddress::where('id', $id)->where('user_id', $auth['id'])->first();
        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Address not found'], 404);
        }

        $address->delete();

        return response()->json(['success' => true, 'data' => ['deleted' => true]]);
    }

    public function getAll()
    {
        $users = User::orderBy('created_at', 'desc')->get()->makeHidden(['password', 'reset_token_hash']);
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function remove($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $user->delete();
        return response()->json(['success' => true, 'data' => ['deleted' => true]]);
    }
}
