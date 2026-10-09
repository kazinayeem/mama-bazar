<?php

namespace App\Models;

use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\EnsureAdminPermission;
use App\Services\RbacService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'password',
        'phone',
        'email',
        'email_verified_at',
        'email_verification_required',
        'marketing_opt_in',
        'marketing_opt_in_at',
        'marketing_consent_source',
        'unsubscribe_token',
        'shipping_area',
        'shipping_address',
        'role',
        'custom_role',
        'permission_mode',
        'permissions_json',
        'sidebar_access_json',
        'status',
        'last_login_at',
        'last_login_ip',
        'last_login_location',
        'must_change_password',
        'invitation_token_hash',
        'invitation_sent_at',
        'invitation_expires_at',
        'invitation_accepted_at',
        'reset_token_hash',
        'reset_token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reset_token_hash',
        'invitation_token_hash',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verification_required' => 'boolean',
        'marketing_opt_in' => 'boolean',
        'marketing_opt_in_at' => 'datetime',
        'permissions_json' => 'array',
        'sidebar_access_json' => 'array',
        'last_login_at' => 'datetime',
        'must_change_password' => 'boolean',
        'invitation_sent_at' => 'datetime',
        'invitation_expires_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function loginHistories(): HasMany
    {
        return $this->hasMany(MemberLoginHistory::class, 'user_id')->orderByDesc('login_at');
    }

    public function isInvitationPending(): bool
    {
        return ! empty($this->invitation_token_hash)
            && is_null($this->invitation_accepted_at)
            && ($this->invitation_expires_at === null || $this->invitation_expires_at->isFuture());
    }

    public function isInvitationExpired(): bool
    {
        return ! empty($this->invitation_token_hash)
            && is_null($this->invitation_accepted_at)
            && $this->invitation_expires_at !== null
            && $this->invitation_expires_at->isPast();
    }

    public function getOrCreateUnsubscribeToken(): string
    {
        if (! $this->unsubscribe_token) {
            $this->unsubscribe_token = Str::random(48);
            $this->save();
        }

        return $this->unsubscribe_token;
    }

    public function isEmailVerified(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Accounts created before email verification existed are grandfathered
     * and never blocked; only flagged accounts must verify first.
     */
    public function mustVerifyEmail(): bool
    {
        return (bool) $this->email_verification_required
            && ! empty($this->email)
            && ! $this->isEmailVerified();
    }

    public function isStaff(): bool
    {
        return EnsureAdminAccess::isAdminLike($this);
    }

    public function isSuperAdmin(): bool
    {
        return RbacService::isSuperAdmin($this);
    }

    public function canAdmin(string|array $permissions): bool
    {
        return EnsureAdminPermission::allows(
            $this,
            is_array($permissions) ? $permissions : explode('|', $permissions)
        );
    }

    public function hasPermission(string $permission): bool
    {
        return $this->canAdmin($permission);
    }

    public function hasSidebarAccess(string $routeOrLabel): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (empty($this->sidebar_access_json)) {
            return true;
        }

        $allowed = is_array($this->sidebar_access_json)
            ? $this->sidebar_access_json
            : (json_decode($this->sidebar_access_json, true) ?: []);

        return in_array($routeOrLabel, $allowed, true);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class, 'user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AdminAuditLog::class, 'actor_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'member_id');
    }

    public function customerNotes()
    {
        return $this->hasMany(CustomerNote::class, 'customer_id')->orderByDesc('created_at');
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class, 'user_id')->orderByDesc('created_at');
    }
}
