<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLoggerService;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    /**
     * Display the admin password change form.
     */
    public function showPasswordForm(Request $request): View
    {
        return view('admin.profile.change-password', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the authenticated administrator's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Please enter your current password.',
            'password.required' => 'Please enter a new password.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The new password must be at least 8 characters long.',
        ]);

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors([
                'current_password' => 'The provided current password is incorrect.',
            ])->withInput();
        }

        if (Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors([
                'password' => 'The new password cannot be the same as your current password.',
            ])->withInput();
        }

        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'must_change_password' => false,
        ])->save();

        try {
            Auth::logoutOtherDevices((string) $request->input('password'));
        } catch (\Throwable $e) {
            // Silently continue if session driver does not support device invalidation
        }

        Auth::login($user);

        AuditService::log([
            'actorId' => $user->id,
            'actorName' => $user->name,
            'actorEmail' => $user->email,
            'action' => 'admin.password_changed',
            'targetType' => 'user',
            'targetId' => (string) $user->id,
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'status' => 'success',
            'details' => ['reason' => 'Admin self-service password update'],
        ]);

        ActivityLoggerService::logSecurity(
            'admin.password_changed',
            "Administrator {$user->name} changed their password.",
            [
                'actor' => $user,
                'source' => 'admin_panel',
            ]
        );

        return redirect()->route('admin.profile.password')
            ->with('success', 'Password changed successfully.');
    }
}
