import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Eye, EyeOff, UserPlus, User, Phone, AlertCircle, Loader2 } from 'lucide-react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { SEO } from '../components/common/SEO'
import { clearAuthError, registerUser } from '../store/slices/authSlice'
import { useAppDispatch, useAppSelector } from '../store/hooks'

const RegisterPage = () => {
  const dispatch = useAppDispatch()
  const navigate = useNavigate()
  const location = useLocation()
  const { loading, error, user } = useAppSelector((state) => state.auth)

  const [name, setName] = useState('')
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
    dispatch(registerUser({ name: name.trim(), phone: phone.trim(), password }))
  }

  return (
    <>
      <SEO
        title="Create Account | Mama Bazar"
        description="Create a Mama Bazar account to start shopping, track deliveries, and manage your wishlist."
        url="/auth/register"
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
            Create Account
          </h1>
          <p className="mt-1.5 text-xs sm:text-sm text-neutral-500">
            Join Mama Bazar to enjoy fast delivery and exclusive deals.
          </p>
        </div>

        {/* Form */}
        <form className="mt-7 space-y-4" onSubmit={onSubmit}>
          {/* Full Name Field */}
          <div>
            <label
              htmlFor="reg-name"
              className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-neutral-600"
            >
              Full Name
            </label>
            <div className="relative">
              <input
                id="reg-name"
                type="text"
                autoComplete="name"
                className="w-full rounded-xl border border-neutral-300 bg-neutral-50/60 px-3.5 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 outline-none transition-all focus:border-brand-green-500 focus:bg-white focus:ring-2 focus:ring-brand-green-500/15"
                onChange={(e) => setName(e.target.value)}
                placeholder="e.g. Mohammad Ali"
                required
                value={name}
              />
              <div className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-neutral-400">
                <User className="h-4 w-4" />
              </div>
            </div>
          </div>

          {/* Email / Phone Field */}
          <div>
            <label
              htmlFor="reg-phone"
              className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-neutral-600"
            >
              Email or Phone Number
            </label>
            <div className="relative">
              <input
                id="reg-phone"
                type="text"
                autoComplete="tel"
                className="w-full rounded-xl border border-neutral-300 bg-neutral-50/60 px-3.5 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 outline-none transition-all focus:border-brand-green-500 focus:bg-white focus:ring-2 focus:ring-brand-green-500/15"
                onChange={(e) => setPhone(e.target.value)}
                placeholder="01711111111 or name@example.com"
                required
                value={phone}
              />
              <div className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-neutral-400">
                <Phone className="h-4 w-4" />
              </div>
            </div>
          </div>

          {/* Password Field */}
          <div>
            <label
              htmlFor="reg-password"
              className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-neutral-600"
            >
              Password
            </label>
            <div className="relative">
              <input
                id="reg-password"
                type={showPassword ? 'text' : 'password'}
                autoComplete="new-password"
                className="w-full rounded-xl border border-neutral-300 bg-neutral-50/60 px-3.5 py-2.5 pr-10 text-sm text-neutral-900 placeholder:text-neutral-400 outline-none transition-all focus:border-brand-green-500 focus:bg-white focus:ring-2 focus:ring-brand-green-500/15"
                onChange={(e) => setPassword(e.target.value)}
                placeholder="At least 6 characters"
                required
                minLength={6}
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

          {/* Terms info */}
          <p className="text-[11px] leading-relaxed text-neutral-500">
            By signing up, you agree to Mama Bazar's{' '}
            <Link to="/terms-and-conditions" className="font-medium text-brand-green-600 hover:underline">
              Terms
            </Link>{' '}
            and{' '}
            <Link to="/privacy-policy" className="font-medium text-brand-green-600 hover:underline">
              Privacy Policy
            </Link>
            .
          </p>

          {/* Submit Button */}
          <button
            type="submit"
            disabled={loading}
            className="group relative flex w-full items-center justify-center gap-2 rounded-xl bg-brand-green-500 py-3 text-xs font-bold uppercase tracking-[0.2em] text-white shadow-sm transition-all hover:bg-brand-green-600 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loading ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin" />
                <span>Creating Account...</span>
              </>
            ) : (
              <>
                <UserPlus className="h-3.5 w-3.5 transition-transform group-hover:scale-110" />
                <span>Create Account</span>
              </>
            )}
          </button>
        </form>

        {/* Switch Link */}
        <div className="mt-6 text-center text-xs sm:text-sm text-neutral-500">
          <span>Already have an account? </span>
          <Link
            to="/auth/login"
            className="font-bold text-brand-green-600 hover:text-brand-green-700 transition-colors"
          >
            Sign In
          </Link>
        </div>
      </div>
    </>
  )
}

export default RegisterPage