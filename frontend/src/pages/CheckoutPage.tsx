import { useEffect, useMemo, useState } from 'react'
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
      setCouponError(error instanceof Error ? error.message : 'Invalid coupon')
    } finally {
      setCouponLoading(false)
    }
  }

  // ---------- Validation ----------
  const validate = (): string | null => {
    if (!form.name.trim()) return 'Please enter your full name'
    const phone = normalizeBdPhone(form.phone)
    if (!BD_PHONE_REGEX.test(phone)) return 'Please enter a valid Bangladeshi phone number (e.g. 01712345678)'
    if (form.alternativePhone && !BD_PHONE_REGEX.test(normalizeBdPhone(form.alternativePhone))) {
      return 'Alternative phone number is invalid'
    }
    if (form.email && !/^\S+@\S+\.\S+$/.test(form.email)) return 'Please enter a valid email address'
    if (!form.address.trim()) return 'Please enter your delivery address'
    if (selectedShippingMethod && !selectedShippingMethod.codAvailable && paymentMethod === 'cod') {
      return `${selectedShippingMethod.name} does not support Cash on Delivery`
    }
    if (requiresTransactionId && !transactionId.trim()) {
      return 'Please enter the Transaction ID'
    }
    if (!agreeTerms) return 'Please agree to the terms and conditions'
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
              <h2 className="font-headline text-xl font-bold text-slate-900">Already have an account?</h2>
              <p className="mt-1 text-sm text-slate-600">Login for a faster checkout experience.</p>
              <div className="mt-3 flex flex-wrap items-center justify-center gap-3 text-xs text-slate-500 md:justify-start">
                <span className="flex items-center gap-1">
                  <CheckCircle size={14} className="text-primary-foreground" />
                  Your details will be auto-filled
                </span>
                <span className="flex items-center gap-1">
                  <CheckCircle size={14} className="text-primary-foreground" />
                  View your previous orders
                </span>
                <span className="flex items-center gap-1">
                  <CheckCircle size={14} className="text-primary-foreground" />
                  Save your delivery address
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
                Login / Register
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
              <p className="text-sm font-semibold text-slate-900">Welcome back, {authUser.name}!</p>
              <p className="text-xs text-slate-500">Your information will be auto-filled from your account.</p>
            </div>
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 gap-10 lg:grid-cols-12">
        <form className="space-y-10 lg:col-span-7" id="checkout-form" onSubmit={onSubmit}>
          <div>
            <h1 className="font-headline text-3xl font-extrabold tracking-tight">Checkout</h1>
            <p className="mt-1 text-xs font-semibold uppercase tracking-[0.2em] text-on-surface-variant">{cart.length} items in your bag</p>
          </div>

          {/* ==================== 1. Address ==================== */}
          <section>
            <h2 className="mb-4 flex items-center gap-2 font-headline text-lg font-bold">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-tertiary text-xs text-white">1</span>
              Delivery Address
            </h2>

            {authUser && (
              <div className="mb-4">
                <label className={labelClass}>Saved Address</label>
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
                  <option value="new">Use a new address</option>
                  {savedAddresses.map((addr) => (
                    <option key={addr.id} value={String(addr.id)}>
                      {addr.recipientName} - {addr.area || addr.shippingArea || addr.district || ''} {addr.isDefault ? '(Default)' : ''}
                    </option>
                  ))}
                </select>
              </div>
            )}

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div className="md:col-span-2">
                <label className={labelClass}>Full Name</label>
                <input className={inputClass} onChange={(event) => set({ name: event.target.value })} required value={form.name} />
              </div>
              <div>
                <label className={labelClass}>Phone</label>
                <input className={inputClass} onChange={(event) => set({ phone: event.target.value })} placeholder="01XXXXXXXXX" required value={form.phone} />
              </div>
              <div>
                <label className={labelClass}>Alternative Phone (optional)</label>
                <input className={inputClass} onChange={(event) => set({ alternativePhone: event.target.value })} placeholder="01XXXXXXXXX" value={form.alternativePhone} />
              </div>
              <div className="md:col-span-2">
                <label className={labelClass}>Email (optional, for order updates)</label>
                <input className={inputClass} onChange={(event) => set({ email: event.target.value })} type="email" value={form.email} />
              </div>
              <div>
                <label className={labelClass}>Country</label>
                <input className={inputClass} onChange={(event) => set({ country: event.target.value })} value={form.country} />
              </div>
              <div>
                <label className={labelClass}>Division</label>
                <input className={inputClass} onChange={(event) => set({ division: event.target.value })} placeholder="Dhaka" value={form.division} />
              </div>
              <div>
                <label className={labelClass}>District</label>
                <input className={inputClass} onChange={(event) => set({ district: event.target.value })} placeholder="Dhaka" value={form.district} />
              </div>
              <div>
                <label className={labelClass}>Upazila / Thana</label>
                <input className={inputClass} onChange={(event) => set({ upazila: event.target.value })} placeholder="Gulshan" value={form.upazila} />
              </div>
              <div>
                <label className={labelClass}>Area / Road</label>
                <input className={inputClass} onChange={(event) => set({ area: event.target.value })} placeholder="Gulshan 1" value={form.area} />
              </div>
              <div>
                <label className={labelClass}>Postal Code (optional)</label>
                <input className={inputClass} onChange={(event) => set({ postalCode: event.target.value })} placeholder="1212" value={form.postalCode} />
              </div>
              <div className="md:col-span-2">
                <label className={labelClass}>House / Street Address</label>
                <textarea className="w-full border border-outline-variant/40 bg-transparent px-3 py-2.5 text-sm  focus:border-tertiary" onChange={(event) => set({ address: event.target.value })} required rows={2} value={form.address} />
              </div>
              <div className="md:col-span-2">
                <label className={labelClass}>Apartment / Floor (optional)</label>
                <input className={inputClass} onChange={(event) => set({ apartment: event.target.value })} value={form.apartment} />
              </div>
            </div>
          </section>

          {/* ==================== 2. Shipping Method ==================== */}
          <section>
            <h2 className="mb-4 flex items-center gap-2 font-headline text-lg font-bold">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-tertiary text-xs text-white">2</span>
              Shipping Method
            </h2>
            {shippingLoading ? (
              <p className="text-sm text-on-surface-variant">Loading shipping options...</p>
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
                        <p className="text-sm font-semibold">{method.name}</p>
                        <p className="mt-0.5 text-xs text-on-surface-variant">
                          {method.estimatedDelivery ? `Delivery: ${method.estimatedDelivery}` : ''}
                          {method.codAvailable ? ' · COD available' : ''}
                        </p>
                        {method.description && <p className="mt-0.5 text-xs text-on-surface-variant">{method.description}</p>}
                      </div>
                      <span className="shrink-0 text-sm font-bold">
                        {free ? <span className="text-success">FREE</span> : currency(method.estimatedCost ?? 0)}
                      </span>
                    </button>
                  )
                })}
                {shippingMethods.length === 0 && <p className="text-sm text-on-surface-variant">No shipping options available.</p>}
              </div>
            )}
          </section>

          {/* ==================== 3. Payment Method ==================== */}
          <section>
            <h2 className="mb-4 flex items-center gap-2 font-headline text-lg font-bold">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-tertiary text-xs text-white">3</span>
              Payment Method
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
                        <p className="text-sm font-semibold">{method.name}</p>
                        {method.code === 'cod' && (
                          <p className="mt-0.5 text-xs text-on-surface-variant">Pay in cash when your order is delivered</p>
                        )}
                        {method.type === 'mobile_banking' && merchantNumber && (
                          <p className="mt-0.5 text-xs text-on-surface-variant">Pay securely using {method.name}</p>
                        )}
                        {method.type === 'bank' && bankName && (
                          <p className="mt-0.5 text-xs text-on-surface-variant">Transfer to {bankName}</p>
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
                                Payment Number
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
                                      Copied!
                                    </>
                                  ) : (
                                    <>
                                      <Copy size={14} />
                                      Copy
                                    </>
                                  )}
                                </button>
                              </div>
                              {method.config?.merchantName && (
                                <p className="mt-2 text-sm text-slate-600">
                                  Account Name: {method.config.merchantName}
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
                                  ৩. তারপর Place Order করুন।
                                </p>
                              </div>
                              <div>
                                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">
                                  English Instructions
                                </p>
                                <p className="mt-1 text-sm text-slate-700">
                                  1. Send the required payment amount to the {method.name} number above.<br />
                                  2. After completing the payment, enter the Transaction ID below.<br />
                                  3. Then confirm your order.
                                </p>
                              </div>
                            </div>

                            {/* Transaction ID input */}
                            <div>
                              <label className={labelClass}>Transaction ID</label>
                              <input
                                className={inputClass}
                                onChange={(event) => setTransactionId(event.target.value)}
                                placeholder="Enter Transaction ID"
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
                                Bank Information
                              </p>
                              <div className="mt-3 space-y-2">
                                {bankName && (
                                  <div className="flex justify-between">
                                    <span className="text-sm text-slate-600">Bank Name:</span>
                                    <span className="text-sm font-semibold text-slate-900">{bankName}</span>
                                  </div>
                                )}
                                {accountName && (
                                  <div className="flex justify-between">
                                    <span className="text-sm text-slate-600">Account Name:</span>
                                    <span className="text-sm font-semibold text-slate-900">{accountName}</span>
                                  </div>
                                )}
                                {accountNumber && (
                                  <div className="flex justify-between">
                                    <span className="text-sm text-slate-600">Account Number:</span>
                                    <span className="text-sm font-semibold text-slate-900">{accountNumber}</span>
                                  </div>
                                )}
                                {branch && (
                                  <div className="flex justify-between">
                                    <span className="text-sm text-slate-600">Branch:</span>
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
                                  English Instructions
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
                              <label className={labelClass}>Transaction ID / Reference</label>
                              <input
                                className={inputClass}
                                onChange={(event) => setTransactionId(event.target.value)}
                                placeholder="Enter Transaction ID or Reference Number"
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
                <p className="text-sm text-on-surface-variant">No payment methods available.</p>
              )}
            </div>
          </section>

          {/* ==================== 4. Coupon + Note ==================== */}
          <section>
            <h2 className="mb-4 flex items-center gap-2 font-headline text-lg font-bold">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-tertiary text-xs text-white">4</span>
              Coupon & Notes
            </h2>
            <div className="space-y-4">
              <div>
                {couponApplied ? (
                  <div className="flex items-center justify-between rounded-md bg-success/10 px-4 py-3 text-sm">
                    <span className="font-semibold text-success">
                      Coupon {couponApplied.code} applied - {currency(couponApplied.discount)} off
                    </span>
                    <button className="text-xs font-bold uppercase tracking-widest" onClick={() => setCouponApplied(null)} type="button">
                      Remove
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
                      placeholder="Enter coupon code"
                      value={couponCode}
                    />
                    <button
                      className="border border-primary px-5 py-2 text-xs font-bold uppercase tracking-[0.2em] text-primary-foreground hover:bg-primary hover:text-primary-foreground disabled:opacity-50"
                      disabled={couponLoading || !couponCode.trim()}
                      onClick={applyCoupon}
                      type="button"
                    >
                      {couponLoading ? '...' : 'Apply'}
                    </button>
                  </div>
                )}
                {couponError && <p className="mt-1 text-xs text-destructive">{couponError}</p>}
              </div>
              <div>
                <label className={labelClass}>Order Note (optional)</label>
                <textarea className="w-full border border-outline-variant/40 bg-transparent px-3 py-2.5 text-sm  focus:border-tertiary" onChange={(event) => setOrderNote(event.target.value)} rows={2} value={orderNote} />
              </div>
            </div>
          </section>

          {/* ==================== 5. Review ==================== */}
          <section className="border-t border-outline-variant/20 pt-6">
            <h2 className="mb-4 flex items-center gap-2 font-headline text-lg font-bold">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-tertiary text-xs text-white">5</span>
              Review & Confirm
            </h2>

            {/* Login reminder for guest users */}
            {!authUser && (
              <div className="mb-4 rounded-xl border border-primary/20 bg-primary/5 p-4">
                <div className="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                  <div className="text-center sm:text-left">
                    <p className="text-sm font-semibold text-slate-900">Have an account?</p>
                    <p className="text-xs text-slate-500">Login to save your order to your account.</p>
                  </div>
                  <Link
                    className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-bold text-primary-foreground transition hover:bg-primary/90"
                    state={{ from: '/checkout' }}
                    to="/auth/login"
                  >
                    <LogIn size={14} />
                    Login
                  </Link>
                </div>
              </div>
            )}

            <label className="flex items-start gap-3 text-sm text-on-surface-variant">
              <input checked={agreeTerms} className="mt-0.5" onChange={(event) => setAgreeTerms(event.target.checked)} type="checkbox" />
              <span>
                I agree to the <span className="font-semibold text-tertiary">Terms & Conditions</span> and understand that my order will be processed after
                confirmation {requiresTransactionId && 'and payment verification'}.
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
                  Placing Order...
                </>
              ) : (
                `Place Order · ${currency(total)}`
              )}
            </button>
          </section>
        </form>

        {/* ==================== Order Summary ==================== */}
        <aside className="lg:col-span-5">
          <div className="sticky top-24 space-y-6">
            <div className="border border-outline-variant/20 bg-white p-6 shadow-panel">
              <h2 className="border-b border-outline-variant/20 pb-4 font-headline text-xl font-bold tracking-tight">Order Summary</h2>

              <div className="mt-5 max-h-72 space-y-4 overflow-y-auto">
                {cart.map((item) => (
                  <div className="flex gap-3" key={item.key}>
                    <img alt={item.product.title} className="h-16 w-14 shrink-0 object-cover" src={item.image || item.product.images[0]} />
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-semibold">{item.product.title}</p>
                      <p className="text-xs text-on-surface-variant">
                        {item.quantity} × {currency(Number(item.product.price))}
                        {item.size ? ` · Size: ${item.size}` : ''}
                        {item.color ? ` · Color: ${item.color}` : ''}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-semibold">{currency(Number(item.product.price) * item.quantity)}</p>
                      <button
                        className="mt-1 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant hover:text-destructive"
                        onClick={() => dispatch(removeFromCart(item.key))}
                        type="button"
                      >
                        Remove
                      </button>
                    </div>
                  </div>
                ))}
                {!cart.length && <p className="text-sm text-on-surface-variant">Your bag is empty.</p>}
              </div>

              {/* Tax option — reflects the single tax rate configured in admin */}
              <div className="mt-5 rounded-lg border border-outline-variant/20 bg-surface-variant/5 p-4">
                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">Tax</p>
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
                      <span className="block text-sm font-semibold">{taxRate > 0 ? `${taxRate}% Tax` : '0% — No Tax'}</span>
                      <span className="block text-xs text-on-surface-variant">
                        {taxRate > 0 ? 'Applied based on your order total.' : 'No tax is applied to this order.'}
                      </span>
                    </span>
                    <span className="ml-auto shrink-0 text-sm font-bold">{currency(tax)}</span>
                  </button>
                </div>
              </div>

              <div className="mt-6 space-y-3 border-t border-outline-variant/20 pt-4 text-sm">
                <div className="flex justify-between">
                  <span className="text-on-surface-variant">Subtotal</span>
                  <span className="font-semibold">{currency(subtotal)}</span>
                </div>
                {discount > 0 && (
                  <div className="flex justify-between text-success">
                    <span>Coupon Discount</span>
                    <span className="font-semibold">-{currency(discount)}</span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span className="text-on-surface-variant">Shipping</span>
                  <span className="font-semibold">{shippingCost === 0 ? 'FREE' : currency(shippingCost)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-on-surface-variant">Tax{taxRate > 0 ? ` (${taxRate}%)` : ''}</span>
                  <span className="font-semibold">{currency(tax)}</span>
                </div>
                <div className="flex items-end justify-between border-t border-outline-variant/20 pt-4">
                  <span className="font-headline text-lg font-bold">Total</span>
                  <span className="font-headline text-2xl font-extrabold">{currency(total)}</span>
                </div>
                {selectedPaymentMethod?.config?.extraFeePercent ? (
                  <p className="text-xs text-on-surface-variant">Note: payment gateway fees may apply.</p>
                ) : null}
              </div>
            </div>

            <p className="text-center text-xs text-on-surface-variant">
              Need help? Call us at <span className="font-semibold">01711111111</span>
            </p>
          </div>
        </aside>
      </div>
    </main>
  )
}

export default CheckoutPage
