import { useState } from 'react'
import { Loader2, Mail, MapPin, Phone, Send } from 'lucide-react'
import StaticInfoLayout from '@/components/layout/StaticInfoLayout'
import { useGetStoreInfoQuery } from '@/store/services/commerceApi'
import { api } from '@/lib/api'

const CONTACT_ROWS: { key: 'phone' | 'email' | 'address'; label: string; bangla: string; icon: typeof Phone }[] = [
  { key: 'phone', label: 'Phone', bangla: 'ফোন', icon: Phone },
  { key: 'email', label: 'Email', bangla: 'ইমেইল', icon: Mail },
  { key: 'address', label: 'Address', bangla: 'ঠিকানা', icon: MapPin },
]

const ContactForm = () => {
  const [form, setForm] = useState({ name: '', phone: '', email: '', message: '' })
  const [status, setStatus] = useState<'idle' | 'sending' | 'error'>('idle')
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [submitted, setSubmitted] = useState(false)

  const update = (k: keyof typeof form, v: string) => {
    setForm((f) => ({ ...f, [k]: v }))
    setErrors((e) => ({ ...e, [k]: '' }))
  }

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    const next: Record<string, string> = {}
    if (!form.name.trim()) next.name = 'Please enter your name'
    if (!form.phone.trim()) next.phone = 'Please enter your phone number'
    else if (!/^01\d{9}$/.test(form.phone.trim())) next.phone = 'Enter a valid 11-digit mobile number'
    if (!form.message.trim()) next.message = 'Please write a message'
    if (Object.keys(next).length) {
      setErrors(next)
      return
    }
    setStatus('sending')
    try {
      await api.submitContactMessage({
        name: form.name.trim(),
        phone: form.phone.trim(),
        email: form.email.trim() || undefined,
        message: form.message.trim(),
      })
      setSubmitted(true)
      setForm({ name: '', phone: '', email: '', message: '' })
      setErrors({})
    } catch {
      setStatus('error')
    } finally {
      setStatus('idle')
    }
  }

  if (submitted) {
    return (
      <div className="rounded-md border border-brand-green-200 bg-brand-green-50 p-6 text-center">
        <p className="text-lg font-bold text-brand-green-700">Thank You!</p>
        <p className="mt-1 text-sm text-brand-green-600">We have received your message and will get back to you soon.</p>
        <button
          className="mt-4 rounded-md bg-brand-green-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-green-600"
          onClick={() => setSubmitted(false)}
        >
          Send Another Message
        </button>
      </div>
    )
  }

  const inputClass =
    'w-full rounded-md border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand-orange-500 focus:ring-2 focus:ring-brand-orange-100'

  return (
    <div className="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-center gap-2">
        <span className="flex h-8 w-8 items-center justify-center rounded-md bg-brand-green-50 text-brand-green-700">
          <Send className="h-4 w-4" />
        </span>
        <h3 className="text-lg font-bold text-brand-green-700">Send a Message</h3>
      </div>
      <p className="mt-1 text-xs text-slate-500">আপনার বার্তা পাঠান</p>
      <form className="mt-4 grid gap-3" noValidate onSubmit={submit}>
        <div className="grid gap-3 sm:grid-cols-2">
          <div>
            <input
              className={inputClass}
              onChange={(e) => update('name', e.target.value)}
              placeholder="Your name *"
              value={form.name}
            />
            {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
          </div>
          <div>
            <input
              className={inputClass}
              inputMode="tel"
              onChange={(e) => update('phone', e.target.value)}
              placeholder="Mobile number (01XXXXXXXXX) *"
              value={form.phone}
            />
            {errors.phone && <p className="mt-1 text-xs text-red-600">{errors.phone}</p>}
          </div>
        </div>
        <div>
          <input
            className={inputClass}
            onChange={(e) => update('email', e.target.value)}
            placeholder="Email (optional)"
            type="email"
            value={form.email}
          />
        </div>
        <div>
          <textarea
            className={inputClass}
            onChange={(e) => update('message', e.target.value)}
            placeholder="Write your message… *"
            rows={4}
            value={form.message}
          />
          {errors.message && <p className="mt-1 text-xs text-red-600">{errors.message}</p>}
        </div>
        {status === 'error' && <p className="text-sm text-red-600">Could not send the message. Please try again.</p>}
        <button
          className="inline-flex w-fit items-center justify-center gap-2 rounded-md bg-brand-green-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-green-600 disabled:opacity-60"
          disabled={status === 'sending'}
          type="submit"
        >
          {status === 'sending' ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
          Send Message
        </button>
      </form>
    </div>
  )
}

const ContactPage = () => {
  const { data: storeInfo } = useGetStoreInfoQuery()
  const info: Record<string, string> = {
    phone: storeInfo?.primaryPhone || '',
    email: storeInfo?.email || '',
    address: storeInfo?.contactAddress || '',
  }

  return (
    <StaticInfoLayout
      kicker="Get In Touch"
      title="Contact Us"
      seoTitle="Contact Us"
      seoDescription="Contact MamaBazar for any questions or support. Reach us by phone, email, WhatsApp, or our contact form. We are here to help 24/7."
      url="/contact"
      relatedLinks={[
        { title: 'About Us', to: '/about' },
        { title: 'Privacy Policy', to: '/privacy-policy' },
        { title: 'Terms & Conditions', to: '/terms-and-conditions' },
        { title: 'Cookie Policy', to: '/cookie-policy' },
      ]}
    >
      <section id="contact-us" className="scroll-mt-28">
        <h2 className="text-xl font-extrabold text-brand-green-700">Contact Us</h2>
        <p className="mt-1 text-[11px] font-bold uppercase tracking-[0.15em] text-brand-orange-500">
          আমাদের সাথে যোগাযোগ করুন
        </p>
        <p className="mt-3 max-w-3xl text-[15px] leading-7 text-slate-700">
          Have a question or need support? Our friendly team is always happy to help. Reach us through any of the
          channels below.
        </p>
        <p className="mt-2 max-w-3xl text-[15px] leading-7 text-slate-700">
          যেকোনো প্রশ্ন বা সহায়তার জন্য আমাদের সাথে যোগাযোগ করুন। আমাদের বন্ধুত্বপূর্ণ টিম সবসময় আপনার পাশে আছে।
        </p>
      </section>

      <section className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.85fr)] lg:gap-8">
        <div>
          <h3 className="text-lg font-bold text-brand-green-700">Contact Information</h3>
          <p className="mt-1 text-[11px] font-bold uppercase tracking-[0.15em] text-brand-orange-500">
            যোগাযোগের তথ্য
          </p>
          <div className="mt-4 grid gap-3 sm:grid-cols-2">
            {CONTACT_ROWS.map(({ key, label, bangla, icon: Icon }) => {
              const value = info[key]
              if (!value) return null
              return (
                <div key={key} className="flex items-start gap-3 rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                  <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-brand-green-50 text-brand-orange-500">
                    <Icon className="h-4 w-4" />
                  </span>
                  <div className="min-w-0">
                    <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                      {label} · {bangla}
                    </p>
                    <p className="mt-0.5 text-sm font-medium text-brand-green-700">{value}</p>
                  </div>
                </div>
              )
            })}
          </div>
        </div>

        <div>
          <ContactForm />
        </div>
      </section>
    </StaticInfoLayout>
  )
}

export default ContactPage