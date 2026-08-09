import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { SEO } from '../components/common/SEO'
import { api } from '../lib/api'
import { currency, salePrice } from '../lib/format'
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
const TAX_RATE = 0.05

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

  const [senderNumber, setSenderNumber] = useState('')
  const [transactionId, setTransactionId] = useState('')
  const [amountSent, setAmountSent] = useState('')
  const [paymentScreenshot, setPaymentScreenshot] = useState('')
  const [uploadingProof, setUploadingProof] = useState(false)

  const [couponCode, setCouponCode] = useState('')
  const [couponApplied, setCouponApplied] = useState<{ code: string; discount: number } | null>(null)
  const [couponError, setCouponError] = useState('')
  const [couponLoading, setCouponLoading] = useState(false)

  const [orderNote, setOrderNote] = useState('')
  const [submitError, setSubmitError] = useState('')
  const [agreeTerms, setAgreeTerms] = useState(false)

  const subtotal = useMemo(
    () => cart.reduce((sum, item) => sum + salePrice(item.product.price, item.product.discount) * item.quantity, 0),
    [cart],
  )

  const shippingCost = useMemo(() => {
    if (shippingMethodId === null) return 0
    return shippingMethods.find((m) => m.id === shippingMethodId)?.estimatedCost ?? 0
  }, [shippingMethodId, shippingMethods])

  const discount = couponApplied?.discount ?? 0
  const tax = Math.round((subtotal - discount) * TAX_RATE)
  const total = subtotal - discount + tax + shippingCost

  const selectedShippingMethod = shippingMethods.find((m) => m.id === shippingMethodId) || null
  const selectedPaymentMethod = paymentMethods.find((m) => m.code === paymentMethod) || null
  const isOnlinePayment = paymentMethod !== 'cod'

  const set = (patch: Partial<AddressFormState>) => setForm((prev) => ({ ...prev, ...patch }))

  // ---------- Load checkout config ----------
  useEffect(() => {
    let mounted = true
    const load = async () => {
      try {
        const [methods, payments, noticeList] = await Promise.all([
          api.estimateShipping(0),
          api.getPaymentMethods(),
          api.getCheckoutNotices(),
        ])
        if (!mounted) return
        setShippingMethods(methods)
        const first = methods[0]
        if (first) setShippingMethodId(first.id)
        setPaymentMethods(payments)
        const cod = payments.find((p) => p.code === 'cod')
        if (cod) setPaymentMethod('cod')
        setNotices(noticeList)
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

  // ---------- Screenshot upload ----------
  const handleProofUpload = async (file: File) => {
    setUploadingProof(true)
    setSubmitError('')
    try {
      const result = await api.uploadPaymentProof(file)
      setPaymentScreenshot(result.url)
    } catch (error) {
      setSubmitError(error instanceof Error ? error.message : 'Screenshot upload failed')
    } finally {
      setUploadingProof(false)
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
    if (isOnlinePayment) {
      if (!senderNumber.trim()) return 'Please enter the sender (bKash/Nagad/Rocket) number you paid from'
      if (!paymentScreenshot && !transactionId.trim()) {
        return 'Please upload your payment screenshot or enter the TrxID'
      }
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
        senderNumber: isOnlinePayment ? senderNumber.trim() : undefined,
        transactionId: isOnlinePayment && transactionId.trim() ? transactionId.trim() : undefined,
        paymentScreenshot: isOnlinePayment && paymentScreenshot ? paymentScreenshot : undefined,
        amountSent: isOnlinePayment && amountSent ? Number(amountSent) : undefined,
        taxAmount: tax,
        items: cart.map((item) => ({
          productId: item.product.id,
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

  const inputClass = 'w-full border-b border-outline-variant/40 bg-transparent py-2 text-sm outline-none focus:border-tertiary'
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

      <div className="grid grid-cols-1 gap-10 lg:grid-cols-12">
        <form className="space-y-10 lg:col-span-7" onSubmit={onSubmit}>
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
                  className="w-full border border-outline-variant/40 bg-transparent px-3 py-2.5 text-sm outline-none"
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
                <textarea className="w-full border border-outline-variant/40 bg-transparent px-3 py-2.5 text-sm outline-none focus:border-tertiary" onChange={(event) => set({ address: event.target.value })} required rows={2} value={form.address} />
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
                return (
                  <button
                    className={`flex w-full items-center gap-4 border px-4 py-3.5 text-left transition-colors ${
                      active ? 'border-tertiary bg-tertiary/5' : 'border-outline-variant/30 hover:border-outline-variant'
                    }`}
                    key={method.code}
                    onClick={() => {
                      setPaymentMethod(method.code)
                      setSubmitError('')
                    }}
                    type="button"
                  >
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-variant/20 text-sm font-bold">{PAYMENT_ICONS[method.code] || '💳'}</span>
                    <span className="min-w-0 flex-1 text-sm font-semibold">{method.name}</span>
                    {method.code === 'cod' && <span className="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Pay at door</span>}
                  </button>
                )
              })}
              {paymentMethods.length === 0 && <p className="text-sm text-on-surface-variant">No payment methods available.</p>}
            </div>

            {/* Online payment details */}
            {selectedPaymentMethod && isOnlinePayment && (
              <div className="mt-4 space-y-4 rounded-lg border border-outline-variant/30 bg-surface-variant/5 p-4">
                {selectedPaymentMethod.config?.instructions && (
                  <div className="rounded-md bg-tertiary/10 p-3 text-sm">
                    <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-on-surface-variant">How to pay</p>
                    <p className="mt-1 whitespace-pre-line">{selectedPaymentMethod.config.instructions}</p>
                    {(selectedPaymentMethod.config.merchantName || selectedPaymentMethod.config.merchantNumber) && (
                      <p className="mt-2 text-xs text-on-surface-variant">
                        Pay to: <span className="font-semibold">{selectedPaymentMethod.config.merchantName || selectedPaymentMethod.config.accountName || ''}</span>{' '}
                        {selectedPaymentMethod.config.merchantNumber || selectedPaymentMethod.config.accountNumber || ''}
                      </p>
                    )}
                  </div>
                )}

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <div>
                    <label className={labelClass}>Sender Number (paid from)</label>
                    <input className={inputClass} onChange={(event) => setSenderNumber(event.target.value)} placeholder="01XXXXXXXXX" value={senderNumber} />
                  </div>
                  <div>
                    <label className={labelClass}>Amount Sent (Tk)</label>
                    <input className={inputClass} onChange={(event) => setAmountSent(event.target.value)} placeholder={String(total)} type="number" value={amountSent} />
                  </div>
                  <div className="md:col-span-2">
                    <label className={labelClass}>Transaction ID (TrxID)</label>
                    <input className={inputClass} onChange={(event) => setTransactionId(event.target.value)} placeholder="e.g. 9XB7H2KD5F" value={transactionId} />
                  </div>
                </div>

                <div>
                  <label className={labelClass}>Payment Screenshot (upload TrxID screenshot)</label>
                  {paymentScreenshot ? (
                    <div className="flex items-center gap-3">
                      <img alt="Payment screenshot" className="h-16 w-16 rounded-md border object-cover" src={paymentScreenshot} />
                      <button className="text-xs font-bold uppercase tracking-widest text-tertiary" onClick={() => setPaymentScreenshot('')} type="button">
                        Remove
                      </button>
                    </div>
                  ) : (
                    <label className="flex cursor-pointer items-center justify-center rounded-lg border border-dashed border-outline-variant/50 px-4 py-5 text-sm text-on-surface-variant hover:border-tertiary">
                      {uploadingProof ? 'Uploading...' : 'Click to upload screenshot (JPG / PNG, max 5MB)'}
                      <input
                        accept="image/jpeg,image/png,image/webp,image/heic"
                        className="hidden"
                        disabled={uploadingProof}
                        onChange={(event) => {
                          const file = event.target.files?.[0]
                          if (file) handleProofUpload(file)
                          event.target.value = ''
                        }}
                        type="file"
                      />
                    </label>
                  )}
                </div>
              </div>
            )}
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
                      className="flex-1 border-b border-outline-variant/40 bg-transparent px-1 py-2 text-sm uppercase outline-none focus:border-tertiary"
                      onChange={(event) => {
                        setCouponCode(event.target.value)
                        setCouponError('')
                      }}
                      placeholder="Enter coupon code"
                      value={couponCode}
                    />
                    <button
                      className="border border-primary px-5 py-2 text-xs font-bold uppercase tracking-[0.2em] text-primary hover:bg-primary hover:text-white disabled:opacity-50"
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
                <textarea className="w-full border border-outline-variant/40 bg-transparent px-3 py-2.5 text-sm outline-none focus:border-tertiary" onChange={(event) => setOrderNote(event.target.value)} rows={2} value={orderNote} />
              </div>
            </div>
          </section>

          {/* ==================== 5. Review ==================== */}
          <section className="border-t border-outline-variant/20 pt-6">
            <h2 className="mb-4 flex items-center gap-2 font-headline text-lg font-bold">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-tertiary text-xs text-white">5</span>
              Review & Confirm
            </h2>

            <label className="flex items-start gap-3 text-sm text-on-surface-variant">
              <input checked={agreeTerms} className="mt-0.5" onChange={(event) => setAgreeTerms(event.target.checked)} type="checkbox" />
              <span>
                I agree to the <span className="font-semibold text-tertiary">Terms & Conditions</span> and understand that my order will be processed after
                confirmation {isOnlinePayment && 'and payment verification'}.
              </span>
            </label>

            {submitError && <p className="mt-3 rounded-md bg-destructive/10 px-4 py-3 text-sm text-destructive">{submitError}</p>}

            <button
              className="mt-6 inline-flex w-full items-center justify-center gap-2 bg-accent py-4 text-xs font-bold uppercase tracking-[0.25em] text-white transition hover:bg-accent-600 disabled:opacity-70"
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
                        {item.quantity} × {currency(salePrice(item.product.price, item.product.discount))}
                        {item.size ? ` · ${item.size}` : ''}
                        {item.color ? ` · ${item.color}` : ''}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-semibold">{currency(salePrice(item.product.price, item.product.discount) * item.quantity)}</p>
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
                  <span className="text-on-surface-variant">VAT (5%)</span>
                  <span className="font-semibold">{currency(tax)}</span>
                </div>
                <div className="flex items-end justify-between border-t border-outline-variant/20 pt-4">
                  <span className="font-headline text-lg font-bold">Total</span>
                  <span className="font-headline text-2xl font-extrabold">{currency(total)}</span>
                </div>
                {isOnlinePayment && selectedPaymentMethod?.config?.extraFeePercent ? (
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
