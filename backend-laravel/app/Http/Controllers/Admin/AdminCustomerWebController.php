<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminCustomerWebController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'user')->withCount('orders');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    public function toggleStatus($id)
    {
        $customer = User::where('role', 'user')->findOrFail($id);
        $customer->status = ($customer->status === 'active') ? 'inactive' : 'active';
        $customer->save();

        return back()->with('success', 'Customer status updated.');
    }
}
