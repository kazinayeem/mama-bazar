import React from 'react'
import { usePermissions } from '../../hooks/usePermissions'

interface PermissionGateProps {
  permission?: string
  anyOf?: string[]
  allOf?: string[]
  children: React.ReactNode
  fallback?: React.ReactNode
}

export const PermissionGate: React.FC<PermissionGateProps> = ({
  permission,
  anyOf,
  allOf,
  children,
  fallback = null,
}) => {
  const { hasPermission, hasAnyPermission, hasAllPermissions } = usePermissions()

  if (permission && !hasPermission(permission)) {
    return <>{fallback}</>
  }

  if (anyOf && anyOf.length > 0 && !hasAnyPermission(...anyOf)) {
    return <>{fallback}</>
  }

  if (allOf && allOf.length > 0 && !hasAllPermissions(...allOf)) {
    return <>{fallback}</>
  }

  return <>{children}</>
}
