import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Eye, EyeOff } from 'lucide-react'
import { Link, useNavigate } from 'react-router-dom'
import { SEO } from '../components/common/SEO'
import { clearAuthError, registerUser } from '../store/slices/authSlice'
import { useAppDispatch, useAppSelector } from '../store/hooks'

const RegisterPage = () => {
  const dispatch = useAppDispatch()
  const navigate = useNavigate()
  const { loading, error, user } = useAppSelector((state) => state.auth)

  const [name, setName] = useState('')
  const [phone, setPhone] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)

  useEffect(() => {
    if (!user) return
    navigate('/dashboard', { replace: true })
  }, [navigate, user])

  useEffect(() => {
    return () => {
      dispatch(clearAuthError())
    }
  }, [dispatch])

  const onSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    dispatch(registerUser({ name, phone, password }))
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-[#f4f6f4] px-4 py-12">
      <SEO
        title="Create Account"
        description="Create a Mama Bazar account to start shopping. Sign up with your phone number."
        url="/auth/register"
      />
      <div className="w-full max-w-md">
        <div className="rounded-2xl border border-neutral-200 bg-white p-8 shadow-[0_10px_40px_-12px_rgba(20,83,45,0.25)] sm:p-10">
          <div className="text-center">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-900 to-emerald-600 shadow-lg shadow-emerald-900/20">
              <span className="font-headline text-2xl font-extrabold text-white">M</span>
            </div>
            <p className="mt-4 text-[10px] font-bold uppercase tracking-[0.25em] text-orange-600">Mama Bazar</p>
            <h1 className="mt-3 font-headline text-3xl font-extrabold tracking-tight text-neutral-950">Create Account</h1>
            <p className="mt-2 text-sm text-neutral-500">Register with your phone to start shopping.</p>
          </div>

          <form className="mt-8 space-y-5" onSubmit={onSubmit}>
            <div>
              <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.2em] text-neutral-500">Name</label>
              <input
                className="w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2 text-sm outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                onChange={(e) => setName(e.target.value)}
                placeholder="Your name"
                required
                value={name}
              />
            </div>
            <div>
              <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.2em] text-neutral-500">Email / Phone</label>
              <input
                className="w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2 text-sm outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                onChange={(e) => setPhone(e.target.value)}
                placeholder="01711111111"
                required
                value={phone}
              />
            </div>
            <div>
              <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.2em] text-neutral-500">Password</label>
              <div className="relative">
                <input
                  className="w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2 pr-11 text-sm outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="At least 6 characters"
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
              {loading ? 'Creating...' : 'Create Account'}
            </button>
          </form>

          <p className="mt-5 text-center text-sm text-neutral-500">
            Already have an account?{' '}
            <Link className="font-semibold text-emerald-700 hover:text-emerald-900" to="/auth/login">
              Sign in
            </Link>
          </p>
        </div>
      </div>
    </main>
  )
}

export default RegisterPage