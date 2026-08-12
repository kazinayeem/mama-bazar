import { CheckCircle2, Mail } from 'lucide-react'
import { useState } from 'react'
import { useToast } from '../../components/common/ToastProvider'
import { useSubscribeNewsletterMutation } from '../../store/services/commerceApi'
import type { HomepageNewsletterSettings } from '../../types/homepage'

interface NewsletterBlockProps {
  settings?: HomepageNewsletterSettings
}

const NewsletterBlock = ({ settings }: NewsletterBlockProps) => {
  const toast = useToast()
  const [email, setEmail] = useState('')
  const [subscribe, { isLoading }] = useSubscribeNewsletterMutation()

  if (!settings?.enabled) return null

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault()
    if (!email.trim()) return
    try {
      const result = await subscribe({ email: email.trim(), source: 'homepage' }).unwrap()
      toast.success(result.alreadySubscribed ? 'You are already subscribed!' : 'Subscribed! Check your inbox for updates.')
      setEmail('')
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Subscription failed. Please try again.')
    }
  }

  return (
    <section className="bg-white pb-14 pt-2 dark:bg-slate-950">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary-700 via-primary to-brand px-6 py-14 shadow-glow sm:px-12 lg:py-16">
          <div className="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-2xl" />
          <div className="pointer-events-none absolute -bottom-20 -left-10 h-72 w-72 rounded-full bg-accent/30 blur-3xl" />
          <div className="relative grid items-center gap-10 lg:grid-cols-2">
            <div>
              <span className="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white backdrop-blur">
                <Mail size={12} /> Newsletter
              </span>
              <h2 className="mt-4 font-headline text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                {settings.title || 'Never miss a deal'}
              </h2>
              <p className="mt-3 max-w-md text-[15px] leading-7 text-primary-50/90">
                {settings.subtitle || 'Subscribe for exclusive deals, early access to new arrivals and smart buying tips.'}
              </p>
              <p className="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-white/80">
                <CheckCircle2 size={13} /> No spam — unsubscribe anytime.
              </p>
            </div>
            <form className="flex flex-col gap-3 rounded-[20px] border border-white/25 bg-white/10 p-3 backdrop-blur-md sm:flex-row" onSubmit={handleSubmit}>
              <input
                aria-label="Email address"
                className="w-full rounded-full bg-white/95 px-5 py-3.5 text-sm text-slate-900 placeholder:text-slate-400"
                onChange={(event) => setEmail(event.target.value)}
                placeholder="Enter your email address"
                required
                type="email"
                value={email}
              />
              <button
                aria-busy={isLoading}
                className="shrink-0 rounded-full bg-accent px-7 py-3.5 text-sm font-bold text-accent-foreground shadow-soft transition hover:bg-accent-600 active:scale-95 disabled:opacity-60"
                disabled={isLoading}
                type="submit"
              >
                {isLoading ? 'Subscribing…' : settings.buttonText || 'Subscribe'}
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>
  )
}

export default NewsletterBlock
