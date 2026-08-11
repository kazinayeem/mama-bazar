import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Eye, EyeOff } from 'lucide-react'
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
    dispatch(loginUser({ phone, password }))
  }

  const onDevLogin = (role: 'SUPER_ADMIN' | 'USER') => {
    dispatch(loginAsDev(role))
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-[#f4f6f4] px-4 py-12">
      <SEO
        title="Sign In"
        description="Sign in to your Mama Bazar account to access your orders, wishlist, and dashboard."
        url="/auth/login"
      />
      <div className="w-full max-w-md">
        <div className="rounded-2xl border border-neutral-200 bg-white p-8 shadow-[0_10px_40px_-12px_rgba(20,83,45,0.25)] sm:p-10">
          <div className="text-center">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-900 to-emerald-600 shadow-lg shadow-emerald-900/20">
              <span className="font-headline text-2xl font-extrabold text-white">M</span>
            </div>
            <p className="mt-4 text-[10px] font-bold uppercase tracking-[0.3em] text-orange-600">Mama Bazar</p>
            <h1 className="mt-1 font-headline text-3xl font-extrabold tracking-tight text-emerald-950">Sign In</h1>
            <p className="mt-2 text-sm text-neutral-500">
              Authenticate with your backend account to access your dashboard.
            </p>
          </div>

          <form className="mt-8 space-y-5" onSubmit={onSubmit}>
            <div>
              <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.2em] text-neutral-600">
                Email / Phone
              </label>
              <input
                className="w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-3 text-sm outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                onChange={(e) => setPhone(e.target.value)}
                placeholder="01711111111"
                required
                value={phone}
              />
            </div>
            <div>
              <div className="mb-1.5 flex items-center justify-between">
                <label className="block text-[10px] font-bold uppercase tracking-[0.2em] text-neutral-500">
                  Password
                </label>
                <Link className="text-xs font-semibold text-emerald-700 hover:text-emerald-900" to="/auth/forgot-password">
                  Forgot password?
                </Link>
              </div>
              <div className="relative">
                <input
                  className="w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2 pr-11 text-sm outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  required
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                />
                <button
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 transition hover:text-emerald-700"
                  onClick={() => setShowPassword((v) => !v)}
                  type="button"
                >
                  {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
              </div>
            </div>

            {error && (
              <p className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>
            )}

            <button
              className="w-full rounded-xl bg-gradient-to-r from-emerald-900 to-emerald-700 py-3.5 text-xs font-bold uppercase tracking-[0.25em] text-white transition hover:from-emerald-950 hover:to-emerald-800 disabled:opacity-60"
              disabled={loading}
              type="submit"
            >
              {loading ? 'Signing In...' : 'Sign In'}
            </button>
          </form>

          <p className="mt-5 text-center text-sm text-neutral-500">
            No account yet?{' '}
            <Link className="font-semibold text-emerald-700 hover:text-emerald-900" to="/auth/register">
              Create Account
            </Link>
          </p>
        </div>

        {import.meta.env.DEV && (
          <>
            <div className="my-6 flex items-center gap-3">
              <span className="h-px flex-1 bg-neutral-300" />
              <span className="text-[10px] font-bold uppercase tracking-[0.25em] text-neutral-400">OR</span>
              <span className="h-px flex-1 bg-neutral-300" />
            </div>

            <div className="rounded-2xl border-2 border-dashed border-neutral-300 bg-neutral-100/70 p-6">
              <p className="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] text-neutral-500">
                <span className="rounded bg-orange-500 px-1.5 py-0.5 text-white">DEV</span>
                Development Quick Login
              </p>
              <p className="mt-2 text-xs text-neutral-500">
                Local-only convenience. Logs in real seeded database accounts through the regular backend
                authentication — never available in production.
              </p>
              <div className="mt-4 space-y-2">
                <button
                  className="w-full rounded-xl border border-emerald-700/40 bg-white px-5 py-3 text-xs font-bold uppercase tracking-[0.2em] text-emerald-900 transition hover:bg-emerald-50 disabled:opacity-60"
                  disabled={loading}
                  onClick={() => onDevLogin('SUPER_ADMIN')}
                  type="button"
                >
                  Continue as Super Admin
                </button>
                <button
                  className="w-full rounded-xl border border-emerald-700/40 bg-white px-5 py-3 text-xs font-bold uppercase tracking-[0.2em] text-emerald-900 transition hover:bg-emerald-50 disabled:opacity-60"
                  disabled={loading}
                  onClick={() => onDevLogin('USER')}
                  type="button"
                >
                  Continue as Test User
                </button>
              </div>
            </div>

            <p className="mt-4 text-center text-xs text-neutral-400">
              Development credentials are configured in the backend seed script.
            </p>
          </>
        )}
      </div>
    </main>
  )
}

export default LoginPage