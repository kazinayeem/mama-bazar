import { useState } from 'react'
import type { FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { Phone, KeyRound, AlertCircle, CheckCircle2, Loader2, ArrowLeft } from 'lucide-react'
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
    if (loading) return
    setError(null)
    setMessage(null)
    setLoading(true)
    try {
      const res = await requestPasswordReset(phone.trim()).unwrap()
      setMessage(res.message || 'If this phone is registered, a reset link has been sent.')
    } catch (err) {
      setError(err instanceof Error && err.message ? err.message : 'Unable to connect to server')
    } finally {
      setLoading(false)
    }
  }

  return (
    <>
      <SEO
        title="Forgot Password | Mama Bazar"
        description="Reset your Mama Bazar account password. Enter your registered phone number."
        url="/auth/forgot-password"
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
            Forgot Password
          </h1>
          <p className="mt-1.5 text-xs sm:text-sm text-neutral-500">
            Enter your phone number and we'll send you instructions to reset your password.
          </p>
        </div>

        {/* Form */}
        <form className="mt-7 space-y-4" onSubmit={onSubmit}>
          <div>
            <label
              htmlFor="reset-phone"
              className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-neutral-600"
            >
              Registered Phone Number
            </label>
            <div className="relative">
              <input
                id="reset-phone"
                type="text"
                autoComplete="tel"
                className="w-full rounded-xl border border-neutral-300 bg-neutral-50/60 px-3.5 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400 outline-none transition-all focus:border-brand-green-500 focus:bg-white focus:ring-2 focus:ring-brand-green-500/15"
                onChange={(e) => setPhone(e.target.value)}
                placeholder="01711111111"
                required
                value={phone}
              />
              <div className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-neutral-400">
                <Phone className="h-4 w-4" />
              </div>
            </div>
          </div>

          {/* Feedback Messages */}
          {message && (
            <div className="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50/90 p-3 text-xs font-medium text-emerald-800 animate-in fade-in">
              <CheckCircle2 className="h-4 w-4 flex-shrink-0 mt-0.5 text-emerald-600" />
              <span>{message}</span>
            </div>
          )}

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
                <span>Sending Instructions...</span>
              </>
            ) : (
              <>
                <KeyRound className="h-3.5 w-3.5 transition-transform group-hover:scale-110" />
                <span>Send Reset Link</span>
              </>
            )}
          </button>
        </form>

        {/* Back Link */}
        <div className="mt-6 text-center text-xs sm:text-sm">
          <Link
            to="/auth/login"
            className="inline-flex items-center gap-1.5 font-bold text-brand-green-600 hover:text-brand-green-700 transition-colors"
          >
            <ArrowLeft className="h-3.5 w-3.5" />
            <span>Back to Sign In</span>
          </Link>
        </div>
      </div>
    </>
  )
}

export default ForgotPasswordPage