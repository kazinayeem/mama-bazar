import { useEffect, useState } from 'react'
import { Store } from 'lucide-react'
import { Link } from 'react-router-dom'
import { useGetCategoriesQuery } from '../../store/services/commerceApi'
import { api } from '../../lib/api'
import type { PaymentMethodInfo } from '../../types'

const PAYMENT_STYLES: Record<string, string> = {
  cod: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  bkash: 'bg-pink-50 text-pink-700 border-pink-200',
  nagad: 'bg-orange-50 text-orange-700 border-orange-200',
  rocket: 'bg-purple-50 text-purple-700 border-purple-200',
  bank: 'bg-blue-50 text-blue-700 border-blue-200',
  stripe: 'bg-indigo-50 text-indigo-700 border-indigo-200',
  sslcommerz: 'bg-amber-50 text-amber-700 border-amber-200',
  paypal: 'bg-sky-50 text-sky-700 border-sky-200',
}

const socialLinks = [
  {
    label: 'Facebook',
    icon: 'M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z',
  },
  {
    label: 'Instagram',
    icon: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zm0 10.162a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z',
  },
  {
    label: 'Twitter / X',
    icon: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
  },
  {
    label: 'YouTube',
    icon: 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
  },
]

const infoLinks = [
  { label: 'আমাদের সম্পর্কে', to: '/#about' },
  { label: 'যোগাযোগ করুন', to: '/contact' },
  { label: 'প্রাইভেসি পলিসি', to: '/privacy-policy' },
  { label: 'শর্তাবলী', to: '/terms-and-conditions' },
  { label: 'কুকি পলিসি', to: '/cookie-policy' },
]

const supportLinks = [
  { label: 'আমার অর্ডার', to: '/dashboard/orders' },
  { label: 'শিপিং ও ডেলিভারি', to: '/shipping-policy' },
  { label: 'রিটার্ন ও রিফান্ড', to: '/refund-policy' },
  { label: 'ওয়ারেন্টি তথ্য', to: '/warranty-policy' },
  { label: 'সাধারণ জিজ্ঞাসা (FAQ)', to: '/faq' },
]

const SiteFooter = () => {
  const year = new Date().getFullYear()
  const categoriesQuery = useGetCategoriesQuery()
  const categories = (categoriesQuery.data || []).filter((c) => !c.parentId)
  const [paymentMethods, setPaymentMethods] = useState<PaymentMethodInfo[]>([])

  useEffect(() => {
    api
      .getPaymentMethods()
      .then((methods) => setPaymentMethods(methods.filter((m) => m.code !== 'cod')))
      .catch(() => {})
  }, [])

  return (
    <footer className="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950" id="contact">
      <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {/* Main columns */}
        <div className="grid grid-cols-2 gap-8 md:grid-cols-4 lg:grid-cols-5">
          {/* Brand column — spans 2 on lg */}
          <div className="col-span-2 lg:col-span-2">
            <div className="flex items-center gap-2">
              <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-primary to-brand text-white">
                <Store size={16} className="fill-white" />
              </span>
              <span className="font-headline text-lg font-extrabold tracking-tight text-slate-900 dark:text-white">
                Mama<span className="text-primary">Bazar</span>
              </span>
            </div>
            <p className="mt-3 max-w-xs text-xs leading-6 text-slate-500 dark:text-slate-400">
              Your trusted online store for home essentials and everyday groceries, delivered fast across the country.
            </p>

            <div className="mt-4 flex gap-2">
              {socialLinks.map(({ icon, label }) => (
                <a
                  aria-label={label}
                  className="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-primary hover:bg-primary hover:text-white dark:border-slate-700 dark:text-slate-400"
                  href="#contact"
                  key={label}
                >
                  <svg fill="currentColor" height="14" viewBox="0 0 24 24" width="14">
                    <path d={icon} />
                  </svg>
                </a>
              ))}
            </div>
          </div>

          {/* Shop By (categories) */}
          <div>
            <p className="mb-3 text-xs font-bold uppercase tracking-[0.15em] text-slate-900 dark:text-white">Shop By</p>
            <ul className="space-y-2">
              {categories.slice(0, 6).map((category) => (
                <li key={category.slug}>
                  <Link className="text-xs text-slate-500 transition hover:text-primary dark:text-slate-400" to={`/shop?category=${category.slug}`}>
                    {category.name}
                  </Link>
                </li>
              ))}
              <li>
                <Link className="text-xs font-semibold text-primary" to="/shop">View All →</Link>
              </li>
            </ul>
          </div>

          {/* Information */}
          <div>
            <p className="mb-3 text-xs font-bold uppercase tracking-[0.15em] text-slate-900 dark:text-white">Information</p>
            <ul className="space-y-2">
              {infoLinks.map((link) => (
                <li key={link.label}>
                  <Link className="text-xs text-slate-500 transition hover:text-primary dark:text-slate-400" to={link.to}>
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Support */}
          <div>
            <p className="mb-3 text-xs font-bold uppercase tracking-[0.15em] text-slate-900 dark:text-white">Support</p>
            <ul className="space-y-2">
              {supportLinks.map((link) => (
                <li key={link.label}>
                  <Link className="text-xs text-slate-500 transition hover:text-primary dark:text-slate-400" to={link.to}>
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </div>

        {/* Bottom row */}
        <div className="mt-8 border-t border-slate-100 pt-6 dark:border-slate-800">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p className="mb-2 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">We Accept</p>
              <div className="flex flex-wrap gap-1.5">
                {paymentMethods.length > 0 ? (
                  paymentMethods.map((method) => (
                    <span
                      className={`rounded border px-2.5 py-1 text-[10px] font-bold tracking-wide ${PAYMENT_STYLES[method.code] || 'border-slate-200 text-slate-600 bg-slate-50'}`}
                      key={method.code}
                    >
                      {method.name}
                    </span>
                  ))
                ) : (
                  ['VISA', 'Mastercard', 'bKash', 'Nagad', 'COD'].map((m) => (
                    <span className="rounded border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-bold text-slate-600" key={m}>
                      {m}
                    </span>
                  ))
                )}
              </div>
            </div>

            <div className="flex flex-col gap-1 text-xs text-slate-400 sm:text-right">
              <p>© {year} Mama Bazar. All rights reserved.</p>
              <div className="flex gap-3 sm:justify-end">
                <Link className="transition hover:text-primary" to="/privacy-policy">Privacy Policy</Link>
                <Link className="transition hover:text-primary" to="/terms-and-conditions">Terms of Service</Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  )
}

export default SiteFooter
