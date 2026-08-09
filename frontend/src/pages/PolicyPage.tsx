import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { createRoot } from 'react-dom/client'
import { Link, useParams } from 'react-router-dom'
import {
  AlertCircle,
  ChevronRight,
  Clock,
  Home,
  Loader2,
  Mail,
  MapPin,
  MessageCircle,
  Phone,
  Send,
} from 'lucide-react'

import { SEO, getPolicyPageSEO } from '@/components/common/SEO'
import { api } from '@/lib/api'
import { cn } from '@/lib/utils'
import type { PaymentMethodInfo, PolicyPage as PolicyPageType, ShippingMethod } from '@/types'

const POLICY_LINKS: { title: string; to: string }[] = [
  { title: 'রিটার্ন ও রিফান্ড নীতিমালা', to: '/refund-policy' },
  { title: 'ডেলিভারি নীতিমালা', to: '/shipping-policy' },
  { title: 'প্রাইভেসি পলিসি', to: '/privacy-policy' },
  { title: 'শর্তাবলী', to: '/terms-and-conditions' },
  { title: 'কুকি পলিসি', to: '/cookie-policy' },
  { title: 'পেমেন্ট নীতিমালা', to: '/payment-policy' },
  { title: 'ক্যান্সেলেশন নীতিমালা', to: '/cancellation-policy' },
  { title: 'ওয়ারেন্টি নীতিমালা', to: '/warranty-policy' },
  { title: 'সাধারণ জিজ্ঞাসা (FAQ)', to: '/faq' },
  { title: 'যোগাযোগ করুন', to: '/contact' },
]

const TOKEN_TAG = (name: string) =>
  `<div class="policy-token" data-policy-token="${name}"></div>`

const SLUG_TO_PATH: Record<string, string> = {
  'return-refund': '/refund-policy',
  shipping: '/shipping-policy',
  privacy: '/privacy-policy',
  terms: '/terms-and-conditions',
  cookie: '/cookie-policy',
  payment: '/payment-policy',
  cancellation: '/cancellation-policy',
  warranty: '/warranty-policy',
  faq: '/faq',
  contact: '/contact',
}

