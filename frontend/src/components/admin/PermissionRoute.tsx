import React from 'react'
import { Link } from 'react-router-dom'
import { ShieldAlert, ArrowLeft, Home } from 'lucide-react'
import { usePermissions } from '../../hooks/usePermissions'

interface PermissionRouteProps {
  requiredPermission?: string
  requiredAny?: string[]
  children: React.ReactNode
}

export const PermissionRoute: React.FC<PermissionRouteProps> = ({
  requiredPermission,
  requiredAny,
  children,
}) => {
  const { hasPermission, hasAnyPermission, customRole } = usePermissions()

  const isAuthorized =
    (!requiredPermission || hasPermission(requiredPermission)) &&
    (!requiredAny || requiredAny.length === 0 || hasAnyPermission(...requiredAny))

  if (!isAuthorized) {
    return (
      <div className="min-h-[60vh] flex flex-col items-center justify-center p-6 text-center">
        <div className="w-16 h-16 rounded-2xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-4 shadow-inner ring-1 ring-rose-200 dark:ring-rose-900/50">
          <ShieldAlert className="w-8 h-8" />
        </div>
        <h2 className="text-xl font-bold text-slate-900 dark:text-white mb-2">Access Denied</h2>
        <p className="text-sm text-slate-500 dark:text-slate-400 max-w-md mb-6">
          Your account role (<span className="font-semibold text-slate-700 dark:text-slate-300">{customRole}</span>) does not have permission to view or manage this section. Contact your Super Admin to request access.
        </p>
        <div className="flex items-center gap-3">
          <button
            onClick={() => window.history.back()}
            className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
          >
            <ArrowLeft className="w-4 h-4" /> Go Back
          </button>
          <Link
            to="/admin/dashboard"
            className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-xl transition-colors shadow-sm"
          >
            <Home className="w-4 h-4" /> Dashboard
          </Link>
        </div>
      </div>
    )
  }

  return <>{children}</>
}
