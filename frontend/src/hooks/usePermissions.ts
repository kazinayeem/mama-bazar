import { useMemo } from 'react'
import { useAppSelector } from '../store/hooks'

export const usePermissions = () => {
  const user = useAppSelector((state) => state.auth.user)

  const isSuperAdmin = useMemo(() => {
    if (!user) return false
    return (
      user.customRole === 'SUPER_ADMIN' ||
      user.role === 'admin' ||
      user.id === 240011 ||
      (user.permissions && user.permissions.includes('*'))
    )
  }, [user])

  const permissions = useMemo(() => {
    return user?.permissions || []
  }, [user])

  const hasPermission = useMemo(
    () => (permission: string): boolean => {
      if (!user) return false
      if (isSuperAdmin) return true
      if (permissions.includes('*')) return true
      return permissions.includes(permission)
    },
    [user, isSuperAdmin, permissions]
  )

  const hasAnyPermission = useMemo(
    () => (...perms: string[]): boolean => {
      if (!user) return false
      if (isSuperAdmin) return true
      if (permissions.includes('*')) return true
      return perms.some((p) => permissions.includes(p))
    },
    [user, isSuperAdmin, permissions]
  )

  const hasAllPermissions = useMemo(
    () => (...perms: string[]): boolean => {
      if (!user) return false
      if (isSuperAdmin) return true
      if (permissions.includes('*')) return true
      return perms.every((p) => permissions.includes(p))
    },
    [user, isSuperAdmin, permissions]
  )

  return {
    user,
    isSuperAdmin,
    customRole: user?.customRole || (user?.role === 'admin' ? 'SUPER_ADMIN' : 'STAFF'),
    permissions,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
  }
}
