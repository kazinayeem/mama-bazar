import React, { useState, useMemo } from 'react'
import {
  Users,
  UserPlus,
  Shield,
  ShieldAlert,
  Search,
  CheckCircle2,
  Edit2,
  Trash2,
  Activity,
  Filter,
  Check,
  ChevronDown,
  ChevronRight,
  Info,
} from 'lucide-react'
import {
  useGetAdminMembersQuery,
  useGetRolesAndPermissionsQuery,
  useCreateAdminMemberMutation,
  useUpdateAdminMemberMutation,
  useDeleteAdminMemberMutation,
  useGetAuditLogsQuery,
  parseError,
} from '../../store/services/adminProductsApi'
import { usePermissions } from '../../hooks/usePermissions'
import { PermissionGate } from '../../components/admin/PermissionGate'
import type { AdminMember } from '../../types/admin'

export const AdminMembersPage: React.FC = () => {
  const { data: members = [], isLoading: loadingMembers, refetch: refetchMembers } = useGetAdminMembersQuery()
  const { data: catalog } = useGetRolesAndPermissionsQuery()
  const { data: auditLogs = [], isLoading: loadingLogs } = useGetAuditLogsQuery({ limit: 100 })

  const [createMember, { isLoading: isCreating }] = useCreateAdminMemberMutation()
  const [updateMember, { isLoading: isUpdating }] = useUpdateAdminMemberMutation()
  const [deleteMember, { isLoading: isDeleting }] = useDeleteAdminMemberMutation()

  const { user: currentUser } = usePermissions()

  const [activeTab, setActiveTab] = useState<'members' | 'audit'>('members')
  const [searchQuery, setSearchQuery] = useState('')
  const [roleFilter, setRoleFilter] = useState('ALL')

  // Modal State
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [editingMember, setEditingMember] = useState<AdminMember | null>(null)
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    email: '',
    password: '',
    role: 'STAFF',
    status: 'active' as 'active' | 'inactive',
    permissions: [] as string[],
  })
  const [expandedModules, setExpandedModules] = useState<Record<string, boolean>>({})
  const [feedback, setFeedback] = useState<{ type: 'success' | 'error'; message: string } | null>(null)
  const [deleteConfirmId, setDeleteConfirmId] = useState<number | null>(null)

  // Roles and Permissions grouping
  const roles = catalog?.roles || []
  const permissions = catalog?.permissions || []

  const permissionsByModule = useMemo(() => {
    const map: Record<string, typeof permissions> = {}
    permissions.forEach((p) => {
      if (!map[p.module]) map[p.module] = []
      map[p.module].push(p)
    })
    return map
  }, [permissions])

  const filteredMembers = useMemo(() => {
    return members.filter((m) => {
      const matchSearch =
        m.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        m.phone.includes(searchQuery) ||
        (m.email && m.email.toLowerCase().includes(searchQuery.toLowerCase()))
      const matchRole = roleFilter === 'ALL' || m.customRole === roleFilter || m.role === roleFilter
      return matchSearch && matchRole
    })
  }, [members, searchQuery, roleFilter])

  const openCreateModal = () => {
    setEditingMember(null)
    const defaultRole = roles.find((r) => r.name === 'STAFF') || roles[0]
    setFormData({
      name: '',
      phone: '',
      email: '',
      password: '',
      role: defaultRole ? defaultRole.name : 'STAFF',
      status: 'active',
      permissions: defaultRole ? [...defaultRole.permissions] : [],
    })
    // Expand all permission modules by default
    const expanded: Record<string, boolean> = {}
    Object.keys(permissionsByModule).forEach((mod) => (expanded[mod] = true))
    setExpandedModules(expanded)
    setFeedback(null)
    setIsModalOpen(true)
  }

  const openEditModal = (member: AdminMember) => {
    setEditingMember(member)
    setFormData({
      name: member.name,
      phone: member.phone,
      email: member.email || '',
      password: '',
      role: member.customRole || (member.role === 'admin' ? 'SUPER_ADMIN' : 'STAFF'),
      status: member.status,
      permissions: [...member.permissions],
    })
    const expanded: Record<string, boolean> = {}
    Object.keys(permissionsByModule).forEach((mod) => (expanded[mod] = true))
    setExpandedModules(expanded)
    setFeedback(null)
    setIsModalOpen(true)
  }

  const handleRoleChange = (newRole: string) => {
    const roleObj = roles.find((r) => r.name === newRole)
    setFormData((prev) => ({
      ...prev,
      role: newRole,
      permissions: roleObj ? [...roleObj.permissions] : prev.permissions,
    }))
  }

  const togglePermission = (code: string) => {
    setFormData((prev) => {
      const exists = prev.permissions.includes(code)
      const newPerms = exists
        ? prev.permissions.filter((p) => p !== code)
        : [...prev.permissions, code]
      return { ...prev, permissions: newPerms, role: 'CUSTOM' }
    })
  }

  const toggleModulePermissions = (moduleName: string) => {
    const modulePerms = permissionsByModule[moduleName] || []
    const moduleCodes = modulePerms.map((p) => p.code)
    const allSelected = moduleCodes.every((c) => formData.permissions.includes(c))

    setFormData((prev) => {
      let newPerms: string[]
      if (allSelected) {
        newPerms = prev.permissions.filter((p) => !moduleCodes.includes(p))
      } else {
        newPerms = Array.from(new Set([...prev.permissions, ...moduleCodes]))
      }
      return { ...prev, permissions: newPerms, role: 'CUSTOM' }
    })
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setFeedback(null)

    try {
      if (editingMember) {
        const payload: any = {
          name: formData.name,
          phone: formData.phone,
          email: formData.email || null,
          role: formData.role,
          status: formData.status,
          permissions: formData.permissions,
        }
        if (formData.password.trim()) {
          payload.password = formData.password
        }
        await updateMember({ id: editingMember.id, payload }).unwrap()
        setFeedback({ type: 'success', message: 'Team member updated successfully.' })
      } else {
        if (!formData.password) {
          setFeedback({ type: 'error', message: 'Password is required for new team members.' })
          return
        }
        await createMember({
          name: formData.name,
          phone: formData.phone,
          email: formData.email || undefined,
          password: formData.password,
          role: formData.role,
          status: formData.status,
          permissions: formData.permissions,
        }).unwrap()
        setFeedback({ type: 'success', message: 'Team member created successfully.' })
      }

      setTimeout(() => {
        setIsModalOpen(false)
        refetchMembers()
      }, 600)
    } catch (err: any) {
      setFeedback({ type: 'error', message: parseError(err) || 'Operation failed. Please check inputs.' })
    }
  }

  const handleDelete = async (id: number) => {
    try {
      await deleteMember(id).unwrap()
      setDeleteConfirmId(null)
      refetchMembers()
    } catch (err: any) {
      alert(parseError(err) || 'Failed to delete member')
    }
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
            <Shield className="w-7 h-7 text-primary" />
            Security & Team Members
          </h1>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Manage admin team members, assign granular module permissions, and inspect security audit logs.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <PermissionGate permission="members.create">
            <button
              onClick={openCreateModal}
              className="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary/90 rounded-xl transition shadow-sm active:scale-95"
            >
              <UserPlus className="w-4 h-4" /> Add Team Member
            </button>
          </PermissionGate>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button
          onClick={() => setActiveTab('members')}
          className={`flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors ${
            activeTab === 'members'
              ? 'bg-primary/10 text-primary dark:bg-primary/20'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
          }`}
        >
          <Users className="w-4 h-4" /> Team Members ({members.length})
        </button>

        <button
          onClick={() => setActiveTab('audit')}
          className={`flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors ${
            activeTab === 'audit'
              ? 'bg-primary/10 text-primary dark:bg-primary/20'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
          }`}
        >
          <Activity className="w-4 h-4" /> Security Audit Log ({auditLogs.length})
        </button>
      </div>

      {/* TAB 1: MEMBERS */}
      {activeTab === 'members' && (
        <div className="space-y-4">
          {/* Filter Bar */}
          <div className="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <div className="relative w-full sm:w-80">
              <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="text"
                placeholder="Search by name, phone, email..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>

            <div className="flex items-center gap-2 w-full sm:w-auto">
              <Filter className="w-4 h-4 text-slate-400 shrink-0" />
              <select
                value={roleFilter}
                onChange={(e) => setRoleFilter(e.target.value)}
                className="text-sm bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="ALL">All Roles</option>
                {roles.map((r) => (
                  <option key={r.name} value={r.name}>
                    {r.displayName}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Members Table */}
          <div className="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                  <tr>
                    <th className="px-5 py-3.5">Member</th>
                    <th className="px-5 py-3.5">Contact</th>
                    <th className="px-5 py-3.5">Role</th>
                    <th className="px-5 py-3.5">Permissions</th>
                    <th className="px-5 py-3.5">Status</th>
                    <th className="px-5 py-3.5">Last Login</th>
                    <th className="px-5 py-3.5 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60 font-normal">
                  {loadingMembers ? (
                    <tr>
                      <td colSpan={7} className="px-5 py-8 text-center text-slate-400">
                        Loading team members...
                      </td>
                    </tr>
                  ) : filteredMembers.length === 0 ? (
                    <tr>
                      <td colSpan={7} className="px-5 py-8 text-center text-slate-400">
                        No team members found matching your search.
                      </td>
                    </tr>
                  ) : (
                    filteredMembers.map((member) => {
                      const isSuper =
                        member.customRole === 'SUPER_ADMIN' ||
                        member.role === 'admin' ||
                        member.id === 240011 ||
                        member.permissions.includes('*')

                      return (
                        <tr key={member.id} className="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                          <td className="px-5 py-4">
                            <div className="flex items-center gap-3">
                              <div className="w-9 h-9 rounded-full bg-primary/10 dark:bg-primary/20 text-primary flex items-center justify-center font-bold text-sm">
                                {member.name.charAt(0).toUpperCase()}
                              </div>
                              <div>
                                <p className="font-semibold text-slate-900 dark:text-white flex items-center gap-1.5">
                                  {member.name}
                                  {member.id === currentUser?.id && (
                                    <span className="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-1.5 py-0.5 rounded font-medium">
                                      You
                                    </span>
                                  )}
                                </p>
                                <p className="text-xs text-slate-500">ID #{member.id}</p>
                              </div>
                            </div>
                          </td>

                          <td className="px-5 py-4">
                            <p className="font-mono text-xs text-slate-800 dark:text-slate-200">{member.phone}</p>
                            {member.email && <p className="text-xs text-slate-500 truncate max-w-[180px]">{member.email}</p>}
                          </td>

                          <td className="px-5 py-4">
                            <span
                              className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${
                                isSuper
                                  ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 ring-1 ring-amber-300 dark:ring-amber-800'
                                  : member.customRole === 'ADMIN'
                                  ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300'
                                  : member.customRole === 'MANAGER'
                                  ? 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300'
                                  : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                              }`}
                            >
                              {isSuper ? 'Super Admin' : member.customRole || member.role.toUpperCase()}
                            </span>
                          </td>

                          <td className="px-5 py-4">
                            {isSuper ? (
                              <span className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                <CheckCircle2 className="w-3.5 h-3.5" /> Full Root Access (*)
                              </span>
                            ) : (
                              <span className="text-xs text-slate-600 dark:text-slate-400">
                                {member.permissions.length} module permissions
                              </span>
                            )}
                          </td>

                          <td className="px-5 py-4">
                            <span
                              className={`inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full ${
                                member.status === 'active'
                                  ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'
                                  : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400'
                              }`}
                            >
                              <span
                                className={`w-1.5 h-1.5 rounded-full ${
                                  member.status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'
                                }`}
                              />
                              {member.status === 'active' ? 'Active' : 'Inactive'}
                            </span>
                          </td>

                          <td className="px-5 py-4 text-xs text-slate-500">
                            {member.lastLoginAt ? (
                              new Date(member.lastLoginAt).toLocaleString()
                            ) : (
                              <span className="italic text-slate-400">Never logged in</span>
                            )}
                          </td>

                          <td className="px-5 py-4 text-right">
                            <div className="flex items-center justify-end gap-2">
                              <PermissionGate permission="members.update">
                                <button
                                  onClick={() => openEditModal(member)}
                                  className="p-1.5 text-slate-600 dark:text-slate-400 hover:text-primary hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition"
                                  title="Edit Member & Permissions"
                                >
                                  <Edit2 className="w-4 h-4" />
                                </button>
                              </PermissionGate>

                              <PermissionGate permission="members.delete">
                                {member.id !== currentUser?.id && (
                                  <button
                                    onClick={() => setDeleteConfirmId(member.id)}
                                    className="p-1.5 text-slate-600 dark:text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition"
                                    title="Delete Member"
                                  >
                                    <Trash2 className="w-4 h-4" />
                                  </button>
                                )}
                              </PermissionGate>
                            </div>
                          </td>
                        </tr>
                      )
                    })
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* TAB 2: AUDIT LOGS */}
      {activeTab === 'audit' && (
        <div className="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div className="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 className="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
              <Activity className="w-4 h-4 text-primary" /> Recent Security & Management Events
            </h3>
            <span className="text-xs text-slate-500">Showing last 100 events</span>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                <tr>
                  <th className="px-5 py-3.5">Timestamp</th>
                  <th className="px-5 py-3.5">Actor</th>
                  <th className="px-5 py-3.5">Action</th>
                  <th className="px-5 py-3.5">Target</th>
                  <th className="px-5 py-3.5">Status</th>
                  <th className="px-5 py-3.5">IP Address</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60 font-normal">
                {loadingLogs ? (
                  <tr>
                    <td colSpan={6} className="px-5 py-8 text-center text-slate-400">
                      Loading audit logs...
                    </td>
                  </tr>
                ) : auditLogs.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-5 py-8 text-center text-slate-400">
                      No security audit events recorded yet.
                    </td>
                  </tr>
                ) : (
                  auditLogs.map((log) => (
                    <tr key={log.id} className="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                      <td className="px-5 py-3 text-xs text-slate-500 whitespace-nowrap">
                        {new Date(log.createdAt).toLocaleString()}
                      </td>
                      <td className="px-5 py-3">
                        <p className="font-semibold text-xs text-slate-900 dark:text-white">{log.actorName}</p>
                        {log.actorEmail && <p className="text-[11px] text-slate-400">{log.actorEmail}</p>}
                      </td>
                      <td className="px-5 py-3">
                        <span className="font-mono text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-2 py-0.5 rounded">
                          {log.action}
                        </span>
                      </td>
                      <td className="px-5 py-3 text-xs text-slate-600 dark:text-slate-400">
                        {log.targetType} {log.targetId ? `#${log.targetId}` : ''}
                      </td>
                      <td className="px-5 py-3">
                        <span
                          className={`text-xs font-semibold ${
                            log.status === 'success' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                          }`}
                        >
                          {log.status.toUpperCase()}
                        </span>
                      </td>
                      <td className="px-5 py-3 font-mono text-xs text-slate-400">
                        {log.ipAddress || '—'}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* MODAL: ADD / EDIT MEMBER */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
          <div className="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden my-8 animate-in fade-in zoom-in-95 duration-200">
            <div className="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
              <h2 className="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Shield className="w-5 h-5 text-primary" />
                {editingMember ? `Edit Member: ${editingMember.name}` : 'Add New Team Member'}
              </h2>
              <button
                onClick={() => setIsModalOpen(false)}
                className="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
              {feedback && (
                <div
                  className={`p-3.5 rounded-xl text-sm font-medium flex items-center gap-2 ${
                    feedback.type === 'success'
                      ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                      : 'bg-rose-50 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800'
                  }`}
                >
                  {feedback.type === 'success' ? <CheckCircle2 className="w-4 h-4" /> : <ShieldAlert className="w-4 h-4" />}
                  {feedback.message}
                </div>
              )}

              {/* Basic Fields */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                    Full Name *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    placeholder="e.g. John Doe"
                    className="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                    Phone Number (Login identifier) *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    placeholder="e.g. 017XXXXXXXX"
                    className="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none font-mono"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                    Email Address (Optional)
                  </label>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    placeholder="e.g. member@company.com"
                    className="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                    {editingMember ? 'New Password (Leave blank to keep existing)' : 'Password *'}
                  </label>
                  <input
                    type="password"
                    required={!editingMember}
                    value={formData.password}
                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                    placeholder="••••••••"
                    className="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none"
                  />
                </div>
              </div>

              {/* Role & Status */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100 dark:border-slate-800">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                    Assign System Role
                  </label>
                  <select
                    value={formData.role}
                    onChange={(e) => handleRoleChange(e.target.value)}
                    className="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none font-semibold"
                  >
                    {roles.map((r) => (
                      <option key={r.name} value={r.name}>
                        {r.displayName} {r.name === 'SUPER_ADMIN' ? '👑 (Full Root)' : ''}
                      </option>
                    ))}
                    <option value="CUSTOM">Custom Tailored Permissions</option>
                  </select>
                  <p className="text-[11px] text-slate-500 mt-1">
                    {roles.find((r) => r.name === formData.role)?.description || 'Custom tailored permission set'}
                  </p>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                    Account Status
                  </label>
                  <select
                    value={formData.status}
                    onChange={(e) => setFormData({ ...formData, status: e.target.value as 'active' | 'inactive' })}
                    className="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none"
                  >
                    <option value="active">Active (Can Login & Perform Actions)</option>
                    <option value="inactive">Inactive (Disabled / Blocked Access)</option>
                  </select>
                </div>
              </div>

              {/* Granular Permissions Section */}
              <div className="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-3">
                <div className="flex items-center justify-between">
                  <div>
                    <h3 className="text-sm font-bold text-slate-900 dark:text-white">
                      Granular Module Permissions
                    </h3>
                    <p className="text-xs text-slate-500">
                      Explicitly control which pages, features, and actions this member can access.
                    </p>
                  </div>
                  <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-primary/10 text-primary">
                    {formData.role === 'SUPER_ADMIN' ? 'All (Root)' : `${formData.permissions.length} Selected`}
                  </span>
                </div>

                {formData.role === 'SUPER_ADMIN' ? (
                  <div className="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 flex items-start gap-3">
                    <Info className="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div className="text-xs text-amber-800 dark:text-amber-300">
                      <strong>Super Admin Role:</strong> Granted full unrestricted access (*) to all present and future modules, database backups, settings, and member administration.
                    </div>
                  </div>
                ) : (
                  <div className="space-y-2 border border-slate-200 dark:border-slate-800 rounded-xl p-3 bg-slate-50/50 dark:bg-slate-800/30">
                    {Object.entries(permissionsByModule).map(([moduleName, modulePerms]) => {
                      const moduleCodes = modulePerms.map((p) => p.code)
                      const allSelected = moduleCodes.every((c) => formData.permissions.includes(c))
                      const someSelected = moduleCodes.some((c) => formData.permissions.includes(c))
                      const isOpen = expandedModules[moduleName] ?? true

                      return (
                        <div
                          key={moduleName}
                          className="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden"
                        >
                          <div className="px-4 py-2.5 bg-slate-50 dark:bg-slate-800/60 flex items-center justify-between">
                            <div className="flex items-center gap-2.5">
                              <button
                                type="button"
                                onClick={() =>
                                  setExpandedModules((prev) => ({ ...prev, [moduleName]: !prev[moduleName] }))
                                }
                                className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                              >
                                {isOpen ? <ChevronDown className="w-4 h-4" /> : <ChevronRight className="w-4 h-4" />}
                              </button>
                              <button
                                type="button"
                                onClick={() => toggleModulePermissions(moduleName)}
                                className="flex items-center gap-2 text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider hover:text-primary"
                              >
                                <span
                                  className={`w-4 h-4 rounded border flex items-center justify-center text-[10px] ${
                                    allSelected
                                      ? 'bg-primary border-primary text-white'
                                      : someSelected
                                      ? 'bg-primary/20 border-primary text-primary'
                                      : 'border-slate-300 dark:border-slate-600'
                                  }`}
                                >
                                  {allSelected && <Check className="w-3 h-3 stroke-[3]" />}
                                  {!allSelected && someSelected && '–'}
                                </span>
                                {moduleName}
                              </button>
                            </div>
                            <span className="text-[11px] text-slate-400">
                              {moduleCodes.filter((c) => formData.permissions.includes(c)).length} / {moduleCodes.length}
                            </span>
                          </div>

                          {isOpen && (
                            <div className="p-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                              {modulePerms.map((perm) => {
                                const isChecked = formData.permissions.includes(perm.code)
                                return (
                                  <label
                                    key={perm.code}
                                    className={`flex items-start gap-2.5 p-2 rounded-lg cursor-pointer transition border ${
                                      isChecked
                                        ? 'bg-primary/5 dark:bg-primary/10 border-primary/30 text-slate-900 dark:text-white'
                                        : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 border-transparent text-slate-600 dark:text-slate-400'
                                    }`}
                                  >
                                    <input
                                      type="checkbox"
                                      checked={isChecked}
                                      onChange={() => togglePermission(perm.code)}
                                      className="mt-0.5 rounded border-slate-300 text-primary focus:ring-primary"
                                    />
                                    <div className="text-xs">
                                      <p className="font-semibold leading-tight">{perm.label}</p>
                                      <p className="text-[10px] text-slate-400 font-mono mt-0.5">{perm.code}</p>
                                    </div>
                                  </label>
                                )
                              })}
                            </div>
                          )}
                        </div>
                      )
                    })}
                  </div>
                )}
              </div>

              {/* Action Buttons */}
              <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isCreating || isUpdating}
                  className="px-5 py-2 text-sm font-semibold text-white bg-primary hover:bg-primary/90 disabled:opacity-50 rounded-xl transition shadow-sm"
                >
                  {isCreating || isUpdating ? 'Saving...' : editingMember ? 'Save Changes' : 'Create Member'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: DELETE CONFIRMATION */}
      {deleteConfirmId !== null && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div className="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl text-center space-y-4 animate-in fade-in zoom-in-95">
            <div className="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
              <Trash2 className="w-6 h-6" />
            </div>
            <h3 className="text-lg font-bold text-slate-900 dark:text-white">Delete Team Member?</h3>
            <p className="text-sm text-slate-500 dark:text-slate-400">
              Are you sure you want to delete this team member? They will immediately lose all access to the admin dashboard and APIs.
            </p>
            <div className="flex items-center justify-center gap-3 pt-2">
              <button
                onClick={() => setDeleteConfirmId(null)}
                className="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
              >
                Cancel
              </button>
              <button
                onClick={() => handleDelete(deleteConfirmId)}
                disabled={isDeleting}
                className="px-5 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 rounded-xl transition shadow-sm"
              >
                {isDeleting ? 'Deleting...' : 'Yes, Delete'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default AdminMembersPage
