import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { CheckCircle, AlertCircle, Edit3, X, Loader2 } from 'lucide-react'
import { useAppDispatch, useAppSelector } from '../../store/hooks'
import { setAuthUser } from '../../store/slices/authSlice'
import { useUpdateMyProfileMutation } from '../../store/services/commerceApi'
import { SEO } from '../../components/common/SEO'

const BD_PHONE_REGEX = /^(\+880|0)[1-9]\d{9}$/

const DashboardProfilePage = () => {
  const dispatch = useAppDispatch()
  const { user, loading: authLoading } = useAppSelector((state) => state.auth)

  const [isEditing, setIsEditing] = useState(false)
  const [name, setName] = useState('')
  const [phone, setPhone] = useState('')
  const [shippingArea, setShippingArea] = useState('')
  const [shippingAddress, setShippingAddress] = useState('')
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [phoneError, setPhoneError] = useState('')
  const [updateMyProfile] = useUpdateMyProfileMutation()

  useEffect(() => {
    setName(user?.name || '')
    setPhone(user?.phone || '')
    setShippingArea(user?.shippingArea || '')
    setShippingAddress(user?.shippingAddress || '')
  }, [user])

  const validatePhone = (value: string): boolean => {
    if (!value.trim()) {
      setPhoneError('Phone number is required')
      return false
    }
    if (!BD_PHONE_REGEX.test(value.trim())) {
      setPhoneError('Please enter a valid Bangladeshi phone number (e.g. 01712345678)')
      return false
    }
    setPhoneError('')
    return true
  }

  const handleCancel = () => {
    setName(user?.name || '')
    setPhone(user?.phone || '')
    setShippingArea(user?.shippingArea || '')
    setShippingAddress(user?.shippingAddress || '')
    setPhoneError('')
    setError(null)
    setMessage(null)
    setIsEditing(false)
  }

  const submitProfile = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setSaving(true)
    setError(null)
    setMessage(null)

    if (!validatePhone(phone)) {
      setSaving(false)
      return
    }

    try {
      const updated = await updateMyProfile({
        name: name.trim(),
        phone: phone.trim(),
        shippingArea: shippingArea.trim() || undefined,
        shippingAddress: shippingAddress.trim() || undefined,
      }).unwrap()
      dispatch(setAuthUser(updated))
      setMessage('Profile updated successfully.')
      setIsEditing(false)
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Failed to update profile')
    } finally {
      setSaving(false)
    }
  }

  if (authLoading && !user) {
    return (
      <div>
        <SEO title="My Profile" description="Manage your profile information." url="/dashboard/profile" />
        <div className="animate-pulse space-y-6">
          <div className="h-8 w-48 rounded-lg bg-slate-200" />
          <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="rounded-xl border border-slate-200 p-4">
                <div className="mb-2 h-4 w-24 rounded bg-slate-200" />
                <div className="h-10 w-full rounded-lg bg-slate-100" />
              </div>
            ))}
          </div>
        </div>
      </div>
    )
  }

  return (
    <div>
      <SEO title="My Profile" description="Manage your profile information and personal details." url="/dashboard/profile" />

      <header className="mb-6 flex items-center justify-between">
        <div>
          <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Profile</p>
          <h2 className="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Personal Information</h2>
        </div>
        {!isEditing && (
          <button
            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
            onClick={() => setIsEditing(true)}
            type="button"
          >
            <Edit3 size={16} />
            Edit Profile
          </button>
        )}
      </header>

      {message && (
        <div className="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
          <CheckCircle size={18} className="shrink-0 text-emerald-500" />
          {message}
        </div>
      )}

      {error && (
        <div className="mb-6 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          <AlertCircle size={18} className="shrink-0 text-red-500" />
          {error}
        </div>
      )}

      <form className="space-y-5" onSubmit={submitProfile}>
        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
          <div>
            <label className="mb-2 block text-xs font-semibold text-slate-500">Full Name</label>
            <input
              className={`w-full rounded-xl border px-4 py-3 text-sm transition ${
                isEditing
                  ? 'border-slate-300 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10'
                  : 'border-slate-100 bg-slate-50'
              }`}
              disabled={!isEditing}
              onChange={(event) => setName(event.target.value)}
              required
              value={name}
            />
          </div>

          <div>
            <label className="mb-2 block text-xs font-semibold text-slate-500">Phone Number</label>
            <input
              className={`w-full rounded-xl border px-4 py-3 text-sm transition ${
                phoneError
                  ? 'border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/10'
                  : isEditing
                    ? 'border-slate-300 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10'
                    : 'border-slate-100 bg-slate-50'
              }`}
              disabled={!isEditing}
              onChange={(event) => {
                setPhone(event.target.value)
                if (phoneError) validatePhone(event.target.value)
              }}
              onBlur={() => {
                if (isEditing && phone) validatePhone(phone)
              }}
              placeholder="01XXXXXXXXX"
              required
              value={phone}
            />
            {phoneError && (
              <p className="mt-1.5 flex items-center gap-1.5 text-xs text-red-600">
                <AlertCircle size={12} />
                {phoneError}
              </p>
            )}
          </div>

          <div>
            <label className="mb-2 block text-xs font-semibold text-slate-500">Shipping Area</label>
            <input
              className={`w-full rounded-xl border px-4 py-3 text-sm transition ${
                isEditing
                  ? 'border-slate-300 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10'
                  : 'border-slate-100 bg-slate-50'
              }`}
              disabled={!isEditing}
              onChange={(event) => setShippingArea(event.target.value)}
              placeholder="e.g. Dhaka"
              value={shippingArea}
            />
          </div>

          <div>
            <label className="mb-2 block text-xs font-semibold text-slate-500">Shipping Address</label>
            <input
              className={`w-full rounded-xl border px-4 py-3 text-sm transition ${
                isEditing
                  ? 'border-slate-300 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10'
                  : 'border-slate-100 bg-slate-50'
              }`}
              disabled={!isEditing}
              onChange={(event) => setShippingAddress(event.target.value)}
              placeholder="e.g. House 123, Road 456"
              value={shippingAddress}
            />
          </div>
        </div>

        {isEditing && (
          <div className="flex items-center gap-3 pt-2">
            <button
              className="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-6 py-3 text-sm font-bold text-white transition hover:bg-slate-800 disabled:opacity-60"
              disabled={saving}
              type="submit"
            >
              {saving ? (
                <>
                  <Loader2 size={16} className="animate-spin" />
                  Saving...
                </>
              ) : (
                'Save Changes'
              )}
            </button>
            <button
              className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
              disabled={saving}
              onClick={handleCancel}
              type="button"
            >
              <X size={16} />
              Cancel
            </button>
          </div>
        )}
      </form>
    </div>
  )
}

export default DashboardProfilePage