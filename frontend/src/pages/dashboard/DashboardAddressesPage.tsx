import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import LoadingBlock from '../../components/common/LoadingBlock'
import { api } from '../../lib/api'
import { useAppSelector } from '../../store/hooks'
import type { UserAddress } from '../../types'
import { SEO } from '../../components/common/SEO'

const DashboardAddressesPage = () => {
  const { user } = useAppSelector((state) => state.auth)
  const [addresses, setAddresses] = useState<UserAddress[]>([])
  const [addressesLoading, setAddressesLoading] = useState(false)
  const [addressSaving, setAddressSaving] = useState(false)
  const [addressMessage, setAddressMessage] = useState<string | null>(null)
  const [addressError, setAddressError] = useState<string | null>(null)
  const [recipientName, setRecipientName] = useState('')
  const [addressPhone, setAddressPhone] = useState('')
  const [addressArea, setAddressArea] = useState('')
  const [addressText, setAddressText] = useState('')

  const loadAddresses = async () => {
    setAddressesLoading(true)
    setAddressError(null)
    try {
      const list = await api.getMyAddresses()
      setAddresses(list)
    } catch (loadError) {
      setAddressError(loadError instanceof Error ? loadError.message : 'Failed to load addresses')
    } finally {
      setAddressesLoading(false)
    }
  }

  useEffect(() => {
    setRecipientName(user?.name || '')
    setAddressPhone(user?.phone || '')
  }, [user])

  useEffect(() => {
    loadAddresses()
  }, [])

  const submitAddress = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setAddressSaving(true)
    setAddressError(null)
    setAddressMessage(null)

    try {
      if (addresses.length >= 5) {
        throw new Error('Maximum 5 addresses allowed')
      }

      const list = await api.createMyAddress({
        recipientName,
        phone: addressPhone,
        shippingArea: addressArea,
        address: addressText,
      })

      setAddresses(list)
      setAddressArea('')
      setAddressText('')
      setAddressMessage('Address added successfully.')
    } catch (submitError) {
      setAddressError(submitError instanceof Error ? submitError.message : 'Failed to add address')
    } finally {
      setAddressSaving(false)
    }
  }

  const setDefaultAddress = async (id: number) => {
    setAddressError(null)
    setAddressMessage(null)
    try {
      const list = await api.updateMyAddress(id, { isDefault: true })
      setAddresses(list)
      setAddressMessage('Default address updated.')
    } catch (setDefaultError) {
      setAddressError(setDefaultError instanceof Error ? setDefaultError.message : 'Failed to update default address')
    }
  }

  const removeAddress = async (id: number) => {
    setAddressError(null)
    setAddressMessage(null)
    try {
      const list = await api.deleteMyAddress(id)
      setAddresses(list)
      setAddressMessage('Address removed.')
    } catch (removeError) {
      setAddressError(removeError instanceof Error ? removeError.message : 'Failed to remove address')
    }
  }

  return (
    <div>
      <SEO title="My Addresses" description="Manage your shipping addresses." url="/dashboard/addresses" />
      <header className="mb-6">
        <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Addresses</p>
        <h2 className="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Address Book</h2>
        <p className="mt-2 text-sm text-slate-600">Add up to 5 saved addresses for faster checkout and fewer form fills.</p>
      </header>

      <div className="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <form className="rounded-xl border border-slate-200 p-4" onSubmit={submitAddress}>
          <h3 className="text-lg font-bold text-slate-900">Add New Address</h3>

          <div className="mt-4 space-y-4">
            <div>
              <label className="mb-2 block text-xs font-semibold text-slate-500">Recipient Name</label>
              <input
                className="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none transition focus:border-slate-900"
                onChange={(event) => setRecipientName(event.target.value)}
                required
                value={recipientName}
              />
            </div>
            <div>
              <label className="mb-2 block text-xs font-semibold text-slate-500">Phone</label>
              <input
                className="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none transition focus:border-slate-900"
                onChange={(event) => setAddressPhone(event.target.value)}
                required
                value={addressPhone}
              />
            </div>
            <div>
              <label className="mb-2 block text-xs font-semibold text-slate-500">Area</label>
              <input
                className="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none transition focus:border-slate-900"
                onChange={(event) => setAddressArea(event.target.value)}
                required
                value={addressArea}
              />
            </div>
            <div>
              <label className="mb-2 block text-xs font-semibold text-slate-500">Address</label>
              <input
                className="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none transition focus:border-slate-900"
                onChange={(event) => setAddressText(event.target.value)}
                required
                value={addressText}
              />
            </div>
          </div>

          {addressError && <p className="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{addressError}</p>}
          {addressMessage && <p className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{addressMessage}</p>}

          <button
            className="mt-5 inline-flex rounded-lg bg-slate-900 px-5 py-3 text-sm font-bold uppercase tracking-[0.15em] text-white disabled:opacity-60"
            disabled={addressSaving || addresses.length >= 5}
            type="submit"
          >
            {addressSaving ? 'Adding...' : addresses.length >= 5 ? 'Address Limit Reached' : 'Add Address'}
          </button>
        </form>

        <section className="rounded-xl border border-slate-200 p-4">
          <h3 className="text-lg font-bold text-slate-900">Saved Addresses</h3>

          {addressesLoading ? (
            <div className="mt-4">
              <LoadingBlock label="Loading addresses" />
            </div>
          ) : addresses.length === 0 ? (
            <p className="mt-4 text-sm text-slate-500">No saved addresses yet.</p>
          ) : (
            <div className="mt-4 space-y-3">
              {addresses.map((item) => (
                <article className="rounded-lg border border-slate-200 p-3" key={item.id}>
                  <div className="flex items-center justify-between gap-2">
                    <p className="font-semibold text-slate-900">{item.recipientName}</p>
                    {item.isDefault ? <span className="text-xs font-bold uppercase tracking-[0.12em] text-indigo-700">Default</span> : null}
                  </div>
                  <p className="mt-1 text-sm text-slate-600">{item.phone}</p>
                  <p className="mt-1 text-sm text-slate-600">{item.shippingArea}</p>
                  <p className="mt-1 text-sm text-slate-600">{item.address}</p>

                  <div className="mt-3 flex flex-wrap gap-3">
                    {!item.isDefault ? (
                      <button className="text-xs font-bold uppercase tracking-[0.12em] text-indigo-700" onClick={() => setDefaultAddress(item.id)} type="button">
                        Set Default
                      </button>
                    ) : null}
                    <button className="text-xs font-bold uppercase tracking-[0.12em] text-red-700" onClick={() => removeAddress(item.id)} type="button">
                      Delete
                    </button>
                  </div>
                </article>
              ))}
            </div>
          )}
        </section>
      </div>
    </div>
  )
}

export default DashboardAddressesPage
