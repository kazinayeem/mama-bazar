import { useCallback, useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { LogIn, CheckCircle, Copy, Check } from 'lucide-react'
import { SEO } from '../components/common/SEO'
import { api } from '../lib/api'
import { currency } from '../lib/format'
import { trackInitiateCheckout } from '../lib/pixel'
import { useAppDispatch, useAppSelector } from '../store/hooks'
import { clearCart, removeFromCart } from '../store/slices/cartSlice'
import { setAuthSession } from '../store/slices/authSlice'
import { placeOrder } from '../store/slices/ordersSlice'
import type {
  CheckoutNotice,
  Order,
  PaymentMethod,
  PaymentMethodInfo,
  ShippingMethod,
  UserAddress,
} from '../types'

const BD_PHONE_REGEX = /^(\+880|0)[1-9]\d{9}$/

const normalizeBdPhone = (value: string) => {
  const trimmed = value.trim()
  if (trimmed.startsWith('+880')) return `0${trimmed.slice(4)}`
  return trimmed
}

const NOTICE_ICONS: Record<string, string> = {
  truck: '🚚',
  alert: '⚠️',
  info: 'ℹ️',
  discount: '🏷️',
}

const PAYMENT_ICONS: Record<string, string> = {
  cod: '💵',
  bkash: '৳',
  nagad: '৳',
  rocket: '৳',
  bank: '🏦',
  stripe: '💳',
  sslcommerz: '🔒',
  paypal: '🅿️',
}

// Bilingual label helper — splits "Bangla / English" and styles each part appropriately
const renderBilingual = (label: string) => {
  const parts = label.split(' / ').map((s) => s.trim())
  if (parts.length === 2) {
    return (
      <span className="bilingual-label">
        <span className="bangla-part">{parts[0]}</span>
        <span className="english-part">{parts[1]}</span>
      </span>
    )
  }
  // Fallback for single-language labels
  return <span className="bangla-text">{label}</span>
}

// Format shipping method name bilingually from known mappings
const BILINGUAL_SHIPPING: Record<string, string> = {
  Dhaka: 'ঢাকা / Dhaka',
  'outside dhaka': 'ঢাকার বাইরে / Outside Dhaka',
  'Outside Dhaka': 'ঢাকার বাইরে / Outside Dhaka',
}

const getShippingLabel = (name: string) => BILINGUAL_SHIPPING[name] ?? name

// Calculate the actual shipping cost for display — use charge when estimatedCost is 0 but charge > 0
const getShippingDisplayCost = (method: ShippingMethod): number => {
  const estimatedCost = Number(method.estimatedCost ?? 0)
  const charge = Number(method.charge ?? 0)
  // If estimatedCost is explicitly 0 AND charge > 0 AND freeShippingMinAmount is 0, it's NOT free
  // Use charge as fallback when estimatedCost is 0 but charge > 0
  if (estimatedCost === 0 && charge > 0 && (Number(method.freeShippingMinAmount ?? 0) === 0)) {
    return charge
  }
  return estimatedCost > 0 ? estimatedCost : charge
}

// Check if shipping is genuinely free
const isShippingFree = (method: ShippingMethod): boolean => {
  const displayCost = getShippingDisplayCost(method)
  return displayCost === 0
}

interface AddressFormState {
  name: string
  phone: string
  alternativePhone: string
  email: string
  country: string
  division: string
  district: string
  upazila: string
  area: string
  address: string
  apartment: string
  postalCode: string
}

const emptyAddress: AddressFormState = {
  name: '',
  phone: '',
  alternativePhone: '',
  email: '',
  country: 'Bangladesh',
  division: '',
  district: '',
  upazila: '',
  area: '',
  address: '',
  apartment: '',
  postalCode: '',
}

const CheckoutPage = () => {
  const dispatch = useAppDispatch()
  const navigate = useNavigate()
  const cart = useAppSelector((state) => state.cart.items)
  const authUser = useAppSelector((state) => state.auth.user)
  const { creating } = useAppSelector((state) => state.orders)

  const [form, setForm] = useState<AddressFormState>(emptyAddress)
  const [savedAddresses, setSavedAddresses] = useState<UserAddress[]>([])
  const [selectedAddressId, setSelectedAddressId] = useState<'new' | number>('new')
  const [addressesLoading, setAddressesLoading] = useState(false)

  const [shippingMethods, setShippingMethods] = useState<ShippingMethod[]>([])
  const [shippingMethodId, setShippingMethodId] = useState<number | null>(null)
  const [shippingLoading, setShippingLoading] = useState(true)

  const [paymentMethods, setPaymentMethods] = useState<PaymentMethodInfo[]>([])
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('cod')
  const [notices, setNotices] = useState<CheckoutNotice[]>([])

  const [transactionId, setTransactionId] = useState('')
  const [copiedNumber, setCopiedNumber] = useState<string | null>(null)

  const [couponCode, setCouponCode] = useState('')
  const [couponApplied, setCouponApplied] = useState<{ code: string; discount: number } | null>(null)
  const [couponError, setCouponError] = useState('')
  const [couponLoading, setCouponLoading] = useState(false)

  const [orderNote, setOrderNote] = useState('')
  const [submitError, setSubmitError] = useState('')
  const [agreeTerms, setAgreeTerms] = useState(false)

  // Tax configuration comes from the admin "Tax Settings" (single source of truth).
  const [taxSettings, setTaxSettings] = useState<{ taxRate: number; applyTaxToShipping: boolean }>({
    taxRate: 0,
    applyTaxToShipping: false,
  })

  const subtotal = useMemo(
    () => cart.reduce((sum, item) => sum + Number(item.product.price) * item.quantity, 0),
    [cart],
  )

  const shippingCost = useMemo(() => {
    if (shippingMethodId === null) return 0
    return shippingMethods.find((m) => m.id === shippingMethodId)?.estimatedCost ?? 0
  }, [shippingMethodId, shippingMethods])

  const discount = couponApplied?.discount ?? 0
  const taxRate = taxSettings.taxRate
  const taxable = subtotal - discount + (taxSettings.applyTaxToShipping ? shippingCost : 0)
  const tax = Math.round(Math.max(0, taxable) * (taxRate / 100))
  const total = subtotal - discount + tax + shippingCost

  const selectedShippingMethod = shippingMethods.find((m) => m.id === shippingMethodId) || null
  const selectedPaymentMethod = paymentMethods.find((m) => m.code === paymentMethod) || null
  const isMobileBanking = selectedPaymentMethod?.type === 'mobile_banking'
  const isBankTransfer = selectedPaymentMethod?.type === 'bank'
  const requiresTransactionId = isMobileBanking || isBankTransfer

  const set = (patch: Partial<AddressFormState>) => setForm((prev) => ({ ...prev, ...patch }))

  // ---------- Copy to clipboard ----------
  const copyToClipboard = async (text: string) => {
    try {
      await navigator.clipboard.writeText(text)
      setCopiedNumber(text)
      setTimeout(() => setCopiedNumber(null), 2000)
    } catch {
      // Fallback for older browsers
      const textArea = document.createElement('textarea')
      textArea.value = text
      document.body.appendChild(textArea)
      textArea.select()
      document.execCommand('copy')
      document.body.removeChild(textArea)
      setCopiedNumber(text)
      setTimeout(() => setCopiedNumber(null), 2000)
    }
  }

  // ---------- Auto-fill from user profile ----------
  useEffect(() => {
    if (!authUser) return

    // Auto-fill from user profile if form is empty
    setForm((prev) => ({
      ...prev,
      name: prev.name || authUser.name || '',
      phone: prev.phone || authUser.phone || '',
      email: prev.email || '',
    }))
  }, [authUser])

  // ---------- Load checkout config ----------
  useEffect(() => {
    let mounted = true
    const load = async () => {
      try {
        const [methods, payments, noticeList, tax] = await Promise.all([
          api.estimateShipping(0),
          api.getPaymentMethods(),
          api.getCheckoutNotices(),
          api.getTaxSettings(),
        ])
        if (!mounted) return
        setShippingMethods(methods)
        const first = methods[0]
        if (first) setShippingMethodId(first.id)
        setPaymentMethods(payments)
        const cod = payments.find((p) => p.code === 'cod')
        if (cod) setPaymentMethod('cod')
        setNotices(noticeList)
        setTaxSettings(tax)
      } catch {
        if (!mounted) return
      } finally {
        if (mounted) setShippingLoading(false)
      }
    }
    load()
    return () => {
      mounted = false
    }
  }, [])

  // Re-estimate shipping when subtotal changes (free-shipping thresholds)
  useEffect(() => {
    if (shippingLoading) return
    let mounted = true
    const timer = setTimeout(async () => {
      try {
        const methods = await api.estimateShipping(subtotal)
        if (!mounted) return
        setShippingMethods(methods)
      } catch {
        // keep last estimate
      }
    }, 300)
    return () => {
      mounted = false
      clearTimeout(timer)
    }
  }, [subtotal, shippingLoading])

  // ---------- Meta Pixel: InitiateCheckout (once per checkout visit) ----------
  useEffect(() => {
    const contents = cart.map((item) => ({ id: String(item.product.id), quantity: item.quantity }))
    trackInitiateCheckout({
      value: subtotal,
      numItems: cart.reduce((sum, item) => sum + item.quantity, 0),
      contents,
    })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  // ---------- Load saved addresses ----------
  useEffect(() => {
    let mounted = true
    const loadAddresses = async () => {
      if (!authUser) {
        setSavedAddresses([])
        setSelectedAddressId('new')
        return
      }
      setAddressesLoading(true)
      try {
        const list = await api.getMyAddresses()
        if (!mounted) return
        setSavedAddresses(list)
        const defaultAddress = list.find((item) => item.isDefault) || list[0]
        if (defaultAddress) {
          setSelectedAddressId(defaultAddress.id)
          setForm({
            name: defaultAddress.recipientName,
            phone: defaultAddress.phone,
            alternativePhone: defaultAddress.alternativePhone || '',
            email: defaultAddress.email || '',
            country: defaultAddress.country || 'Bangladesh',
            division: defaultAddress.division || '',
            district: defaultAddress.district || '',
            upazila: defaultAddress.upazila || '',
            area: defaultAddress.area || '',
            address: defaultAddress.address,
            apartment: defaultAddress.apartment || '',
            postalCode: defaultAddress.postalCode || '',
          })
        }
      } catch {
        if (!mounted) return
        setSavedAddresses([])
      } finally {
        if (mounted) setAddressesLoading(false)
      }
    }
    loadAddresses()
    return () => {
      mounted = false
    }
  }, [authUser])

  const pickSavedAddress = (id: number) => {
    setSelectedAddressId(id)
    const picked = savedAddresses.find((item) => item.id === id)
    if (!picked) return
    setForm({
      name: picked.recipientName,
      phone: picked.phone,
      alternativePhone: picked.alternativePhone || '',
      email: picked.email || '',
      country: picked.country || 'Bangladesh',
      division: picked.division || '',
      district: picked.district || '',
      upazila: picked.upazila || '',
      area: picked.area || '',
      address: picked.address,
      apartment: picked.apartment || '',
      postalCode: picked.postalCode || '',
    })
  }

  // ---------- Coupon ----------
  const applyCoupon = async () => {
    setCouponError('')
    setCouponLoading(true)
    try {
      const result = await api.validateCoupon(couponCode.trim(), subtotal)
      setCouponApplied({ code: couponCode.trim().toUpperCase(), discount: Math.round(result.discount) })
      setCouponCode('')
    } catch (error) {
      setCouponApplied(null)
      setCouponError(error instanceof Error ? error.message : 'কুপনটি সঠিক নয়। / Invalid coupon')
    } finally {
      setCouponLoading(false)
    }
  }

  // ---------- Validation ----------
  const validate = (): string | null => {
    if (!form.name.trim()) return 'আপনার সম্পূর্ণ নাম লিখুন। / Please enter your full name'
    const phone = normalizeBdPhone(form.phone)
    if (!BD_PHONE_REGEX.test(phone)) return 'সঠিক বাংলাদেশি ফোন নম্বর লিখুন (যেমন: 01712345678)। / Please enter a valid Bangladeshi phone number (e.g. 01712345678)'
    if (form.alternativePhone && !BD_PHONE_REGEX.test(normalizeBdPhone(form.alternativePhone))) {
      return 'বিকল্প ফোন নম্বরটি সঠিক নয়। / Alternative phone number is invalid'
    }
    if (form.email && !/^\S+@\S+\.\S+$/.test(form.email)) return 'সঠিক ইমেইল ঠিকানা লিখুন। / Please enter a valid email address'
    if (!form.address.trim()) return 'আপনার ডেলিভারি ঠিকানা লিখুন। / Please enter your delivery address'
    if (selectedShippingMethod && !selectedShippingMethod.codAvailable && paymentMethod === 'cod') {
      return `${displayName(selectedShippingMethod.name)} ক্যাশ অন ডেলিভারি সমর্থন করে না। / ${displayName(selectedShippingMethod.name)} does not support Cash on Delivery`
    }
    if (requiresTransactionId && !transactionId.trim()) {
      return 'ট্রানজেকশন আইডি লিখুন। / Please enter the Transaction ID'
    }
    if (!agreeTerms) return 'অনুগ্রহ করে শর্তাবলীতে সম্মত হন। / Please agree to the terms and conditions'
    return null
  }

  const onSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!cart.length) return
    setSubmitError('')

    const error = validate()
    if (error) {
      setSubmitError(error)
      return
    }

    const addressInput = {
      name: form.name.trim(),
      phone: normalizeBdPhone(form.phone),
      alternativePhone: form.alternativePhone ? normalizeBdPhone(form.alternativePhone) : undefined,
      email: form.email.trim() || undefined,
      country: form.country.trim() || undefined,
      division: form.division.trim() || undefined,
      district: form.district.trim() || undefined,
      upazila: form.upazila.trim() || undefined,
      area: form.area.trim() || undefined,
      address: form.address.trim(),
      apartment: form.apartment.trim() || undefined,
      postalCode: form.postalCode.trim() || undefined,
    }

    const result = await dispatch(
      placeOrder({
        ...addressInput,
        shippingArea: form.district.trim() || form.division.trim() || 'Dhaka',
        shippingCost,
        shippingMethodId: shippingMethodId ?? undefined,
        couponCode: couponApplied?.code,
        orderNote: orderNote.trim() || undefined,
        paymentMethod,
        transactionId: transactionId.trim() || undefined,
        taxAmount: tax,
        items: cart.map((item) => ({
          productId: item.product.id,
          variantId: item.variantId,
          quantity: item.quantity,
          size: item.size,
          color: item.color,
        })),
      }),
    )

    if (placeOrder.fulfilled.match(result)) {
      dispatch(clearCart())
      const order: Order | undefined = result.payload.order
      if (result.payload.auth) {
        dispatch(setAuthSession(result.payload.auth))
      }
      if (order) {
        navigate('/order/success', { state: { order } })
      } else {
        navigate('/order/success', { state: { orderId: result.payload.orderId, message: result.payload.message } })
      }
    }
  }

  const inputClass = 'w-full border-b border-input/40 bg-transparent py-2 text-sm transition focus:border-primary-foreground'
  const labelClass = 'mb-2 block text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant'

  return (
    <main className="mx-auto max-w-7xl px-6 py-10 lg:px-8">
      <SEO
        title="Checkout"
        description="Complete your order at Mama Bazar. Secure checkout with multiple payment options."
        url="/checkout"
      />
      {/* Checkout notices */}
      {notices.length > 0 && (
        <div className="mb-8 space-y-2">
          {notices.map((notice) => (
            <div
              className="flex items-center gap-3 rounded-lg border px-4 py-3 text-sm font-medium"
              key={notice.id}
              style={{ backgroundColor: notice.backgroundColor, color: notice.textColor }}
            >
              <span className="text-base">{NOTICE_ICONS[notice.icon] || 'ℹ️'}</span>
              {notice.text}
            </div>
          ))}
        </div>
      )}

      {/* Login Prompt for Guest Users */}
      {!authUser && (
        <div className="mb-8 rounded-2xl border border-primary/20 bg-gradient-to-r from-primary/5 to-primary/10 p-6">
          <div className="flex flex-col items-center gap-6 md:flex-row md:items-start md:justify-between">
            <div className="text-center md:text-left">
              <h2 className="font-headline text-xl font-bold text-slate-900">আগে থেকে অ্যাকাউন্ট আছে? / Already have an account?</h2>
              <p className="mt-1 text-sm text-slate-600">দ্রুত চেকআউটের জন্য লগইন করুন। / Login for a faster checkout experience.</p>
              <div className="mt-3 flex flex-wrap items-center justify-center gap-3 text-xs text-slate-500 md:justify-start">
                <span className="flex items-center gap-1">
                  <CheckCircle size={14} className="text-primary-foreground" />
                  আপনার তথ্য স্বয়ংক্রিয়ভাবে পূরণ হবে / Your details will be auto-filled
                </span>
                <span className="flex items-center gap-1">
                  <CheckCircle size={14} className="text-primary-foreground" />
                  আপনার আগের অর্ডার দেখুন / View your previous orders
                </span>
                <span className="flex items-center gap-1">
                  <CheckCircle size={14} className="text-primary-foreground" />
                  আপনার ডেলিভারি ঠিকানা সংরক্ষণ করুন / Save your delivery address
                </span>
              </div>
            </div>
            <div className="flex flex-col gap-3 sm:flex-row">
              <Link
                className="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-primary-foreground shadow-lg shadow-primary/25 transition hover:bg-primary/90 hover:shadow-xl"
                state={{ from: '/checkout' }}
                to="/auth/login"
              >
                <LogIn size={16} />
                লগইন / রেজিস্টার / Login / Register
              </Link>
              <button
                className="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
                onClick={() => {
                  // Scroll to the checkout form
                  document.getElementById('checkout-form')?.scrollIntoView({ behavior: 'smooth' })
                }}
                type="button"
              >
                Continue as Guest
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Logged-in user indicator */}
      {authUser && (
        <div className="mb-6 rounded-xl border border-success/30 bg-success/5 p-4">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-success/20">
              <CheckCircle size={20} className="text-success" />
            </div>
            <div>
              <p className="text-sm font-semibold text-slate-900">স্বাগতম, {authUser.name}! / Welcome back, {authUser.name}!</p>
              <p className="text-xs text-slate-500">আপনার তথ্য আপনার অ্যাকাউন্ট থেকে স্বয়ংক্রিয়ভাবে পূরণ হবে। / Your information will be auto-filled from your account.</p>
            </div>
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 gap-10 lg:grid-cols-12">
        <form className="space-y-10 lg:col-span-7" id="checkout-form" onSubmit={onSubmit}>
          <div>
            <h1 className="font-headline text-3xl font-extrabold tracking-tight">চেকআউট / Checkout</h1>
            <p className="mt-1 text-xs font-semibold uppercase tracking-[0.2em] text-on-surface-variant">{cart.length} টি আইটেম আপনার ব্যাগে / {cart.length} items in your bag</p>
          </div>

          {/* ==================== 1. Address ==================== */}
          <section>
            <h2 className="mb-4 flex items-center gap-2 font-headline text-lg font-bold">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-tertiary text-xs text-white">1</span>
              ডেলিভারি ঠিকানা / Delivery Address
            </h2>

            {authUser && (
              <div className="mb-4">
                <label className={labelClass}>সংরক্ষিত ঠিকানা / Saved Address</label>
                <select
                  className="w-full border border-outline-variant/40 bg-transparent px-3 py-2.5 text-sm "
                  disabled={addressesLoading}
                  onChange={(event) => {
                    const value = event.target.value
                    if (value === 'new') {
                      setSelectedAddressId('new')
                      return
                    }
                    pickSavedAddress(Number(value))
                  }}
                  value={selectedAddressId === 'new' ? 'new' : String(selectedAddressId)}
                >
                  <option value="new">নতুন ঠিকানা ব্যবহার করুন / Use a New Address</option>
                  {savedAddresses.map((addr) => (
                    <option key={addr.id} value={String(addr.id)}>
                      {addr.recipientName} - {addr.area || addr.shippingArea || addr.district || ''} {addr.isDefault ? '(ডিফল্ট / Default)' : ''}
                    </option>
                  ))}
                </select>
              </div>
            )}

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div className="md:col-span-2">
                <label className={labelClass}>নাম / Full Name</label>
                <input className={inputClass} onChange={(event) => set({ name: event.target.value })} placeholder="আপনার নাম লিখুন / Enter your name" required value={form.name} />
              </div>
              <div>
                <label className={labelClass}>ফোন নম্বর / Phone Number</label>
                <input className={inputClass} onChange={(event) => set({ phone: event.target.value })} placeholder="01XXXXXXXXX" required value={form.phone} />
              </div>
              <div>
                <label className={labelClass}>বিকল্প ফোন নম্বর / Alternative Phone (Optional)</label>
                <input className={inputClass} onChange={(event) => set({ alternativePhone: event.target.value })} placeholder="01XXXXXXXXX" value={form.alternativePhone} />
              </div>
              <div className="md:col-span-2">
                <label className={labelClass}>ইমেইল / Email (Optional)</label>
                <input className={inputClass} onChange={(event) => set({ email: event.target.value })} type="email" value={form.email} />
              </div>
              <div>
                <label className={labelClass}>দেশ / Country</label>
                <input className={inputClass} onChange={(event) => set({ country: event.target.value })} value={form.country} />
              </div>
              <div>
                <label className={labelClass}>বিভাগ / Division</label>
                <input className={inputClass} onChange={(event) => set({ division: event.target.value })} placeholder="Dhaka" value={form.division} />
              </div>
              <div>
                <label className={labelClass}>জেলা / District</label>
                <input className={inputClass} onChange={(event) => set({ district: event.target.value })} placeholder="Dhaka" value={form.district} />
              </div>
              <div>
                <label className={labelClass}>উপজেলা / Upazila / Thana</label>
                <input className={inputClass} onChange={(event) => set({ upazila: event.target.value })} placeholder="Gulshan" value={form.upazila} />
              </div>
              <div>
                <label className={labelClass}>এলাকা / Area / Road</label>
                <input className={inputClass} onChange={(event) => set({ area: event.target.value })} placeholder="Gulshan 1" value={form.area} />
              </div>
              <div>
                <label className={labelClass}>পোস্টাল কোড / Postal Code (Optional)</label>
                <input className={inputClass} onChange={(event) => set({ postalCode: event.target.value })} placeholder="1212" value={form.postalCode} />
              </div>
              <div className="md:col-span-2">
                <label className={labelClass}>বাড়ি / রাস্তার ঠিকানা / House / Street Address</label>
                <textarea className="w-full border border-outline-variant/40 bg-transparent px-3 py-2.5 text-sm  focus:border-tertiary" onChange={(event) => set({ address: event.target.value })} placeholder="ঠিকানা লিখুন / Enter your address" required rows={2} value={form.address} />
              </div>
              <div className="md:col-span-2">
                <label className={labelClass}>অ্যাপার্টমেন্ট / ফ্লোর / Apartment / Floor (Optional)</label>
                <input className={inputClass} onChange={(event) => set({ apartment: event.target.value })} value={form.apartment} />
              </div>
            </div>
          </section>

          {/* ==================== 2. Shipping Method ==================== */}
          <section>
            <h2 className="mb-4 flex items-center gap-2 font-headline text-lg font-bold">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-tertiary text-xs text-white">2</span>
              ডেলিভারি পদ্ধতি / Shipping Method
            </h2>
            {shippingLoading ? (
              <p className="text-sm text-on-surface-variant">শিপিং অপশন লোড হচ্ছে... / Loading shipping options...</p>
            ) : (
              <div className="space-y-3">
                {shippingMethods.map((method) => {
                  const free = method.estimatedCost === 0
                  return (
                    <button
                      className={`flex w-full items-center justify-between gap-4 border px-4 py-3.5 text-left transition-colors ${
                        shippingMethodId === method.id ? 'border-tertiary bg-tertiary/5' : 'border-outline-variant/30 hover:border-outline-variant'
                      }`}
                      key={method.id}
                      onClick={() => setShippingMethodId(method.id)}
                      type="button"
                    >
                      <div className="min-w-0">
                        <p className="text-sm font-semibold">{displayName(method.name)}</p>
                        <p className="mt-0.5 text-xs text-on-surface-variant">
                          {method.estimatedDelivery ? `ডেলিভারি: / Delivery: ${method.estimatedDelivery}` : ''}
                          {method.codAvailable ? ' · ক্যাশ অন ডেলিভারি উপলব্ধ / COD available' : ''}
                        </p>
                        {method.description && <p className="mt-0.5 text-xs text-on-surface-variant">{method.description}</p>}
                      </div>
                      <span className="shrink-0 text-sm font-bold">
                        {free ? <span className="text-success">ফ্রি / FREE</span> : currency(method.estimatedCost ?? 0)}
                      </span>
                    </button>
                  )
                })}
                {shippingMethods.length === 0 && <p className="text-sm text-on-surface-variant">কোনো শিপিং অপশন উপলব্ধ নেই। / No shipping options available.</p>}
              </div>
            )}
          </section>

          {/* ==================== 3. Payment Method ==================== */}
          <section>
            <h2 className="mb-4 flex items-center gap-2 font-headline text-lg font-bold">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-tertiary text-xs text-white">3</span>
              পেমেন্ট পদ্ধতি / Payment Method
            </h2>
            <div className="space-y-3">
              {paymentMethods.map((method) => {
                const active = paymentMethod === method.code
                const merchantNumber = method.config?.merchantNumber
                const bankName = method.config?.bankName
                const accountName = method.config?.accountName
                const accountNumber = method.config?.accountNumber
                const branch = method.config?.branch

                return (
                  <div key={method.code} className="rounded-xl border transition-all duration-200">
                    {/* Payment method header */}
                    <button
                      className={`flex w-full items-center gap-4 px-4 py-4 text-left transition-colors ${
                        active ? 'border-primary bg-primary/5' : 'border-transparent hover:bg-surface-variant/30'
                      }`}
                      onClick={() => {
                        setPaymentMethod(method.code)
                        setTransactionId('')
                        setSubmitError('')
                      }}
                      type="button"
                    >
                      {/* Radio indicator */}
                      <div className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 ${
                        active ? 'border-primary' : 'border-slate-300'
                      }`}>
                        {active && <div className="h-2.5 w-2.5 rounded-full bg-primary" />}
                      </div>
                      
                      {/* Payment icon */}
                      <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-surface-variant/20 text-lg font-bold">
                        {PAYMENT_ICONS[method.code] || '💳'}
                      </span>
                      
                      {/* Payment name and description */}
                      <div className="min-w-0 flex-1">
                        <p className="text-sm font-semibold">{displayName(method.name)}</p>
                        {method.code === 'cod' && (
                          <p className="mt-0.5 text-xs text-on-surface-variant">পণ্য হাতে পাওয়ার সময় নগদ অর্থ প্রদান করুন। / Pay in cash when your order is delivered.</p>
                        )}
                        {method.type === 'mobile_banking' && merchantNumber && (
                          <p className="mt-0.5 text-xs text-on-surface-variant">{displayName(method.name)} ব্যবহার করে নিরাপদে পেমেন্ট করুন / Pay securely using {displayName(method.name)}</p>
                        )}
                        {method.type === 'bank' && bankName && (
                          <p className="mt-0.5 text-xs text-on-surface-variant">{bankName} এ ট্রান্সফার করুন / Transfer to {bankName}</p>
                        )}
                      </div>
                      
                      {/* Checkmark for selected */}
                      {active && (
                        <CheckCircle size={20} className="shrink-0 text-primary-foreground" />
                      )}
                    </button>

                    {/* Expanded payment details */}
                    {active && (
                      <div className="border-t border-outline-variant/20 px-4 py-5">
                        {/* COD instructions */}
                        {method.code === 'cod' && (
                          <div className="rounded-lg bg-surface-variant/30 p-4">
                            <p className="text-sm font-medium text-slate-700">
                              বাংলা: ডেলিভারি পাওয়ার সময় ক্যাশ পেমেন্ট করতে হবে।
                            </p>
                            <p className="mt-2 text-sm text-slate-600">
                              English: Pay in cash when your order is delivered.
                            </p>
                          </div>
                        )}

                        {/* Mobile banking instructions */}
                        {method.type === 'mobile_banking' && merchantNumber && (
                          <div className="space-y-4">
                            {/* Payment number with copy */}
                            <div className="rounded-lg bg-surface-variant/30 p-4">
                              <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">
                                পেমেন্ট নম্বর / Payment Number
                              </p>
                              <div className="mt-2 flex items-center gap-3">
                                <span className="text-xl font-bold text-slate-900">{merchantNumber}</span>
                                <button
                                  className="inline-flex items-center gap-1.5 rounded-md bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary-foreground transition hover:bg-primary/20"
                                  onClick={() => copyToClipboard(merchantNumber)}
                                  type="button"
                                >
                                  {copiedNumber === merchantNumber ? (
                                    <>
                                      <Check size={14} />
                                      কপি হয়েছে! / Copied!
                                    </>
                                  ) : (
                                    <>
                                      <Copy size={14} />
                                      কপি / Copy
                                    </>
                                  )}
                                </button>
                              </div>
                              {method.config?.merchantName && (
                                <p className="mt-2 text-sm text-slate-600">
                                  অ্যাকাউন্টের নাম: / Account Name: {method.config.merchantName}
                                </p>
                              )}
                            </div>

                            {/* Bilingual instructions */}
                            <div className="space-y-3">
                              <div>
                                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">
                                  বাংলা নির্দেশনা
                                </p>
                                <p className="mt-1 text-sm text-slate-700">
                                  ১. উপরের {method.name} নম্বরে Send Money/Payment করুন।<br />
                                  ২. পেমেন্ট সম্পন্ন করার পর Transaction ID নিচের ঘরে দিন।<br />
                                  ৩. তারপর অর্ডার নিশ্চিত করুন (Place Order)।
                                </p>
                              </div>
                              <div>
                                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">
                                  ইংরেজি নির্দেশনা / English Instructions
                                </p>
                                <p className="mt-1 text-sm text-slate-700">
                                  1. Send the required payment amount to the {displayName(method.name)} number above.<br />
                                  2. After completing the payment, enter the Transaction ID below.<br />
                                  3. Then confirm your order.
                                </p>
                              </div>
                            </div>

                            {/* Transaction ID input */}
                            <div>
                              <label className={labelClass}>ট্রানজেকশন আইডি / Transaction ID</label>
                              <input
                                className={inputClass}
                                onChange={(event) => setTransactionId(event.target.value)}
                                placeholder="ট্রানজেকশন আইডি লিখুন / Enter Transaction ID"
                                required
                                value={transactionId}
                              />
                            </div>
                          </div>
                        )}

                        {/* Bank transfer instructions */}
                        {method.type === 'bank' && (
                          <div className="space-y-4">
                            {/* Bank information */}
                            <div className="rounded-lg bg-surface-variant/30 p-4">
                              <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">
                                ব্যাংক তথ্য / Bank Information
                              </p>
                              <div className="mt-3 space-y-2">
                                {bankName && (
                                  <div className="flex justify-between">
                                    <span className="text-sm text-slate-600">ব্যাংকের নাম: / Bank Name:</span>
                                    <span className="text-sm font-semibold text-slate-900">{bankName}</span>
                                  </div>
                                )}
                                {accountName && (
                                  <div className="flex justify-between">
                                    <span className="text-sm text-slate-600">অ্যাকাউন্টের নাম: / Account Name:</span>
                                    <span className="text-sm font-semibold text-slate-900">{accountName}</span>
                                  </div>
                                )}
                                {accountNumber && (
                                  <div className="flex justify-between">
                                    <span className="text-sm text-slate-600">অ্যাকাউন্ট নম্বর: / Account Number:</span>
                                    <span className="text-sm font-semibold text-slate-900">{accountNumber}</span>
                                  </div>
                                )}
                                {branch && (
                                  <div className="flex justify-between">
                                    <span className="text-sm text-slate-600">শাখা: / Branch:</span>
                                    <span className="text-sm font-semibold text-slate-900">{branch}</span>
                                  </div>
                                )}
                              </div>
                            </div>

                            {/* Bilingual instructions */}
                            <div className="space-y-3">
                              <div>
                                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">
                                  বাংলা নির্দেশনা
                                </p>
                                <p className="mt-1 text-sm text-slate-700">
                                  ১. উপরের ব্যাংক অ্যাকাউন্টে নির্ধারিত পরিমাণ টাকা ট্রান্সফার করুন।<br />
                                  ২. ট্রান্সফার সম্পন্ন করার পর Transaction ID/Reference Number দিন।<br />
                                  ৩. তারপর অর্ডার কনফার্ম করুন।
                                </p>
                              </div>
                              <div>
                                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">
                                  ইংরেজি নির্দেশনা / English Instructions
                                </p>
                                <p className="mt-1 text-sm text-slate-700">
                                  1. Transfer the required amount to the bank account above.<br />
                                  2. After completing the transfer, enter the Transaction ID/Reference Number.<br />
                                  3. Confirm your order.
                                </p>
                              </div>
                            </div>

                            {/* Transaction ID input */}
                            <div>
                              <label className={labelClass}>ট্রানজেকশন আইডি / রেফারেন্স / Transaction ID / Reference</label>
                              <input
                                className={inputClass}
                                onChange={(event) => setTransactionId(event.target.value)}
                                placeholder="ট্রানজেকশন আইডি বা রেফারেন্স নম্বর লিখুন / Enter Transaction ID or Reference Number"
                                required
                                value={transactionId}
                              />
                            </div>
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                )
              })}
              {paymentMethods.length === 0 && (
                <p className="text-sm text-on-surface-variant">কোনো পেমেন্ট পদ্ধতি উপলব্ধ নেই। / No payment methods available.</p>
              )}
            </div>
          </section>

          {/* ==================== 4. Coupon + Note ==================== */}
          <section>
            <h2 className="mb-4 flex items-center gap-2 font-headline text-lg font-bold">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-tertiary text-xs text-white">4</span>
              কুপন ও নোট / Coupon & Notes
            </h2>
            <div className="space-y-4">
              <div>
                {couponApplied ? (
                  <div className="flex items-center justify-between rounded-md bg-success/10 px-4 py-3 text-sm">
                    <span className="font-semibold text-success">
                      কুপন {couponApplied.code} প্রয়োগ হয়েছে — {currency(couponApplied.discount)} ছাড় / Coupon {couponApplied.code} applied - {currency(couponApplied.discount)} off
                    </span>
                    <button className="text-xs font-bold uppercase tracking-widest" onClick={() => setCouponApplied(null)} type="button">
                      মুছে ফেলুন / Remove
                    </button>
                  </div>
                ) : (
                  <div className="flex gap-2">
                    <input
                      className="flex-1 border-b border-input/40 bg-transparent px-1 py-2 text-sm uppercase transition focus:border-primary-foreground"
                      onChange={(event) => {
                        setCouponCode(event.target.value)
                        setCouponError('')
                      }}
                      placeholder="কুপন কোড লিখুন / Enter Coupon Code"
                      value={couponCode}
                    />
                    <button
                      className="border border-primary px-5 py-2 text-xs font-bold uppercase tracking-[0.2em] text-primary-foreground hover:bg-primary hover:text-primary-foreground disabled:opacity-50"
                      disabled={couponLoading || !couponCode.trim()}
                      onClick={applyCoupon}
                      type="button"
                    >
                      {couponLoading ? '...' : 'প্রয়োগ করুন / Apply'}
                    </button>
                  </div>
                )}
                {couponError && <p className="mt-1 text-xs text-destructive">{couponError}</p>}
              </div>
              <div>
                <label className={labelClass}>অর্ডার নোট (ঐচ্ছিক) / Order Note (Optional)</label>
                <textarea className="w-full border border-outline-variant/40 bg-transparent px-3 py-2.5 text-sm  focus:border-tertiary" onChange={(event) => setOrderNote(event.target.value)} placeholder="অর্ডার সম্পর্কে অতিরিক্ত তথ্য / Additional order notes" rows={2} value={orderNote} />
              </div>
            </div>
          </section>

          {/* ==================== 5. Review ==================== */}
          <section className="border-t border-outline-variant/20 pt-6">
            <h2 className="mb-4 flex items-center gap-2 font-headline text-lg font-bold">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-tertiary text-xs text-white">5</span>
              পর্যালোচনা ও নিশ্চিতকরণ / Review & Confirm
            </h2>

            {/* Login reminder for guest users */}
            {!authUser && (
              <div className="mb-4 rounded-xl border border-primary/20 bg-primary/5 p-4">
                <div className="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                  <div className="text-center sm:text-left">
                    <p className="text-sm font-semibold text-slate-900">অ্যাকাউন্ট আছে? / Have an account?</p>
                    <p className="text-xs text-slate-500">আপনার অর্ডার অ্যাকাউন্টে সংরক্ষণ করতে লগইন করুন। / Login to save your order to your account.</p>
                  </div>
                  <Link
                    className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-bold text-primary-foreground transition hover:bg-primary/90"
                    state={{ from: '/checkout' }}
                    to="/auth/login"
                  >
                    <LogIn size={14} />
                    লগইন / Login
                  </Link>
                </div>
              </div>
            )}

            <label className="flex items-start gap-3 text-sm text-on-surface-variant">
              <input checked={agreeTerms} className="mt-0.5" onChange={(event) => setAgreeTerms(event.target.checked)} type="checkbox" />
              <span>
                আমি <span className="font-semibold text-tertiary">Terms &amp; Conditions</span>-এ সম্মত এবং বুঝতে পারছি যে নিশ্চিত করার পর আমার অর্ডারটি প্রক্রিয়াজাত করা হবে। / I agree to the{' '}
                <span className="font-semibold text-tertiary">Terms &amp; Conditions</span> and understand that my order will be processed after confirmation{' '}
                {requiresTransactionId && 'and payment verification'}.
              </span>
            </label>

            {submitError && <p className="mt-3 rounded-md bg-destructive/10 px-4 py-3 text-sm text-destructive">{submitError}</p>}

            <button
              className="mt-6 inline-flex w-full items-center justify-center gap-2 bg-accent py-4 text-xs font-bold uppercase tracking-[0.25em] text-accent-foreground transition hover:bg-accent-600 disabled:opacity-70"
              disabled={creating || !cart.length || shippingLoading}
              type="submit"
            >
              {creating ? (
                <>
                  <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                  অর্ডার প্রক্রিয়াজাত হচ্ছে... / Placing Order...
                </>
              ) : (
                `অর্ডার নিশ্চিত করুন / Place Order • ${currency(total)}`
              )}
            </button>
          </section>
        </form>

        {/* ==================== Order Summary ==================== */}
        <aside className="lg:col-span-5">
          <div className="sticky top-24 space-y-6">
            <div className="border border-outline-variant/20 bg-white p-6 shadow-panel">
              <h2 className="border-b border-outline-variant/20 pb-4 font-headline text-xl font-bold tracking-tight">অর্ডার সারাংশ / Order Summary</h2>

              <div className="mt-5 max-h-72 space-y-4 overflow-y-auto">
                {cart.map((item) => (
                  <div className="flex gap-3" key={item.key}>
                    <img alt={item.product.title} className="h-16 w-14 shrink-0 object-cover" src={item.image || item.product.images[0]} />
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-semibold">{item.product.title}</p>
                      <p className="text-xs text-on-surface-variant">
                        {item.quantity} × {currency(Number(item.product.price))}
                        {item.size ? ` · সাইজ: / Size: ${item.size}` : ''}
                        {item.color ? ` · রং: / Color: ${item.color}` : ''}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-semibold">{currency(Number(item.product.price) * item.quantity)}</p>
                      <button
                        className="mt-1 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant hover:text-destructive"
                        onClick={() => dispatch(removeFromCart(item.key))}
                        type="button"
                      >
                        মুছে ফেলুন / Remove
                      </button>
                    </div>
                  </div>
                ))}
                {!cart.length && <p className="text-sm text-on-surface-variant">আপনার ব্যাগ খালি। / Your bag is empty.</p>}
              </div>

              {/* Tax option — reflects the single tax rate configured in admin */}
              <div className="mt-5 rounded-lg border border-outline-variant/20 bg-surface-variant/5 p-4">
                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">ট্যাক্স / Tax</p>
                <div className="mt-2" role="radiogroup" aria-label="Tax option">
                  <button
                    aria-checked="true"
                    className="flex w-full items-center gap-3 border border-tertiary bg-tertiary/5 px-4 py-3 text-left transition-colors"
                    role="radio"
                    type="button"
                  >
                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-tertiary">
                      <span className="h-2.5 w-2.5 rounded-full bg-tertiary" />
                    </span>
                    <span className="min-w-0">
                      <span className="block text-sm font-semibold">{taxRate > 0 ? `${taxRate}% কর / ${taxRate}% Tax` : '০% — কর নেই / 0% — No Tax'}</span>
                      <span className="block text-xs text-on-surface-variant">
                        {taxRate > 0 ? 'আপনার অর্ডার মোটের উপর প্রয়োগ করা হয়েছে। / Applied based on your order total.' : 'এই অর্ডারে কোনো কর প্রযোজ্য নয়। / No tax is applied to this order.'}
                      </span>
                    </span>
                    <span className="ml-auto shrink-0 text-sm font-bold">{currency(tax)}</span>
                  </button>
                </div>
              </div>

              <div className="mt-6 space-y-3 border-t border-outline-variant/20 pt-4 text-sm">
                <div className="flex justify-between">
                  <span className="text-on-surface-variant">সাবটোটাল / Subtotal</span>
                  <span className="font-semibold">{currency(subtotal)}</span>
                </div>
                {discount > 0 && (
                  <div className="flex justify-between text-success">
                    <span>কুপন ডিসকাউন্ট / Coupon Discount</span>
                    <span className="font-semibold">-{currency(discount)}</span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span className="text-on-surface-variant">ডেলিভারি চার্জ / Shipping</span>
                  <span className="font-semibold">{shippingCost === 0 ? 'ফ্রি / FREE' : currency(shippingCost)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-on-surface-variant">ট্যাক্স / Tax{taxRate > 0 ? ` (${taxRate}%)` : ''}</span>
                  <span className="font-semibold">{currency(tax)}</span>
                </div>
                <div className="flex items-end justify-between border-t border-outline-variant/20 pt-4">
                  <span className="font-headline text-lg font-bold">মোট / Total</span>
                  <span className="font-headline text-2xl font-extrabold">{currency(total)}</span>
                </div>
                {selectedPaymentMethod?.config?.extraFeePercent ? (
                  <p className="text-xs text-on-surface-variant">দ্রষ্টব্য: পেমেন্ট গেটওয়ে ফি প্রযোজ্য হতে পারে। / Note: payment gateway fees may apply.</p>
                ) : null}
              </div>
            </div>

            <p className="text-center text-xs text-on-surface-variant">
              সাহায্য দরকার? আমাদের কল করুন / Need help? Call us at <span className="font-semibold">01711111111</span>
            </p>
          </div>
        </aside>
      </div>
    </main>
  )
}

export default CheckoutPage
