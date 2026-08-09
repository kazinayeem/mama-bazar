import { Navigate, useLocation } from 'react-router-dom'
import type { ReactNode } from 'react'
import { useAppSelector } from '../../store/hooks'
import type { UserRole } from '../../types'

type ProtectedRouteProps = {
  children: ReactNode
  allowedRoles?: UserRole[]
}

const ProtectedRoute = ({ children, allowedRoles }: ProtectedRouteProps) => {
  const location = useLocation()
  const { token, user } = useAppSelector((state) => state.auth)

  if (!token || !user) {
    return <Navigate replace state={{ from: location.pathname }} to="/auth/login" />
  }

  if (allowedRoles?.length && !allowedRoles.includes(user.role)) {
    return <Navigate replace to="/dashboard" />
  }

  return children
}

export default ProtectedRoute
