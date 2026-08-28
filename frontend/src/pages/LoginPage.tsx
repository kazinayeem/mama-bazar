import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Eye, EyeOff, Lock, User, AlertCircle, Loader2 } from 'lucide-react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { SEO } from '../components/common/SEO'
import { clearAuthError, loginAsDev, loginUser } from '../store/slices/authSlice'
import { useAppDispatch, useAppSelector } from '../store/hooks'

const LoginPage = () => {
  const dispatch = useAppDispatch()
  const navigate = useNavigate()
  const location = useLocation()
  const { loading, error, user } = useAppSelector((state) => state.auth)

  const [phone, setPhone] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)

  // Get the redirect path from location state or URL params
  const redirectPath = useMemo(() => {
    // Check location state first (used when navigating from checkout)
    const stateFrom = (location.state as { from?: string } | null)?.from
    if (stateFrom) return stateFrom

    // Check URL search params (used for direct links)
    const searchParams = new URLSearchParams(location.search)
    const redirectTo = searchParams.get('redirect')
    if (redirectTo) return redirectTo

    return '/dashboard'
  }, [location.state, location.search])

  useEffect(() => {
    if (!user) return
    if (user.role === 'admin' || user.role === 'manager') {
      navigate('/admin/dashboard', { replace: true })
      return
    }
    navigate(redirectPath, { replace: true })
  }, [navigate, user, redirectPath])

  useEffect(() => {
    return () => {
      dispatch(clearAuthError())
    }
  }, [dispatch])

  const onSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (loading) return
    dispatch(loginUser({ phone: phone.trim(), password }))
  }

  const onDevLogin = (role: 'SUPER_ADMIN' | 'USER') => {
    if (loading) return
    dispatch(loginAsDev(role))
  }

  return (
    <>
      <SEO
        title="Sign In | Mama Bazar"
        description="Sign in to your Mama Bazar account to manage orders, wishlist, and profile."
        url="/auth/login"
      />

      <div className="rounded-2xl border border-neutral-200/80 bg-white p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        {/* Header Branding */}
        <div className="text-center">
          <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-green-50 border border-brand-green-100 p-2.5 shadow-sm">
            <img
              src="/brandlogo.png"
              alt="Mama Bazar"
              className="h-full w-full object-contain"
            />
          </div>
          <p className="mt-3 text-[11px] font-bold uppercase tracking-[0.25em] text-brand-orange-500">
            Mama Bazar
          </p>
          <h1 className="mt-1 font-headline text-2xl sm:text-3xl font-bold tracking-tight text-neutral-900">
            Sign In
          </h1>
          <p className="mt-1.5 text-xs sm:text-sm text-neutral-500">
            Welcome back! Enter your details to access your account.
          </p>
        </div>

        {/* Form */}
        <form className="mt-7 space-y-4" onSubmit={onSubmit}>
          {/* Email/Phone Field */}
          <div>
            <label
              htmlFor="auth-phone"
              className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-neutral-600"
            >
              Email or Phone Number
            </label>
            <div className="relative">
              <input
                id="auth-phone"
                type="text"
                autoComplete="username"
                className="w-full rounded-xl border border-neutral-300 bg-neutral-50/60 px-3.5 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 outline-none transition-all focus:border-brand-green-500 focus:bg-white focus:ring-2 focus:ring-brand-green-500/15"
                onChange={(e) => setPhone(e.target.value)}
                placeholder="01711111111 or name@example.com"
                required
                value={phone}
              />
              <div className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-neutral-400">
                <User className="h-4 w-4" />
              </div>
            </div>
          </div>

          {/* Password Field */}
          <div>
            <div className="mb-1.5 flex items-center justify-between">
              <label
                htmlFor="auth-password"
                className="block text-xs font-semibold uppercase tracking-wider text-neutral-600"
              >
                Password
              </label>
              <Link
                to="/auth/forgot-password"
                className="text-xs font-semibold text-brand-green-600 hover:text-brand-green-700 transition-colors"
              >
                Forgot password?
              </Link>
            </div>
            <div className="relative">
              <input
                id="auth-password"
                type={showPassword ? 'text' : 'password'}
                autoComplete="current-password"
                className="w-full rounded-xl border border-neutral-300 bg-neutral-50/60 px-3.5 py-2.5 pr-10 text-sm text-neutral-900 placeholder:text-neutral-400 outline-none transition-all focus:border-brand-green-500 focus:bg-white focus:ring-2 focus:ring-brand-green-500/15"
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                required
                value={password}
              />
              <button
                type="button"
                onClick={() => setShowPassword((v) => !v)}
                aria-label={showPassword ? 'Hide password' : 'Show password'}
                className="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-neutral-400 hover:text-neutral-600 transition-colors"
              >
                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
              </button>
            </div>
          </div>

          {/* Error Message */}
          {error && (
            <div className="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50/90 p-3 text-xs font-medium text-red-700 animate-in fade-in">
              <AlertCircle className="h-4 w-4 flex-shrink-0 mt-0.5 text-red-600" />
              <span>{error}</span>
            </div>
          )}

          {/* Submit Button */}
          <button
            type="submit"
            disabled={loading}
            className="group relative flex w-full items-center justify-center gap-2 rounded-xl bg-brand-green-500 py-3 text-xs font-bold uppercase tracking-[0.2em] text-white shadow-sm transition-all hover:bg-brand-green-600 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loading ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin" />
                <span>Signing In...</span>
              </>
            ) : (
              <>
                <Lock className="h-3.5 w-3.5 transition-transform group-hover:scale-110" />
                <span>Sign In</span>
              </>
            )}
          </button>
        </form>

        {/* Switch Link */}
        <div className="mt-6 text-center text-xs sm:text-sm text-neutral-500">
          <span>No account yet? </span>
          <Link
            to="/auth/register"
            className="font-bold text-brand-green-600 hover:text-brand-green-700 transition-colors"
          >
            Create Account
          </Link>
        </div>

        {/* Development Quick-Login Helper */}
        {import.meta.env.DEV && (
          <div className="mt-6 border-t border-dashed border-neutral-200 pt-5">
            <div className="rounded-xl border border-amber-200/80 bg-amber-50/70 p-3.5">
              <div className="flex items-center justify-between">
                <span className="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-amber-900">
                  <span className="rounded bg-brand-orange-500 px-1 py-0.5 text-[9px] font-extrabold text-white">
                    DEV
                  </span>
                  Quick Login
                </span>
                <span className="text-[10px] text-amber-700 font-medium">Local Dev Only</span>
              </div>
              <div className="mt-2.5 grid grid-cols-2 gap-2">
                <button
                  type="button"
                  disabled={loading}
                  onClick={() => onDevLogin('SUPER_ADMIN')}
                  className="rounded-lg border border-brand-green-600/30 bg-white px-2.5 py-1.5 text-[11px] font-bold text-brand-green-700 shadow-2xs transition hover:bg-brand-green-50 active:scale-95 disabled:opacity-50"
                >
                  Super Admin
                </button>
                <button
                  type="button"
                  disabled={loading}
                  onClick={() => onDevLogin('USER')}
                  className="rounded-lg border border-neutral-300 bg-white px-2.5 py-1.5 text-[11px] font-bold text-neutral-700 shadow-2xs transition hover:bg-neutral-50 active:scale-95 disabled:opacity-50"
                >
                  Test Customer
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </>
  )
}

export default LoginPage