import { useState } from 'react'
import type { FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { SEO } from '../components/common/SEO'
import { useRequestPasswordResetMutation } from '../store/services/commerceApi'

const ForgotPasswordPage = () => {
  const [phone, setPhone] = useState('')
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  const [requestPasswordReset] = useRequestPasswordResetMutation()

  const onSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setError(null)
    setMessage(null)
    setLoading(true)
    try {
      const res = await requestPasswordReset(phone).unwrap()
      setMessage(res.message || 'If this phone is registered, a reset link has been sent.')
    } catch (err) {
      setError(err instanceof Error && err.message ? err.message : 'Unable to connect to server')
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-[#f4f6f4] px-4 py-12">
      <SEO
        title="Forgot Password"
        description="Reset your Mama Bazar account password. Enter your registered phone number."
        url="/auth/forgot-password"
      />
      <div className="w-full max-w-md">
        <div className="rounded-2xl border border-neutral-200 bg-white p-8 shadow-[0_10px_40px_-12px_rgba(20,83,45,0.25)] sm:p-10">
          <div className="text-center">
            <p className="text-[10px] font-bold uppercase tracking-[0.25em] text-orange-600">Mama Bazar</p>
            <h1 className="mt-2 font-headline text-3xl font-extrabold tracking-tight text-neutral-950">
              Forgot Password
            </h1>
            <p className="mt-2 text-sm text-neutral-500">Enter your registered phone number to reset your password.</p>
          </div>

          <form className="mt-8 space-y-5" onSubmit={onSubmit}>
            <div>
              <label className="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.2em] text-neutral-500">Phone</label>
              <input
                className="w-full rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-2 text-sm outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20"
                onChange={(e) => setPhone(e.target.value)}
                placeholder="01711111111"
                required
                value={phone}
              />
            </div>

            {message && (
              <p className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{message}</p>
            )}
            {error && <p className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}

            <button
              className="w-full rounded-xl bg-gradient-to-r from-emerald-900 to-emerald-700 py-3.5 text-xs font-bold uppercase tracking-[0.25em] text-white transition hover:from-emerald-950 hover:to-emerald-800 disabled:opacity-60"
              disabled={loading}
              type="submit"
            >
              {loading ? 'Sending...' : 'Send Reset Link'}
            </button>
          </form>

          <p className="mt-5 text-center text-sm text-neutral-500">
            <Link className="font-semibold text-emerald-700 hover:text-emerald-900" to="/auth/login">
              Back to Sign In
            </Link>
          </p>
        </div>
      </div>
    </main>
  )
}

export default ForgotPasswordPage