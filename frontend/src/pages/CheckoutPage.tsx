import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { LogIn, CheckCircle, Copy, Check } from 'lucide-react'
import { SEO } from '../components/common/SEO'
import { api } from '../lib/api'
import { currency } from '../lib/format'
import { trackInitiateCheckout } from '../lib/pixel'
import { useAppDispatch, useAppSelector } from '../store/hooks'
import { useGetStoreInfoQuery } from '../store/services/commerceApi'
import { clearCart, removeFromCart } from '../store/slices/cartSlice'
import { setAuthSession } from '../store/slices/authSlice'
import { placeOrder } from '../store/slices/ordersSlice'
import LocationSelect from '../components/common/LocationSelect'
import { getAreas, getDistricts, getDivisions, getUpazilas, loadLocations, type GeoNode } from '../data/locations'
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

// Well-known method names are shown bilingually; any other database value is left untouched.
const BILINGUAL_NAMES: Record<string, string> = {
  Standard: 'স্ট্যান্ডার্ড / Standard',
  'Cash on Delivery': 'ক্যাশ অন ডেলিভারি / Cash on Delivery',
}

const displayName = (name: string) => BILINGUAL_NAMES[name] ?? name

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
  village: string
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
  village: '',
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
  const { data: storeInfo } = useGetStoreInfoQuery()

  const [form, setForm] = useState<AddressFormState>(emptyAddress)
  const [savedAddresses, setSavedAddresses] = useState<UserAddress[]>([])
  const [selectedAddressId, setSelectedAddressId] = useState<'new' | number>('new')
  const [addressesLoading, setAddressesLoading] = useState(false)
  const [geo, setGeo] = useState<GeoNode[] | null>(null)
  const [locationsLoading, setLocationsLoading] = useState(true)

  const [shippingMethods, setShippingMethods] = useState<ShippingMethod[]>([])
  const [shippingMethodId, setShippingMethodId] = useState<number | null>(null)
  const [shippingLoading, setShippingLoading] = useState(true)

  const [paymentMethods, setPaymentMethods] = useState<PaymentMethodInfo[]>([])
  const [selectedPaymentMethodId, setSelectedPaymentMethodId] = useState<number | null>(null)
  const [notices, setNotices] = useState<CheckoutNotice[]>([])

  const [transactionId, setTransactionId] = useState('')
  const [paymentSenderNumber, setPaymentSenderNumber] = useState('')
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
    const method = shippingMethods.find((m) => m.id === shippingMethodId)
    return method ? getShippingDisplayCost(method) : 0
  }, [shippingMethodId, shippingMethods])

  const discount = couponApplied?.discount ?? 0
  const taxRate = taxSettings.taxRate
  const taxable = subtotal - discount + (taxSettings.applyTaxToShipping ? shippingCost : 0)
  const tax = Math.round(Math.max(0, taxable) * (taxRate / 100))
  const total = subtotal - discount + tax + shippingCost

  const selectedShippingMethod = shippingMethods.find((m) => m.id === shippingMethodId) || null
  const selectedPaymentMethod = paymentMethods.find((m) => m.id === selectedPaymentMethodId) || null
  const paymentMethod: PaymentMethod = selectedPaymentMethod?.code || 'cod'
  const isCOD = selectedPaymentMethod?.code?.toLowerCase() === 'cod'
  const requiresPaymentVerification = Boolean(selectedPaymentMethod && !isCOD)
  const requiresTransactionId = requiresPaymentVerification

  const set = (patch: Partial<AddressFormState>) => setForm((prev) => ({ ...prev, ...patch }))

  // ---------- Bangladesh location cascading options ----------
  const divisionOptions = useMemo(() => (geo ? getDivisions(geo) : []), [geo])
  const districtOptions = useMemo(() => (geo ? getDistricts(geo, form.division) : []), [geo, form.division])
  const upazilaOptions = useMemo(
    () => (geo ? getUpazilas(geo, form.division, form.district) : []),
    [geo, form.division, form.district],
  )
  const areaOptions = useMemo(
    () => (geo ? getAreas(geo, form.division, form.district, form.upazila) : []),
    [geo, form.division, form.district, form.upazila],
  )

  const handleDivisionChange = (value: string) => set({ division: value, district: '', upazila: '', area: '', village: '' })
  const handleDistrictChange = (value: string) => set({ district: value, upazila: '', area: '', village: '' })
  const handleUpazilaChange = (value: string) => set({ upazila: value, area: '', village: '' })
  const handleAreaChange = (value: string) => set({ area: value, village: '' })

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
        if (payments[0]) setSelectedPaymentMethodId(payments[0].id)
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
  // Deduplicate by ID to prevent duplicate shipping methods
  useEffect(() => {
    if (shippingLoading) return
    let mounted = true
    const timer = setTimeout(async () => {
      try {
        const methods = await api.estimateShipping(subtotal)
        if (!mounted) return
        // Deduplicate by ID, keeping the last occurrence
        const deduped = Array.from(new Map(methods.map((m) => [m.id, m])).values())
        setShippingMethods(deduped)
        // If current selection is no longer valid, select first available
        setShippingMethodId((prev) => {
          if (prev && deduped.some((m) => m.id === prev)) return prev
          return deduped[0]?.id ?? null
        })
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
      village: '',
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

  // ---------- Load Bangladesh location dataset ----------
  useEffect(() => {
    let mounted = true
    loadLocations()
      .then((data) => {
        if (!mounted) return
        setGeo(data)
      })
      .catch(() => {
        if (!mounted) return
      })
      .finally(() => {
        if (mounted) setLocationsLoading(false)
      })
    return () => {
      mounted = false
    }
  }, [])

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
      village: '',
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
    if (requiresPaymentVerification && !paymentSenderNumber.trim()) {
      return 'পেমেন্ট নম্বর/অ্যাকাউন্ট দিন। / Please enter the sender account/mobile number.'
    }
    if (requiresPaymentVerification && !transactionId.trim()) {
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
      address: [form.address.trim(), form.village.trim()].filter(Boolean).join(', '),
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
        senderNumber: requiresPaymentVerification ? paymentSenderNumber.trim() : undefined,
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

  const inputClass =
    'w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100'
  const labelClass = 'mb-1.5 block text-sm font-medium text-slate-700'

  return (
    <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <SEO
        title="Checkout"
        description="Complete your order at Mama Bazar. Secure checkout with multiple payment options."
        url="/checkout"
      />
      {/* Checkout notices */}
      {notices.length > 0 && (
        <div className="mb-6 space-y-2">
          {notices.map((notice) => (
            <div
              className="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-medium"
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
        <div className="mb-6 flex flex-col gap-3 rounded-lg border border-brand-green-200 bg-brand-green-50/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
          <p className="text-base font-semibold text-slate-900">
            Already have an account? <span className="font-normal text-slate-600">Login for a faster checkout.</span>
          </p>
          <div className="flex flex-col gap-2 sm:flex-row">
            <Link
              className="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-brand-green-600 px-4 text-[13px] font-semibold text-white transition hover:bg-brand-green-700"
              state={{ from: '/checkout' }}
              to="/auth/login"
            >
              <LogIn size={15} />
              Login / Register
            </Link>
            <button
              className="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-[13px] font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
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
      )}

      {/* Logged-in user indicator */}
      {authUser && (
        <div className="mb-6 flex items-center gap-3 rounded-2xl border border-brand-green-200 bg-brand-green-50/70 px-5 py-4">
          <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-green-600/15">
            <CheckCircle size={20} className="text-brand-green-600" />
          </div>
          <div>
            <p className="text-sm font-semibold text-slate-900 bangla-text">স্বাগতম, {authUser.name}! / Welcome back, {authUser.name}!</p>
            <p className="text-xs text-slate-500 bangla-text">আপনার তথ্য আপনার অ্যাকাউন্ট থেকে স্বয়ংক্রিয়ভাবে পূরণ হবে। / Your information will be auto-filled from your account.</p>
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 gap-8 lg:grid-cols-12">
        <form className="space-y-0 lg:col-span-8" id="checkout-form" onSubmit={onSubmit}>
          <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div className="border-b border-slate-100 px-6 py-6 sm:px-8">
              <h1 className="font-headline text-2xl font-extrabold tracking-tight text-slate-900">{renderBilingual('চেকআউট / Checkout')}</h1>
              <p className="mt-1 text-sm text-slate-500 bangla-text">{cart.length} টি আইটেম আপনার ব্যাগে / {cart.length} items in your bag</p>
            </div>

            {/* ==================== 1. Address ==================== */}
            <section className="border-b border-slate-100 px-6 py-8 sm:px-8">
              <h2 className="mb-6 flex items-center gap-3 font-headline text-lg font-bold text-slate-900">
                <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-green-600 text-xs font-bold text-white">1</span>
                <span className="bangla-text">ডেলিভারি ঠিকানা / Delivery Address</span>
              </h2>

              {authUser && (
                <div className="mb-6">
                  <label className={labelClass}>Saved Address</label>
                  <select
                    className="w-full appearance-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100"
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
                    <option value="new">Use a New Address</option>
                    {savedAddresses.map((addr) => (
                      <option key={addr.id} value={String(addr.id)}>
                        {addr.recipientName} - {addr.area || addr.shippingArea || addr.district || ''} {addr.isDefault ? '(Default)' : ''}
                      </option>
                    ))}
                  </select>
                </div>
              )}

              <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div className="sm:col-span-2">
                  <label className={labelClass}>Full Name</label>
                  <input className={inputClass} onChange={(event) => set({ name: event.target.value })} placeholder="Enter your name" required value={form.name} />
                </div>
                <div>
                  <label className={labelClass}>Phone Number</label>
                  <input className={inputClass} onChange={(event) => set({ phone: event.target.value })} placeholder="01XXXXXXXXX" required value={form.phone} />
                </div>
                <div>
                  <label className={labelClass}>Alternative Phone</label>
                  <input className={inputClass} onChange={(event) => set({ alternativePhone: event.target.value })} placeholder="01XXXXXXXXX" value={form.alternativePhone} />
                </div>
                <div className="sm:col-span-2">
                  <label className={labelClass}>Email (Optional)</label>
                  <input className={inputClass} onChange={(event) => set({ email: event.target.value })} type="email" value={form.email} />
                </div>
                <LocationSelect
                  disabled={locationsLoading}
                  label="Division"
                  onChange={handleDivisionChange}
                  options={divisionOptions}
                  placeholder="Select Division"
                  searchPlaceholder="Search division..."
                  value={form.division}
                />
                <LocationSelect
                  disabled={locationsLoading || !form.division}
                  label="District"
                  onChange={handleDistrictChange}
                  options={districtOptions}
                  placeholder="Select District"
                  searchPlaceholder="Search district..."
                  value={form.district}
                />
                <LocationSelect
                  disabled={locationsLoading || !form.district}
                  label="Upazila / Thana"
                  onChange={handleUpazilaChange}
                  options={upazilaOptions}
                  placeholder="Select Upazila / Thana"
                  searchPlaceholder="Search upazila / thana..."
                  value={form.upazila}
                />
                <LocationSelect
                  disabled={locationsLoading || !form.upazila}
                  label="Union / Area"
                  onChange={handleAreaChange}
                  options={areaOptions}
                  placeholder="Select Union / Area"
                  searchPlaceholder="Search union / area..."
                  value={form.area}
                />
                <div>
                  <label className={labelClass}>Village / Locality</label>
                  <input className={inputClass} onChange={(event) => set({ village: event.target.value })} placeholder="Enter village / locality" value={form.village} />
                </div>
                <div>
                  <label className={labelClass}>Postal Code</label>
                  <input className={inputClass} onChange={(event) => set({ postalCode: event.target.value })} placeholder="1212" value={form.postalCode} />
                </div>
                <div className="sm:col-span-2">
                  <label className={labelClass}>House / Road / Street Address</label>
                  <textarea className={inputClass} onChange={(event) => set({ address: event.target.value })} placeholder="Enter your address" required rows={2} value={form.address} />
                </div>
                <div>
                  <label className={labelClass}>Apartment / Floor (Optional)</label>
                  <input className={inputClass} onChange={(event) => set({ apartment: event.target.value })} value={form.apartment} />
                </div>
                <div>
                  <label className={labelClass}>Country</label>
                  <input className={inputClass} onChange={(event) => set({ country: event.target.value })} value={form.country} />
                </div>
              </div>
            </section>

            {/* ==================== 2. Shipping Method ==================== */}
            <section className="border-b border-slate-100 px-6 py-8 sm:px-8">
              <h2 className="mb-6 flex items-center gap-3 font-headline text-lg font-bold text-slate-900">
                <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-green-600 text-xs font-bold text-white">2</span>
                <span className="bangla-text">ডেলিভারি পদ্ধতি / Shipping Method</span>
              </h2>
              {shippingLoading ? (
                <p className="text-sm text-slate-500">শিপিং অপশন লোড হচ্ছে... / Loading shipping options...</p>
              ) : (
                <div>
                  {shippingMethods.length > 0 ? (
                    <>
                      <div className="relative">
                        <select
                          aria-label="Shipping method"
                          className="w-full appearance-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-sm font-medium text-slate-900 shadow-soft outline-none transition focus:border-brand-green-500 focus:ring-2 focus:ring-brand-green-100"
                          onChange={(event) => setShippingMethodId(Number(event.target.value))}
                          value={shippingMethodId ?? ''}
                        >
                          {shippingMethods.map((method) => {
                            const cost = getShippingDisplayCost(method)
                            return (
                              <option key={method.id} value={method.id}>
                                {getShippingLabel(method.name)} — {isShippingFree(method) ? 'ফ্রি / FREE' : currency(cost)}
                              </option>
                            )
                          })}
                        </select>
                        <span className="pointer-events-none absolute inset-y-0 right-3.5 flex items-center text-brand-green-600">⌄</span>
                      </div>
                      {selectedShippingMethod && (
                        <p className="mt-2 text-xs text-slate-500 bangla-text">
                          {selectedShippingMethod.estimatedDelivery
                            ? `ডেলিভারি: ${selectedShippingMethod.estimatedDelivery} / Delivery: ${selectedShippingMethod.estimatedDelivery}`
                            : ''}
                          {selectedShippingMethod.codAvailable ? ' · ক্যাশ অন ডেলিভারি উপলব্ধ / COD available' : ''}
                        </p>
                      )}
                    </>
                  ) : (
                    <p className="text-sm text-slate-500 bangla-text">
                      কোনো শিপিং অপশন উপলব্ধ নেই। / No shipping options available.
                    </p>
                  )}
                </div>
              )}
            </section>

            {/* ==================== 3. Payment Method ==================== */}
            <section className="border-b border-slate-100 px-6 py-8 sm:px-8">
              <h2 className="mb-6 flex items-center gap-3 font-headline text-lg font-bold text-slate-900">
                <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-green-600 text-xs font-bold text-white">3</span>
                <span className="bangla-text">পেমেন্ট পদ্ধতি / Payment Method</span>
              </h2>
              <div className="space-y-4">
                {paymentMethods.length > 0 && selectedPaymentMethod ? (
                  <>
                    <div className="relative">
                      <select
                        aria-label="Payment method"
                        className="w-full appearance-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-sm font-medium text-slate-900 shadow-soft outline-none transition focus:border-brand-green-500 focus:ring-2 focus:ring-brand-green-100"
                        onChange={(event) => {
                          setSelectedPaymentMethodId(Number(event.target.value))
                          setTransactionId('')
                          setSubmitError('')
                        }}
                        value={selectedPaymentMethodId ?? ''}
                      >
                        {paymentMethods.map((method) => (
                          <option key={method.id} value={method.id}>
                            {displayName(method.name)}
                          </option>
                        ))}
                      </select>
                      <span className="pointer-events-none absolute inset-y-0 right-3.5 flex items-center text-brand-green-600">⌄</span>
                    </div>

                    {selectedPaymentMethod.config?.instructions && (
                      <div className="rounded-xl border border-brand-green-200 bg-brand-green-50/70 p-4">
                        <p className="text-xs font-bold uppercase tracking-[0.15em] text-brand-green-700 bangla-text">নির্দেশনা / Instructions</p>
                        <p className="mt-2 whitespace-pre-line text-sm text-slate-700 bangla-text">{selectedPaymentMethod.config.instructions}</p>
                      </div>
                    )}

                    {paymentMethod === 'cod' && (
                      <div className="rounded-xl bg-slate-50 p-4">
                        <p className="text-sm font-medium text-slate-700 bangla-text">ডেলিভারি পাওয়ার সময় ক্যাশ পেমেন্ট করতে হবে। / Pay in cash when your order is delivered.</p>
                      </div>
                    )}

                    {requiresPaymentVerification && (
                      <div className="space-y-4 rounded-xl border border-brand-green-200 bg-brand-green-50/70 p-5">
                        <div>
                          <p className="text-sm font-bold text-brand-green-700 bangla-text">পেমেন্ট যাচাই / Payment Verification</p>
                          <p className="mt-1 text-xs text-slate-500 bangla-text">
                            পেমেন্ট করার পর নিচের তথ্য দিন। / After completing the payment, provide the following details.
                          </p>
                        </div>
                        <div>
                          <label className={labelClass}>যে নম্বর/অ্যাকাউন্ট থেকে টাকা পাঠিয়েছেন / Sender Account / Mobile Number</label>
                          <input
                            className={inputClass}
                            inputMode="tel"
                            onChange={(event) => setPaymentSenderNumber(event.target.value)}
                            placeholder="01XXXXXXXXX"
                            required
                            value={paymentSenderNumber}
                          />
                        </div>
                        <div>
                          <label className={labelClass}>Transaction ID / ট্রানজেকশন আইডি</label>
                          <input
                            className={inputClass}
                            onChange={(event) => setTransactionId(event.target.value)}
                            placeholder="Enter your Transaction ID"
                            required
                            value={transactionId}
                          />
                        </div>
                      </div>
                    )}

                    {selectedPaymentMethod.type === 'mobile_banking' && selectedPaymentMethod.config?.merchantNumber && (
                      <div className="space-y-4 rounded-xl bg-slate-50 p-5">
                        <div>
                          <p className="text-xs font-bold uppercase tracking-[0.15em] text-slate-500 bangla-text">পেমেন্ট নম্বর / Payment Number</p>
                          <div className="mt-2 flex flex-wrap items-center gap-3">
                            <span className="text-xl font-bold text-slate-900">{selectedPaymentMethod.config.merchantNumber}</span>
                            <button
                              className="inline-flex items-center gap-1.5 rounded-lg bg-brand-green-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-green-700"
                              onClick={() => copyToClipboard(selectedPaymentMethod.config?.merchantNumber || '')}
                              type="button"
                            >
                              {copiedNumber === selectedPaymentMethod.config.merchantNumber ? <><Check size={14} /> কপি হয়েছে! / Copied!</> : <><Copy size={14} /> কপি / Copy</>}
                            </button>
                          </div>
                          {selectedPaymentMethod.config.merchantName && (
                            <p className="mt-2 text-sm text-slate-600 bangla-text">অ্যাকাউন্টের নাম: / Account Name: {selectedPaymentMethod.config.merchantName}</p>
                          )}
                        </div>
                        <div>
                          <p className="text-sm text-slate-700 bangla-text">১. উপরের {selectedPaymentMethod.name} নম্বরে পেমেন্ট করুন।<br />২. পেমেন্ট সম্পন্ন করার পর Transaction ID দিন।</p>
                        </div>
                      </div>
                    )}

                    {selectedPaymentMethod.type === 'bank' && (
                      <div className="space-y-4 rounded-xl bg-slate-50 p-5">
                        <div className="space-y-2">
                          <p className="text-xs font-bold uppercase tracking-[0.15em] text-slate-500 bangla-text">ব্যাংক তথ্য / Bank Information</p>
                          {selectedPaymentMethod.config?.bankName && <p className="text-sm text-slate-700">Bank Name: <strong>{selectedPaymentMethod.config.bankName}</strong></p>}
                          {selectedPaymentMethod.config?.accountName && <p className="text-sm text-slate-700">Account Name: <strong>{selectedPaymentMethod.config.accountName}</strong></p>}
                          {selectedPaymentMethod.config?.accountNumber && <p className="text-sm text-slate-700">Account Number: <strong>{selectedPaymentMethod.config.accountNumber}</strong></p>}
                          {selectedPaymentMethod.config?.branch && <p className="text-sm text-slate-700">Branch: <strong>{selectedPaymentMethod.config.branch}</strong></p>}
                        </div>
                      </div>
                    )}
                  </>
                ) : (
                  <p className="text-sm text-slate-500">কোনো পেমেন্ট পদ্ধতি উপলব্ধ নেই। / No payment methods available.</p>
                )}
              </div>
            </section>

            {/* ==================== 4. Coupon + Note ==================== */}
            <section className="border-b border-slate-100 px-6 py-8 sm:px-8">
              <h2 className="mb-6 flex items-center gap-3 font-headline text-lg font-bold text-slate-900">
                <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-green-600 text-xs font-bold text-white">4</span>
                <span className="bangla-text">কুপন ও নোট / Coupon &amp; Notes</span>
              </h2>
              <div className="space-y-5">
                <div>
                  {couponApplied ? (
                    <div className="flex items-center justify-between rounded-xl bg-brand-green-50 px-4 py-3 text-sm">
                      <span className="font-semibold text-brand-green-700 bangla-text">
                        কুপন {couponApplied.code} প্রয়োগ হয়েছে — {currency(couponApplied.discount)} ছাড় / Coupon {couponApplied.code} applied - {currency(couponApplied.discount)} off
                      </span>
                      <button className="text-xs font-bold uppercase tracking-widest text-slate-500 transition hover:text-destructive" onClick={() => setCouponApplied(null)} type="button">
                        মুছে ফেলুন / Remove
                      </button>
                    </div>
                  ) : (
                    <div className="flex gap-2">
                      <input
                        className={`${inputClass} min-w-0 flex-1`}
                        onChange={(event) => {
                          setCouponCode(event.target.value)
                          setCouponError('')
                        }}
                        placeholder="কুপন কোড লিখুন / Enter Coupon Code"
                        value={couponCode}
                      />
                      <button
                        className="shrink-0 rounded-lg bg-brand-green-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-green-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                        disabled={couponLoading || !couponCode.trim()}
                        onClick={applyCoupon}
                        type="button"
                      >
                        {couponLoading ? "..." : "প্রয়োগ করুন / Apply"}
                      </button>
                    </div>
                  )}
                  {couponError && <p className="mt-1.5 text-xs text-destructive">{couponError}</p>}
                </div>
                <div>
                  <label className={labelClass}>অর্ডার নোট (ঐচ্ছিক) / Order Note (Optional)</label>
                  <textarea className={inputClass} onChange={(event) => setOrderNote(event.target.value)} placeholder="অর্ডার সম্পর্কে অতিরিক্ত তথ্য / Additional order notes" rows={2} value={orderNote} />
                </div>
              </div>
            </section>

            {/* ==================== 5. Review ==================== */}
            <section className="px-6 py-8 sm:px-8">
              <h2 className="mb-6 flex items-center gap-3 font-headline text-lg font-bold text-slate-900">
                <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-green-600 text-xs font-bold text-white">5</span>
                <span className="bangla-text">পর্যালোচনা ও নিশ্চিতকরণ / Review &amp; Confirm</span>
              </h2>

              {!authUser && (
                <div className="mb-5 rounded-xl border border-brand-green-200 bg-brand-green-50/70 p-4">
                  <div className="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                    <div className="text-center sm:text-left">
                      <p className="text-sm font-semibold text-slate-900">অ্যাকাউন্ট আছে? / Have an account?</p>
                      <p className="text-xs text-slate-500 bangla-text">আপনার অর্ডার অ্যাকাউন্টে সংরক্ষণ করতে লগইন করুন। / Login to save your order to your account.</p>
                    </div>
                    <Link
                      className="inline-flex items-center gap-2 rounded-lg bg-brand-green-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-brand-green-700"
                      state={{ from: '/checkout' }}
                      to="/auth/login"
                    >
                      <LogIn size={14} />
                      লগইন / Login
                    </Link>
                  </div>
                </div>
              )}

              <label className="flex items-start gap-3 text-sm text-slate-600">
                <input checked={agreeTerms} className="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500" onChange={(event) => setAgreeTerms(event.target.checked)} type="checkbox" />
                <span className="bangla-text">
                  আমি <span className="font-semibold text-brand-green-700">Terms &amp; Conditions</span>-এ সম্মত এবং বুঝতে পারছি যে নিশ্চিত করার পর আমার অর্ডারটি প্রক্রিয়াজাত করা হবে। / I agree to the{' '}
                  <span className="font-semibold text-brand-green-700">Terms &amp; Conditions</span> and understand that my order will be processed after confirmation{' '}
                  {requiresTransactionId && 'and payment verification'}.
                </span>
              </label>

              {submitError && <p className="mt-3 rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">{submitError}</p>}

              <button
                className="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-orange-500 px-6 py-4 text-sm font-bold text-white shadow-lg shadow-brand-orange-500/25 transition hover:bg-brand-orange-600 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
                disabled={creating || !cart.length || shippingLoading}
                type="submit"
              >
                {creating ? (
                  <>
                    <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                    অর্ডার প্রক্রিয়াজাত হচ্ছে... / Placing Order...
                  </>
                ) : (
                  <span className="bangla-text">অর্ডার নিশ্চিত করুন / Place Order • {currency(total)}</span>
                )}
              </button>
            </section>
          </div>
        </form>

        {/* ==================== Order Summary ==================== */}
        <aside className="lg:col-span-4">
          <div className="space-y-5 lg:sticky lg:top-24">
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
              <h2 className="border-b border-slate-100 px-5 py-4 font-headline text-base font-bold text-slate-900">Order Summary</h2>

              <div className="px-5 py-4">
                <div className="max-h-72 space-y-3 overflow-y-auto">
                  {cart.map((item) => (
                    <div className="flex gap-3" key={item.key}>
                      <img alt={item.product.title} className="h-14 w-12 shrink-0 rounded-lg border border-slate-100 object-cover" src={item.image || item.product.images[0]} />
                      <div className="min-w-0 flex-1">
                        <p className="line-clamp-2 text-sm font-medium text-slate-900">{item.product.title}</p>
                        <p className="text-xs text-slate-500">
                          {item.quantity} × {currency(Number(item.product.price))}
                          {item.size ? ` · Size: ${item.size}` : ''}
                          {item.color ? ` · Color: ${item.color}` : ''}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-semibold text-slate-900">{currency(Number(item.product.price) * item.quantity)}</p>
                        <button
                          className="mt-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 transition hover:text-destructive"
                          onClick={() => dispatch(removeFromCart(item.key))}
                          type="button"
                        >
                          Remove
                        </button>
                      </div>
                    </div>
                  ))}
                  {!cart.length && <p className="text-sm text-slate-500">Your bag is empty.</p>}
                </div>

                <div className="mt-4 space-y-2.5 border-t border-slate-100 pt-3.5 text-sm">
                  <div className="flex justify-between">
                    <span className="text-slate-500">Subtotal</span>
                    <span className="font-semibold text-slate-900">{currency(subtotal)}</span>
                  </div>
                  {discount > 0 && (
                    <div className="flex justify-between text-brand-green-600">
                      <span>Coupon Discount</span>
                      <span className="font-semibold">-{currency(discount)}</span>
                    </div>
                  )}
                  <div className="flex justify-between">
                    <span className="text-slate-500">Shipping</span>
                    <span className="font-semibold text-slate-900">{shippingCost === 0 ? 'FREE' : currency(shippingCost)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className={taxRate > 0 ? 'text-slate-500' : 'text-slate-400'}>Tax{taxRate > 0 ? ` (${taxRate}%)` : ''}</span>
                    <span className={`font-semibold ${taxRate > 0 ? 'text-slate-900' : 'text-slate-500'}`}>{currency(tax)}</span>
                  </div>
                  {taxRate > 0 && tax > 0 && <p className="text-xs text-slate-400">Tax is calculated based on your order total.</p>}
                  <div className="flex items-end justify-between border-t border-slate-100 pt-3.5">
                    <span className="font-headline text-base font-bold text-slate-900">Total</span>
                    <span className="font-headline text-xl font-extrabold text-brand-green-600">{currency(total)}</span>
                  </div>
                  {selectedPaymentMethod?.config?.extraFeePercent ? <p className="text-xs text-slate-400">Note: payment gateway fees may apply.</p> : null}
                </div>
              </div>
            </div>

            <p className="text-center text-xs text-slate-500">
              Need help? Call us at{' '}
              <span className="font-semibold">{storeInfo?.primaryPhone || '01711111111'}</span>
            </p>
          </div>
        </aside>
      </div>
    </main>
  )
}

export default CheckoutPage