const ShippingMethodsBlock = () => {
  const [methods, setMethods] = useState<ShippingMethod[] | null>(null)

  useEffect(() => {
    api
      .getShippingMethods()
      .then(setMethods)
      .catch(() => setMethods([]))
  }, [])

  if (methods === null) {
    return (
      <div className="my-4 flex items-center gap-2 rounded-md bg-emerald-50 p-4 text-sm text-emerald-800">
        <Loader2 className="h-4 w-4 animate-spin" /> ডেলিভারি তথ্য লোড হচ্ছে…
      </div>
    )
  }

  if (methods.length === 0) {
    return (
      <div className="my-4 rounded-md bg-slate-50 p-4 text-sm text-slate-500">
        এই মুহূর্তে ডেলিভারি অপশন পাওয়া যাচ্ছে না। বিস্তারিত জানতে আমাদের হটলাইনে যোগাযোগ করুন।
      </div>
    )
  }

  return (
    <div className="my-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
      <table className="w-full text-left text-sm">
        <thead>
          <tr className="bg-emerald-950 text-white">
            <th className="px-4 py-3 font-semibold">পদ্ধতি</th>
            <th className="px-4 py-3 font-semibold">চার্জ</th>
            <th className="hidden px-4 py-3 font-semibold sm:table-cell">ডেলিভারি সময়</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-emerald-50">
          {methods.map((m, i) => (
            <tr key={m.id} className={cn(i % 2 === 1 && 'bg-emerald-50/40')}>
              <td className="px-4 py-3 font-medium text-emerald-950">{m.name}</td>
              <td className="px-4 py-3 text-emerald-800">
                {Number(m.charge) === 0 ? 'ফ্রি' : `৳${m.charge}`}
              </td>
              <td className="hidden px-4 py-3 text-emerald-700 sm:table-cell">
                {m.estimatedDelivery || '—'}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

const PaymentMethodsBlock = () => {
  const [payments, setPayments] = useState<PaymentMethodInfo[] | null>(null)

  useEffect(() => {
    api
      .getPaymentMethods()
      .then(setPayments)
      .catch(() => setPayments([]))
  }, [])

  if (payments === null) {
    return (
      <div className="my-4 flex items-center gap-2 rounded-md bg-emerald-50 p-4 text-sm text-emerald-800">
        <Loader2 className="h-4 w-4 animate-spin" /> পেমেন্ট তথ্য লোড হচ্ছে…
      </div>
    )
  }

  return (
    <ul className="my-4 grid gap-2 sm:grid-cols-2">
      {payments.map((p) => (
        <li key={p.code} className="rounded-md border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm">
          <p className="font-semibold text-emerald-950">{p.name}</p>
          {p.config?.instructions && (
            <p className="mt-0.5 text-xs leading-5 text-slate-500">{p.config.instructions}</p>
          )}
        </li>
      ))}
    </ul>
  )
}

const CONTACT_ROWS: { key: string; label: string; icon: typeof Phone }[] = [
  { key: 'hotline', label: 'হটলাইন', icon: Phone },
  { key: 'phone', label: 'ফোন', icon: Phone },
  { key: 'whatsapp', label: 'হোয়াটসঅ্যাপ', icon: MessageCircle },
  { key: 'email', label: 'ইমেইল', icon: Mail },
  { key: 'address', label: 'ঠিকানা', icon: MapPin },
  { key: 'hours', label: 'সার্ভিস সময়', icon: Clock },
]

const ContactInfoBlock = () => {
  const [info, setInfo] = useState<Record<string, string> | null>(null)

  useEffect(() => {
    api
      .getContactSetting()
      .then(setInfo)
      .catch(() => setInfo({}))
  }, [])

  return (
    <div className="my-4 grid gap-2 sm:grid-cols-2">
      {CONTACT_ROWS.map(({ key, label, icon: Icon }) => {
        const value = info?.[key]
        if (!value) return null
        return (
          <div key={key} className="flex items-start gap-3 rounded-md border border-slate-200 bg-white p-3.5 shadow-sm">
            <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-50 text-orange-600">
              <Icon className="h-4 w-4" />
            </span>
            <div className="min-w-0">
              <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{label}</p>
              <p className="break-words text-sm font-medium text-emerald-950">{value}</p>
            </div>
          </div>
        )
      })}
    </div>
  )
}

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
    if (!form.name.trim()) next.name = 'আপনার নাম লিখুন'
    if (!form.phone.trim()) next.phone = 'ফোন নম্বর লিখুন'
    else if (!/^01\d{9}$/.test(form.phone.trim())) next.phone = 'সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন'
    if (!form.message.trim()) next.message = 'বার্তা লিখুন'
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
      <div className="my-4 rounded-md border border-emerald-200 bg-emerald-50 p-6 text-center">
        <p className="text-lg font-bold text-emerald-900">ধন্যবাদ!</p>
        <p className="mt-1 text-sm text-emerald-700">
          আপনার বার্তা আমাদের কাছে পৌঁছেছে। আমরা যত দ্রুত সম্ভব যোগাযোগ করব।
        </p>
        <button
          className="mt-4 rounded-md bg-emerald-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-950"
          onClick={() => setSubmitted(false)}
        >
          আরেকটি বার্তা পাঠান
        </button>
      </div>
    )
  }

  const inputClass =
    'w-full rounded-md border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-100'

  return (
    <div className="my-4 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-center gap-2">
        <span className="flex h-8 w-8 items-center justify-center rounded-md bg-emerald-50 text-emerald-900">
          <Send className="h-4 w-4" />
        </span>
        <h3 className="text-lg font-bold text-emerald-950">বার্তা পাঠান</h3>
      </div>
      <form className="mt-4 grid gap-3" onSubmit={submit} noValidate>
        <div className="grid gap-3 sm:grid-cols-2">
          <div>
            <input
              value={form.name}
              onChange={(e) => update('name', e.target.value)}
              placeholder="আপনার নাম *"
              className={inputClass}
            />
            {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
          </div>
          <div>
            <input
              value={form.phone}
              onChange={(e) => update('phone', e.target.value)}
              placeholder="মোবাইল নম্বর (01XXXXXXXXX) *"
              inputMode="tel"
              className={inputClass}
            />
            {errors.phone && <p className="mt-1 text-xs text-red-600">{errors.phone}</p>}
          </div>
        </div>
        <div>
          <input
            value={form.email}
            onChange={(e) => update('email', e.target.value)}
            placeholder="ইমেইল (ঐচ্ছিক)"
            type="email"
            className={inputClass}
          />
        </div>
        <div>
          <textarea
            value={form.message}
            onChange={(e) => update('message', e.target.value)}
            placeholder="আপনার বার্তা লিখুন… *"
            rows={4}
            className={inputClass}
          />
          {errors.message && <p className="mt-1 text-xs text-red-600">{errors.message}</p>}
        </div>
        {status === 'error' && (
          <p className="text-sm text-red-600">বার্তা পাঠানো যায়নি। আবার চেষ্টা করুন।</p>
        )}
        <button
          disabled={status === 'sending'}
          className="inline-flex w-fit items-center justify-center gap-2 rounded-md bg-emerald-950 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-900 disabled:opacity-60"
        >
          {status === 'sending' ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
          বার্তা পাঠান
        </button>
      </form>
    </div>
  )
}

const TOKEN_COMPONENTS: Record<string, React.ComponentType> = {
  SHIPPING_METHODS: ShippingMethodsBlock,
  PAYMENT_METHODS: PaymentMethodsBlock,
  CONTACT_INFO: ContactInfoBlock,
  CONTACT_FORM: ContactForm,
}

interface PolicyPageProps {
  slug?: string
}

const PolicyPage = ({ slug: slugProp }: PolicyPageProps) => {
  const { slug: slugParam } = useParams()
  const slug = slugProp || slugParam || ''

  const contentRef = useRef<HTMLDivElement>(null)
  const [page, setPage] = useState<PolicyPageType | null>(null)
  const [loading, setLoading] = useState(true)
  const [notFound, setNotFound] = useState(false)
  const [toc, setToc] = useState<{ id: string; text: string }[]>([])

  useEffect(() => {
    if (!slug) return
    api
      .getPolicyPage(slug)
      .then(setPage)
      .catch(() => setNotFound(true))
      .finally(() => setLoading(false))
  }, [slug])

  const renderTokens = useCallback(() => {
    const container = contentRef.current
    if (!container) return
    container.querySelectorAll('[data-policy-token]').forEach((node) => {
      const name = node.getAttribute('data-policy-token') || ''
      const Component = TOKEN_COMPONENTS[name]
      if (!Component) return
      const el = node as HTMLElement
      if ((el as unknown as { __policyRoot?: ReturnType<typeof createRoot> }).__policyRoot) {
        ;(el as unknown as { __policyRoot: ReturnType<typeof createRoot> }).__policyRoot.unmount()
      }
      const root = createRoot(node)
      ;(el as unknown as { __policyRoot?: ReturnType<typeof createRoot> }).__policyRoot = root
      root.render(<Component />)
    })

    const headings = Array.from(container.querySelectorAll('h2, h3'))
    const items: { id: string; text: string }[] = []
    headings.forEach((h, i) => {
      const id = `policy-section-${i}`
      h.setAttribute('id', id)
      const t = h.textContent?.trim() || ''
      if (t) items.push({ id, text: t })
    })
    setToc(items)
  }, [])

  useEffect(() => {
    if (!page) return
    const raf = requestAnimationFrame(renderTokens)
    return () => cancelAnimationFrame(raf)
  }, [page, renderTokens])

  const html = useMemo(
    () =>
      (page?.content || '')
        .replace(/<script[\s\S]*?<\/script>/gi, '')
        .replace(/\{\{([A-Z_]+)\}\}/g, (_, name: string) => TOKEN_TAG(name)),
    [page],
  )

  if (loading) {
    return (
      <div className="mx-auto flex min-h-[50vh] max-w-3xl items-center justify-center">
        <SEO title="Loading..." description="Loading page content..." noIndex />
        <div className="flex flex-col items-center gap-3 text-slate-400">
          <Loader2 className="h-8 w-8 animate-spin text-emerald-900" />
          <p className="text-sm">লোড হচ্ছে…</p>
        </div>
      </div>
    )
  }

  if (!slug || notFound || !page) {
    return (
      <div className="mx-auto flex min-h-[50vh] max-w-xl flex-col items-center justify-center px-4 text-center">
        <SEO title="Page Not Found" description="The page you are looking for does not exist." noIndex />
        <AlertCircle className="h-12 w-12 text-orange-500" />
        <h1 className="mt-4 text-2xl font-bold text-emerald-950">পেজটি পাওয়া যায়নি</h1>
        <p className="mt-2 text-sm text-slate-500">পেজটি মুছে ফেলা হয়েছে অথবা ঠিকানা ভুল।</p>
        <Link
          to="/"
          className="mt-6 inline-flex items-center gap-2 rounded-md bg-emerald-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-900"
        >
          <Home className="h-4 w-4" /> হোম পেজে ফিরে যান
        </Link>
      </div>
    )
  }

  const updatedAt = page.lastUpdated ? new Date(page.lastUpdated * 1000) : undefined
  const currentPath = SLUG_TO_PATH[page.slug] || `/${page.slug}`
  const otherPolicyLinks = POLICY_LINKS.filter((l) => l.to !== currentPath)

  return (
    <div className="min-h-[60vh] bg-[#f5f7f5] pb-16">
      <div className="mx-auto max-w-7xl px-4 pt-5 sm:px-6 lg:px-8">
        <SEO {...getPolicyPageSEO(slug)} />
        <nav className="flex items-center gap-1.5 text-xs text-slate-500">
          <Link to="/" className="flex items-center gap-1 transition hover:text-emerald-900">
            <Home className="h-3.5 w-3.5" /> হোম
          </Link>
          <ChevronRight className="h-3.5 w-3.5" />
          <span className="font-medium text-emerald-950">{page.title}</span>
        </nav>

        <div className="mt-6 grid gap-8 lg:grid-cols-[260px_minmax(0,1fr)]">
          {toc.length > 0 && (
            <aside className="order-2 lg:order-1">
              <div className="hidden rounded-md border border-slate-200 bg-white p-4 shadow-sm lg:sticky lg:top-24 lg:block">
                <p className="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">
                  এই পেজে
                </p>
                <ul className="mt-3 space-y-1">
                  {toc.map((item) => (
                    <li key={item.id}>
                      <a
                        href={`#${item.id}`}
                        className="block rounded-md px-2 py-1.5 text-[13px] leading-5 text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-900"
                      >
                        {item.text}
                      </a>
                    </li>
                  ))}
                </ul>
              </div>
            </aside>
          )}

          <article className="order-1 min-w-0 lg:order-2">
            <div className="rounded-md border border-slate-200 bg-white px-5 py-8 shadow-sm sm:px-10">
              <p className="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-600">
                পলিসি ও গাইডলাইন
              </p>
              <h1 className="mt-2 text-2xl font-extrabold leading-snug text-emerald-950 sm:text-3xl">
                {page.title}
              </h1>
              {updatedAt && (
                <p className="mt-3 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800">
                  <Clock className="h-3.5 w-3.5" /> সর্বশেষ আপডেট:{' '}
                  {updatedAt.toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' })}
                </p>
              )}

              <div
                ref={contentRef}
                className="policy-body mt-8 text-[15px] leading-7 text-slate-700 [&_h2]:mt-10 [&_h2]:border-b [&_h2]:border-slate-100 [&_h2]:pb-2 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-emerald-950 [&_h3]:mt-6 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-emerald-900 [&_p]:mt-4 [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:space-y-1.5 [&_ul]:pl-5 [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:space-y-1.5 [&_ol]:pl-5 [&_li]:leading-7 [&_strong]:font-semibold [&_strong]:text-emerald-950 [&_a]:font-medium [&_a]:text-orange-600 [&_a]:underline [&_a]:underline-offset-4 [&_blockquote]:mt-4 [&_blockquote]:border-l-4 [&_blockquote]:border-orange-400 [&_blockquote]:bg-orange-50/60 [&_blockquote]:px-4 [&_blockquote]:py-3 [&_blockquote]:italic [&_blockquote]:text-slate-600 [&_hr]:mt-8 [&_hr]:border-slate-100"
                dangerouslySetInnerHTML={{ __html: html }}
              />

              {toc.length > 0 && (
                <div className="mt-8 rounded-md border border-slate-200 bg-slate-50 p-4 lg:hidden">
                  <p className="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">
                    এই পেজে
                  </p>
                  <ul className="mt-2 space-y-1">
                    {toc.map((item) => (
                      <li key={item.id}>
                        <a href={`#${item.id}`} className="block text-[13px] text-slate-600 hover:text-emerald-900">
                          {item.text}
                        </a>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>

            <section className="mt-6 rounded-md border border-emerald-900/20 bg-emerald-950 px-5 py-6 text-white shadow-sm sm:px-8">
              <h2 className="flex items-center gap-2 text-lg font-bold">
                <MessageCircle className="h-5 w-5 text-orange-400" />
                আরও সাহায্য দরকার?
              </h2>
              <p className="mt-2 text-sm leading-6 text-emerald-100/90">
                আমাদের সাপোর্ট টিম অর্ডার, ডেলিভারি এবং রিটার্ন সংক্রান্ত যেকোনো প্রশ্নে আপনাকে
                সাহায্য করতে প্রস্তুত।
              </p>
              <div className="mt-4 flex flex-wrap gap-3">
                <Link
                  to="/contact"
                  className="inline-flex items-center gap-2 rounded-md bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-600"
                >
                  <Phone className="h-4 w-4" /> যোগাযোগ করুন
                </Link>
                <Link
                  to="/faq"
                  className="inline-flex items-center gap-2 rounded-md border border-emerald-100/30 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-900"
                >
                  FAQ দেখুন
                </Link>
              </div>
            </section>

            <section className="mt-6">
              <p className="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">
                অন্যান্য নীতিমালা
              </p>
              <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                {otherPolicyLinks.map((l) => (
                  <Link
                    key={l.to}
                    to={l.to}
                    className="rounded-md border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-emerald-950 shadow-sm transition hover:border-orange-300 hover:text-orange-600"
                  >
                    {l.title}
                  </Link>
                ))}
              </div>
            </section>
          </article>
        </div>
      </div>
    </div>
  )
}

export default PolicyPage