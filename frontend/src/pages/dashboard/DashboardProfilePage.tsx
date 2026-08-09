import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { api } from '../../lib/api'
import { useAppDispatch, useAppSelector } from '../../store/hooks'
import { fetchMyProfile } from '../../store/slices/authSlice'
import { SEO } from '../../components/common/SEO'

const DashboardProfilePage = () => {
  const dispatch = useAppDispatch()
  const { user } = useAppSelector((state) => state.auth)

  const [name, setName] = useState('')
  const [phone, setPhone] = useState('')
  const [shippingArea, setShippingArea] = useState('')
  const [shippingAddress, setShippingAddress] = useState('')
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    setName(user?.name || '')
    setPhone(user?.phone || '')
    setShippingArea(user?.shippingArea || '')
    setShippingAddress(user?.shippingAddress || '')
  }, [user])

  const submitProfile = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setSaving(true)
    setError(null)
    setMessage(null)

    try {
      await api.updateMyProfile({
        name,
        phone,
        shippingArea,
        shippingAddress,
      })
      await dispatch(fetchMyProfile({ force: true }))
      setMessage('Profile updated successfully.')
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Failed to update profile')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div>
      <SEO title="My Profile" description="Manage your profile information and personal details." url="/dashboard/profile" />
      <header className="mb-6">
        <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Profile</p>
        <h2 className="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Personal Information</h2>
      </header>

      <form className="grid grid-cols-1 gap-5 md:grid-cols-2" onSubmit={submitProfile}>
        <div className="rounded-xl border border-slate-200 p-4">
          <label className="mb-2 block text-xs font-semibold text-slate-500">Full Name</label>
          <input
            className="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none transition focus:border-slate-900"
            onChange={(event) => setName(event.target.value)}
            required
            value={name}
          />
        </div>

        <div className="rounded-xl border border-slate-200 p-4">
          <label className="mb-2 block text-xs font-semibold text-slate-500">Phone Number</label>
          <input
            className="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none transition focus:border-slate-900"
            onChange={(event) => setPhone(event.target.value)}
            required
            value={phone}
          />
        </div>

        <div className="rounded-xl border border-slate-200 p-4">
          <label className="mb-2 block text-xs font-semibold text-slate-500">Shipping Area</label>
          <input
            className="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none transition focus:border-slate-900"
            onChange={(event) => setShippingArea(event.target.value)}
            value={shippingArea}
          />
        </div>

        <div className="rounded-xl border border-slate-200 p-4">
          <label className="mb-2 block text-xs font-semibold text-slate-500">Shipping Address</label>
          <input
            className="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none transition focus:border-slate-900"
            onChange={(event) => setShippingAddress(event.target.value)}
            value={shippingAddress}
          />
        </div>

        <div className="md:col-span-2">
          {error && <p className="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}
          {message && <p className="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{message}</p>}

          <button
            className="inline-flex rounded-lg bg-slate-900 px-5 py-3 text-sm font-bold uppercase tracking-[0.15em] text-white disabled:opacity-60"
            disabled={saving}
            type="submit"
          >
            {saving ? 'Saving...' : 'Save Profile'}
          </button>
        </div>
      </form>
    </div>
  )
}

export default DashboardProfilePage
