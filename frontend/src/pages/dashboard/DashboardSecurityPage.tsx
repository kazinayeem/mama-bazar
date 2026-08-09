import { useState } from 'react'
import type { FormEvent } from 'react'
import { api } from '../../lib/api'
import { SEO } from '../../components/common/SEO'

const DashboardSecurityPage = () => {
  const [oldPassword, setOldPassword] = useState('')
  const [newPassword, setNewPassword] = useState('')
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  const submitPassword = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setSaving(true)
    setError(null)
    setMessage(null)

    try {
      const response = await api.changeMyPassword({ oldPassword, newPassword })
      setMessage(response.message)
      setOldPassword('')
      setNewPassword('')
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Failed to change password')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div>
      <SEO title="Security Settings" description="Manage your password and security settings." url="/dashboard/security" />
      <header className="mb-6">
        <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Security</p>
        <h2 className="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Password & Account Access</h2>
        <p className="mt-2 text-sm text-slate-600">
          If your account was auto-created from checkout, your current password is your phone number. Change it now for better security.
        </p>
      </header>

      <form className="max-w-2xl space-y-4" onSubmit={submitPassword}>
        <div className="rounded-xl border border-slate-200 p-4">
          <label className="mb-2 block text-xs font-semibold text-slate-500">Current Password</label>
          <input
            className="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none transition focus:border-slate-900"
            onChange={(event) => setOldPassword(event.target.value)}
            required
            type="password"
            value={oldPassword}
          />
        </div>

        <div className="rounded-xl border border-slate-200 p-4">
          <label className="mb-2 block text-xs font-semibold text-slate-500">New Password</label>
          <input
            className="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none transition focus:border-slate-900"
            minLength={6}
            onChange={(event) => setNewPassword(event.target.value)}
            required
            type="password"
            value={newPassword}
          />
        </div>

        {error && <p className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}
        {message && <p className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{message}</p>}

        <button
          className="inline-flex rounded-lg bg-slate-900 px-5 py-3 text-sm font-bold uppercase tracking-[0.15em] text-white disabled:opacity-60"
          disabled={saving}
          type="submit"
        >
          {saving ? 'Updating...' : 'Update Password'}
        </button>
      </form>
    </div>
  )
}

export default DashboardSecurityPage
