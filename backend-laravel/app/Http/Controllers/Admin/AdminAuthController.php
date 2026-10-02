<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'manager', 'editor', 'staff', 'super_admin'])) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->input('login');
        $password = $request->input('password');

        // Allow login via email OR phone
        $user = User::query()
            ->where(function ($query) use ($login) {
                $query->where('email', $login)
                    ->orWhere('phone', $login);
            })
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            LoginTrackingService::recordFailure($request, $login, 'Invalid credentials provided.');

            throw ValidationException::withMessages([
                'login' => ['Invalid credentials provided.'],
            ]);
        }

        // Authorization check: User must be an admin, manager, editor, staff, or have custom_role
        $allowedRoles = ['admin', 'manager', 'editor', 'staff', 'super_admin'];
        if (!in_array($user->role, $allowedRoles) && empty($user->custom_role)) {
            LoginTrackingService::recordFailure($request, $login, 'Access denied: Insufficient privileges.');

            throw ValidationException::withMessages([
                'login' => ['Access denied: You do not have administrative privileges.'],
            ]);
        }

        if ($user->status !== 'active') {
            LoginTrackingService::recordFailure($request, $login, 'Admin account inactive.');

            throw ValidationException::withMessages([
                'login' => ['Your admin account is inactive.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        LoginTrackingService::recordSuccess($user, $request);

        if ($user->must_change_password) {
            return redirect()->route('admin.password.change')
                ->with('info', 'Please set a permanent password before accessing the admin dashboard.');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
