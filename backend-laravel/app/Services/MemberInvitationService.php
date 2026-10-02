<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberInvitationService
{
    public static function expiryHours(): int
    {
        return max(1, (int) config('email_system.invitation_expires_hours', 48));
    }

    /**
     * Create an invitation token and dispatch the invitation email.
     *
     * @return array{success: bool, token?: string, url?: string, message: string}
     */
    public static function createInvitation(User $member, ?User $actor = null): array
    {
        if (empty($member->email)) {
            return ['success' => false, 'message' => 'Team member must have an email address to receive an invitation.'];
        }

        $token = Str::random(64);
        $hours = self::expiryHours();

        $member->forceFill([
            'invitation_token_hash' => hash('sha256', $token),
            'invitation_sent_at' => now(),
            'invitation_expires_at' => now()->addHours($hours),
            'invitation_accepted_at' => null,
            'must_change_password' => true,
        ])->save();

        $setupUrl = route('admin.setup-password', ['token' => $token]);

        $roleLabel = match ($member->role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'manager' => 'Manager',
            'editor' => 'Editor',
            'staff' => 'Staff',
            default => $member->custom_role ?: ucfirst($member->role),
        };

        // Dispatch invitation email
        EmailDispatcherService::sendTemplate(
            'member_invitation',
            $member->email,
            $member->name,
            [
                'member_name' => $member->name,
                'member_email' => $member->email,
                'member_role' => $roleLabel,
                'setup_link' => $setupUrl,
                'expires_hours' => (string) $hours,
            ],
            'auth',
            [
                'user_id' => $member->id,
                'redact' => [$token, $setupUrl],
            ]
        );

        AuditService::log([
            'actorId' => $actor?->id,
            'actorName' => $actor ? $actor->name : 'System',
            'actorEmail' => $actor?->email,
            'action' => 'member.invitation_sent',
            'targetType' => 'member',
            'targetId' => (string) $member->id,
            'status' => 'success',
            'details' => [
                'recipient' => $member->email,
                'expires_at' => $member->invitation_expires_at->toIso8601String(),
            ],
        ]);

        return [
            'success' => true,
            'token' => $token,
            'url' => $setupUrl,
            'message' => 'Invitation sent to '.$member->email,
        ];
    }

    public static function verifyToken(string $token): ?User
    {
        $cleanToken = trim($token);
        if (strlen($cleanToken) !== 64) {
            return null;
        }

        $hash = hash('sha256', $cleanToken);

        $user = User::where('invitation_token_hash', $hash)->first();
        if (! $user) {
            return null;
        }

        if ($user->invitation_accepted_at !== null) {
            return null;
        }

        if ($user->invitation_expires_at !== null && $user->invitation_expires_at->isPast()) {
            return null;
        }

        return $user;
    }

    public static function acceptInvitation(string $token, string $newPassword): array
    {
        $user = self::verifyToken($token);
        if (! $user) {
            return [
                'success' => false,
                'message' => 'This account setup link is invalid or has expired. Please ask your administrator to send a new invitation.',
            ];
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'invitation_accepted_at' => now(),
            'invitation_token_hash' => null,
            'must_change_password' => false,
            'status' => 'active',
            'email_verified_at' => $user->email_verified_at ?: now(),
            'remember_token' => Str::random(60),
        ])->save();

        AuditService::log([
            'actorId' => $user->id,
            'actorName' => $user->name,
            'actorEmail' => $user->email,
            'action' => 'member.invitation_accepted',
            'targetType' => 'member',
            'targetId' => (string) $user->id,
            'status' => 'success',
            'details' => [
                'accepted_at' => now()->toIso8601String(),
            ],
        ]);

        AuditService::log([
            'actorId' => $user->id,
            'actorName' => $user->name,
            'actorEmail' => $user->email,
            'action' => 'member.password_setup_completed',
            'targetType' => 'member',
            'targetId' => (string) $user->id,
            'status' => 'success',
        ]);

        return [
            'success' => true,
            'user' => $user,
            'message' => 'Your password has been set up successfully. You can now log in to the admin panel.',
        ];
    }
}
