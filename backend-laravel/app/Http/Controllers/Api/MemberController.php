<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\AuditService;
use App\Services\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function getRolesAndPermissions(): JsonResponse
    {
        $roles = [];
        foreach (RbacService::ROLE_PRESETS as $name => $preset) {
            $roles[] = [
                'name' => $name,
                'displayName' => $preset['displayName'],
                'description' => $preset['description'],
                'permissions' => $preset['permissions'],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'permissions' => RbacService::ALL_PERMISSIONS,
                'roles' => $roles,
            ],
        ]);
    }

    public function listMembers(): JsonResponse
    {
        $members = User::where('role', '!=', 'user')
            ->orWhereNotNull('custom_role')
            ->orderBy('created_at', 'desc')
            ->get();

        $result = $members->map(function ($m) {
            $resolved = RbacService::resolveUserPermissions($m->id, $m->role, $m->custom_role);
            return [
                'id' => $m->id,
                'name' => $m->name,
                'phone' => $m->phone,
                'email' => $m->email,
                'role' => $m->role,
                'customRole' => $resolved['customRole'],
                'status' => $m->status,
                'permissions' => $resolved['permissions'],
                'lastLoginAt' => $m->last_login_at,
                'createdAt' => $m->created_at,
            ];
        });

        return response()->json(['success' => true, 'data' => $result]);
    }

    private function countActiveSuperAdmins(?int $excludeId = null): int
    {
        $query = User::where(function ($q) {
            $q->where('custom_role', 'SUPER_ADMIN')
                ->orWhere('role', 'admin')
                ->orWhere('id', 240011);
        })->where('status', 'active');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->count();
    }

    public function createMember(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'nullable|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'status' => 'nullable|in:active,inactive',
            'permissions' => 'nullable|array',
        ]);

        $actor = $request->user();
        $normalizedRole = strtoupper($validated['role']);
        $legacyRole = in_array($normalizedRole, ['SUPER_ADMIN', 'ADMIN']) ? 'admin' : 'manager';
        $permissionsJson = !empty($validated['permissions']) ? json_encode($validated['permissions']) : null;

        $member = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $legacyRole,
            'custom_role' => $normalizedRole,
            'permissions_json' => $permissionsJson,
            'status' => $validated['status'] ?? 'active',
        ]);

        if (!empty($validated['permissions'])) {
            foreach ($validated['permissions'] as $perm) {
                UserPermission::create([
                    'user_id' => $member->id,
                    'permission_code' => $perm,
                    'granted' => true,
                ]);
            }
        }

        RbacService::invalidateUserPermissionCache($member->id);

        AuditService::log([
            'actorId' => $actor?->id,
            'actorName' => $actor?->name ?? 'Super Admin',
            'actorEmail' => $actor?->email,
            'action' => 'MEMBER_CREATED',
            'targetType' => 'User',
            'targetId' => (string) $member->id,
            'details' => ['name' => $member->name, 'phone' => $member->phone, 'role' => $normalizedRole],
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
        ]);

        $resolved = RbacService::resolveUserPermissions($member->id, $legacyRole, $normalizedRole);

        return response()->json([
            'success' => true,
            'message' => 'Member created successfully',
            'data' => [
                'id' => $member->id,
                'name' => $member->name,
                'phone' => $member->phone,
                'email' => $member->email,
                'role' => $legacyRole,
                'customRole' => $resolved['customRole'],
                'status' => $member->status,
                'permissions' => $resolved['permissions'],
            ],
        ], 201);
    }

    public function updateMember(Request $request, int $id): JsonResponse
    {
        $member = User::find($id);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|max:20|unique:users,phone,' . $id,
            'email' => 'nullable|email|max:100|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|string',
            'status' => 'sometimes|in:active,inactive',
            'permissions' => 'nullable|array',
        ]);

        $actor = $request->user();
        $isSuperAdmin = ($member->custom_role === 'SUPER_ADMIN' || $member->role === 'admin' || $member->id === 240011);

        // Safety check: Prevent deactivating or demoting the last Super Admin
        if ($isSuperAdmin) {
            $isDemoting = isset($validated['role']) && strtoupper($validated['role']) !== 'SUPER_ADMIN';
            $isDeactivating = isset($validated['status']) && $validated['status'] === 'inactive';
            if ($isDemoting || $isDeactivating) {
                $remaining = $this->countActiveSuperAdmins($id);
                if ($remaining === 0) {
                    return response()->json(['success' => false, 'message' => 'Action blocked: Cannot deactivate or demote the only active Super Admin'], 400);
                }
            }
        }

        $updateData = [];
        if ($request->has('name')) $updateData['name'] = $validated['name'];
        if ($request->has('phone')) $updateData['phone'] = $validated['phone'];
        if ($request->has('email')) $updateData['email'] = $validated['email'];
        if ($request->has('status')) $updateData['status'] = $validated['status'];

        if ($request->has('role')) {
            $normalizedRole = strtoupper($validated['role']);
            $updateData['custom_role'] = $normalizedRole;
            $updateData['role'] = in_array($normalizedRole, ['SUPER_ADMIN', 'ADMIN']) ? 'admin' : 'manager';
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        if ($request->has('permissions')) {
            $updateData['permissions_json'] = json_encode($validated['permissions'] ?? []);

            UserPermission::where('user_id', $id)->delete();
            if (!empty($validated['permissions'])) {
                foreach ($validated['permissions'] as $perm) {
                    UserPermission::create([
                        'user_id' => $id,
                        'permission_code' => $perm,
                        'granted' => true,
                    ]);
                }
            }
        }

        $member->update($updateData);
        RbacService::invalidateUserPermissionCache($id);

        AuditService::log([
            'actorId' => $actor?->id,
            'actorName' => $actor?->name ?? 'Super Admin',
            'actorEmail' => $actor?->email,
            'action' => 'MEMBER_UPDATED',
            'targetType' => 'User',
            'targetId' => (string) $id,
            'details' => [
                'name' => $validated['name'] ?? $member->name,
                'role' => $validated['role'] ?? $member->custom_role,
                'status' => $validated['status'] ?? $member->status,
            ],
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
        ]);

        $fresh = $member->fresh();
        $resolved = RbacService::resolveUserPermissions($id, $fresh->role, $fresh->custom_role);

        return response()->json([
            'success' => true,
            'message' => 'Member updated successfully',
            'data' => [
                'id' => $fresh->id,
                'name' => $fresh->name,
                'phone' => $fresh->phone,
                'email' => $fresh->email,
                'role' => $fresh->role,
                'customRole' => $resolved['customRole'],
                'status' => $fresh->status,
                'permissions' => $resolved['permissions'],
                'lastLoginAt' => $fresh->last_login_at,
                'createdAt' => $fresh->created_at,
            ],
        ]);
    }

    public function deleteMember(Request $request, int $id): JsonResponse
    {
        $member = User::find($id);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member not found'], 404);
        }

        $actor = $request->user();
        $isSuperAdmin = ($member->custom_role === 'SUPER_ADMIN' || $member->role === 'admin' || $member->id === 240011);

        if ($isSuperAdmin) {
            $remaining = $this->countActiveSuperAdmins($id);
            if ($remaining === 0) {
                return response()->json(['success' => false, 'message' => 'Action blocked: Cannot delete the only active Super Admin'], 400);
            }
        }

        if ($actor && $actor->id === $id) {
            return response()->json(['success' => false, 'message' => 'Action blocked: You cannot delete your own active account'], 400);
        }

        UserPermission::where('user_id', $id)->delete();
        $member->delete();
        RbacService::invalidateUserPermissionCache($id);

        AuditService::log([
            'actorId' => $actor?->id,
            'actorName' => $actor?->name ?? 'Super Admin',
            'actorEmail' => $actor?->email,
            'action' => 'MEMBER_DELETED',
            'targetType' => 'User',
            'targetId' => (string) $id,
            'details' => ['name' => $member->name, 'phone' => $member->phone],
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true, 'message' => 'Member deleted successfully', 'data' => ['success' => true]]);
    }

    public function listAuditLogs(Request $request): JsonResponse
    {
        $limit = min((int) ($request->query('limit') ?: 100), 500);
        $logs = AuditService::getLogs($limit);
        return response()->json(['success' => true, 'data' => $logs]);
    }
}
