<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\MemberInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminInvitationController extends Controller
{
    /**
     * Show the account setup / password creation page from an invitation link.
     */
    public function showSetup(string $token)
    {
        $member = MemberInvitationService::verifyToken($token);

        return view('admin.auth.setup-password', [
            'token' => $token,
            'member' => $member,
            'invalid' => $member === null,
        ]);
    }

    /**
     * Process password establishment for the invited team member.
     */
    public function processSetup(Request $request, string $token)
    {
        $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $result = MemberInvitationService::acceptInvitation($token, (string) $request->input('password'));

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('admin.login')
            ->with('success', 'Your administrator password has been created successfully. Please sign in to continue.');
    }

    /**
     * Show forced password change page for members whose accounts require a password reset.
     */
    public function showChangePassword()
    {
        return view('admin.auth.change-password');
    }

    /**
     * Process forced password change.
     */
    public function processChangePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('admin.login');
        }

        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'must_change_password' => false,
        ])->save();

        AuditService::log([
            'actorId' => $user->id,
            'actorName' => $user->name,
            'actorEmail' => $user->email,
            'action' => 'member.password_changed',
            'targetType' => 'member',
            'targetId' => (string) $user->id,
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'status' => 'success',
            'details' => ['reason' => 'Mandatory password change completed'],
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Your password has been updated successfully.');
    }
}
