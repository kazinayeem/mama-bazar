import React, { useState, useEffect, useRef } from 'react'
import { ShieldAlert, Eye, EyeOff, Lock, ArrowRight, Loader2, X } from 'lucide-react'

interface SecurityPinModalProps {
  isOpen: boolean
  onClose: () => void
  onSuccess: () => void
  onVerify: (pin: string) => Promise<{ success: boolean; message?: string }>
  title?: string
  subtitle?: string
  actionLabel?: string
}

export const SecurityPinModal: React.FC<SecurityPinModalProps> = ({
  isOpen,
  onClose,
  onSuccess,
  onVerify,
  title = 'Backup Security Check',
  subtitle = 'For your safety, enter the backup PIN before continuing.',
  actionLabel = 'Continue',
}) => {
  const [pin, setPin] = useState('')
  const [showPin, setShowPin] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [isVerifying, setIsVerifying] = useState(false)
  const inputRef = useRef<HTMLInputElement>(null)

  // Dynamic date hint calculation based on current client date
  const [dynamicHint, setDynamicHint] = useState<{ readable: string; formatted: string }>({
    readable: '',
    formatted: 'DDMMYYYY',
  })

  useEffect(() => {
    if (isOpen) {
      setPin('')
      setShowPin(false)
      setError(null)
      setIsVerifying(false)

      const now = new Date()
      const day = String(now.getDate()).padStart(2, '0')
      const monthNum = String(now.getMonth() + 1).padStart(2, '0')
      const monthName = now.toLocaleString('en-US', { month: 'long' })
      const year = now.getFullYear()

      setDynamicHint({
        readable: `${day} ${monthName} ${year}`,
        formatted: `${day}${monthNum}${year}`,
      })

      // Auto-focus input on open
      setTimeout(() => {
        inputRef.current?.focus()
      }, 100)
    }
  }, [isOpen])

  // Handle ESC key
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen && !isVerifying) {
        onClose()
      }
    }
    window.addEventListener('keydown', handleKeyDown)
    return () => window.removeEventListener('keydown', handleKeyDown)
  }, [isOpen, isVerifying, onClose])

  if (!isOpen) return null

  const handleSubmit = async (e?: React.FormEvent) => {
    if (e) e.preventDefault()
    if (!pin.trim() || isVerifying) return

    setError(null)
    setIsVerifying(true)

    try {
      const result = await onVerify(pin.trim())
      if (result.success) {
        setPin('')
        setIsVerifying(false)
        onSuccess()
      } else {
        setError(result.message || 'Wrong PIN. Nice try 😄 Please check your backup PIN and try again.')
        setIsVerifying(false)
        inputRef.current?.focus()
      }
    } catch (err: any) {
      const msg = err?.data?.message || err?.message || 'Wrong PIN. Nice try 😄 Please check your backup PIN and try again.'
      setError(msg)
      setIsVerifying(false)
      inputRef.current?.focus()
    }
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm transition-opacity animate-in fade-in duration-200"
      onClick={(e) => {
        if (e.target === e.currentTarget && !isVerifying) {
          onClose()
        }
      }}
      role="dialog"
      aria-modal="true"
      aria-labelledby="security-pin-modal-title"
    >
      <div className="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-7 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-5 relative animate-in zoom-in-95 fade-in duration-200">
        {/* Close Button */}
        <button
          type="button"
          onClick={onClose}
          disabled={isVerifying}
          aria-label="Close modal"
          className="absolute top-4 right-4 p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition disabled:opacity-40"
        >
          <X className="w-5 h-5" />
        </button>

        {/* Icon & Heading */}
        <div className="flex flex-col items-center text-center space-y-3 pt-1">
          <div className="relative">
            <div className="w-14 h-14 rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-inner border border-emerald-500/20">
              <ShieldAlert className="w-7 h-7" />
            </div>
            <div className="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-primary/20 text-primary flex items-center justify-center">
              <Lock className="w-3 h-3" />
            </div>
          </div>

          <div>
            <h3 id="security-pin-modal-title" className="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
              {title}
            </h3>
            <p className="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-xs mx-auto">
              {subtitle}
            </p>
          </div>
        </div>

        {/* Funny Security Message & Dynamic Hint Card */}
        <div className="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 text-xs text-slate-700 dark:text-slate-300 space-y-2.5 shadow-sm">
          <div className="flex items-start gap-2.5">
            <span className="text-base leading-none">🔐</span>
            <div className="space-y-0.5">
              <p className="font-semibold text-slate-900 dark:text-white">Backup PIN is managed by Bornosoft.</p>
              <p className="text-slate-500 dark:text-slate-400 text-[11px]">
                Need help? Contact <a href="mailto:pin@bornosoft.bd" className="text-primary font-medium underline underline-offset-2 hover:opacity-80">pin@bornosoft.bd</a>
              </p>
            </div>
          </div>


          {dynamicHint.readable && (
            <p className="text-[10px] text-slate-500 dark:text-slate-400 italic">
              Example for today ({dynamicHint.readable}): represents <span className="font-mono font-semibold text-slate-700 dark:text-slate-300">{dynamicHint.formatted}</span>
            </p>
          )}
        </div>

        {/* PIN Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-1.5">
            <label htmlFor="security-pin-input" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
              Enter Backup Security PIN
            </label>
            <div className="relative">
              <input
                id="security-pin-input"
                ref={inputRef}
                type={showPin ? 'text' : 'password'}
                value={pin}
                onChange={(e) => {
                  setPin(e.target.value)
                  if (error) setError(null)
                }}
                disabled={isVerifying}
                autoComplete="off"
                placeholder="Enter 8-digit PIN (DDMMYYYY)"
                maxLength={16}
                className={`w-full px-4 py-3 pr-11 text-center font-mono text-lg tracking-widest rounded-xl bg-slate-50 dark:bg-slate-800 border ${
                  error
                    ? 'border-rose-500 focus:ring-rose-500'
                    : 'border-slate-200 dark:border-slate-700 focus:border-primary focus:ring-primary'
                } text-slate-900 dark:text-white placeholder:text-slate-400 placeholder:text-sm placeholder:font-sans placeholder:tracking-normal focus:outline-none focus:ring-2 focus:ring-opacity-20 transition shadow-inner`}
              />
              <button
                type="button"
                tabIndex={-1}
                onClick={() => setShowPin(!showPin)}
                disabled={isVerifying}
                className="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                aria-label={showPin ? 'Hide PIN' : 'Show PIN'}
              >
                {showPin ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>

            {/* Error Message */}
            {error && (
              <p
                role="alert"
                className="text-xs font-medium text-rose-600 dark:text-rose-400 text-center animate-in fade-in duration-150 pt-1"
              >
                {error}
              </p>
            )}
          </div>

          {/* Action Buttons */}
          <div className="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={onClose}
              disabled={isVerifying}
              className="w-full sm:w-auto px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={!pin.trim() || isVerifying}
              className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition shadow-md active:scale-95"
            >
              {isVerifying ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  Verifying...
                </>
              ) : (
                <>
                  {actionLabel}
                  <ArrowRight className="w-4 h-4" />
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
