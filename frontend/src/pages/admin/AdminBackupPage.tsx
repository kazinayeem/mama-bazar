import React, { useState, useRef } from 'react'
import {
  Database,
  Download,
  Upload,
  RefreshCw,
  Trash2,
  CheckCircle2,
  AlertTriangle,
  FileArchive,
  ShieldCheck,
  HardDrive,
  Calendar,
  Layers,
} from 'lucide-react'
import {
  useGetBackupsQuery,
  useVerifyBackupPinMutation,
  useCreateBackupMutation,
  useRestoreBackupMutation,
  useDeleteBackupMutation,
  parseError,
} from '../../store/services/adminProductsApi'
import { PermissionGate } from '../../components/admin/PermissionGate'
import { SecurityPinModal } from '../../components/admin/SecurityPinModal'
import { API_BASE_URL } from '../../lib/apiConfig'
import { authStorage } from '../../lib/authStorage'

export const AdminBackupPage: React.FC = () => {
  const { data: backups = [], isLoading: loadingBackups, refetch } = useGetBackupsQuery()
  const [verifyPinMutation] = useVerifyBackupPinMutation()
  const [createBackup, { isLoading: isCreating }] = useCreateBackupMutation()
  const [restoreBackup, { isLoading: isRestoring }] = useRestoreBackupMutation()
  const [deleteBackup, { isLoading: isDeleting }] = useDeleteBackupMutation()

  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [isRestoreModalOpen, setIsRestoreModalOpen] = useState(false)
  const [isPinModalOpen, setIsPinModalOpen] = useState(false)
  const [pinAction, setPinAction] = useState<'create' | 'restore' | null>(null)
  const [deleteConfirmId, setDeleteConfirmId] = useState<number | null>(null)
  const [feedback, setFeedback] = useState<{ type: 'success' | 'error'; message: string } | null>(null)
  const fileInputRef = useRef<HTMLInputElement>(null)

  const formatBytes = (bytes: number) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
  }

  const handleRequestCreateBackup = () => {
    setPinAction('create')
    setIsPinModalOpen(true)
  }

  const handleRequestRestore = () => {
    setPinAction('restore')
    setIsPinModalOpen(true)
  }

  const handleVerifyPin = async (pin: string) => {
    return await verifyPinMutation({ pin }).unwrap()
  }

  const handlePinSuccess = () => {
    const action = pinAction
    setIsPinModalOpen(false)
    setPinAction(null)

    if (action === 'create') {
      executeCreateBackup()
    } else if (action === 'restore') {
      // Programmatically open system file picker
      if (fileInputRef.current) {
        fileInputRef.current.value = ''
        fileInputRef.current.click()
      }
    }
  }

  const executeCreateBackup = async () => {
    setFeedback(null)
    try {
      const result = await createBackup({ type: 'manual' }).unwrap()
      setFeedback({
        type: 'success',
        message: `Backup archive '${result.filename}' generated successfully (${formatBytes(result.size)}).`,
      })
      refetch()

      // Automatically trigger browser download
      downloadBackupFile(result.id, result.filename)
    } catch (err: any) {
      setFeedback({ type: 'error', message: parseError(err) || 'Failed to generate database backup' })
    }
  }

  const downloadBackupFile = async (id: number, filename: string) => {
    const token = authStorage.getToken()
    const url = `${API_BASE_URL}/api/backup/download/${id}`
    try {
      const response = await fetch(url, { headers: { Authorization: `Bearer ${token}` } })

      if (!response.ok) {
        // Try to read a JSON error message if available
        const contentType = response.headers.get('content-type') || ''
        if (contentType.includes('application/json')) {
          const errJson = await response.json().catch(() => null)
          const msg = errJson?.message || `Server error ${response.status}`
          throw new Error(msg)
        }
        throw new Error(`Server returned ${response.status}`)
      }

      // The backend always streams the real binary ZIP — no cross-origin redirect tricks
      const blob = await response.blob()

      // Sanity-check: a real ZIP is at minimum ~22 bytes (empty archive)
      if (blob.size < 22) {
        throw new Error(`Archive too small (${blob.size} bytes). The backup may be corrupted on the server.`)
      }

      const objectUrl = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = objectUrl
      a.download = filename
      a.style.display = 'none'
      document.body.appendChild(a)
      a.click()
      // Small delay before revoking so the browser has time to start the download
      setTimeout(() => {
        a.remove()
        window.URL.revokeObjectURL(objectUrl)
      }, 200)
    } catch (err: any) {
      console.error('Error downloading backup:', err)
      setFeedback({
        type: 'error',
        message: err?.message || 'Unable to download backup archive. Please try again.',
      })
    }
  }

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      const file = e.target.files[0]
      if (!file.name.endsWith('.zip')) {
        alert('Please select a valid MamaBazar backup ZIP archive (.zip)')
        return
      }
      setSelectedFile(file)
      setIsRestoreModalOpen(true)
    }
  }

  const handleRestoreSubmit = async () => {
    if (!selectedFile) return
    setFeedback(null)

    const formData = new FormData()
    formData.append('file', selectedFile)

    try {
      const res = await restoreBackup(formData).unwrap()
      setFeedback({
        type: 'success',
        message: `Database successfully restored (${res.restoredTablesCount} tables, ${res.restoredRecordsCount} records). Pre-restore safety backup preserved: ${res.safetyBackupFilename}`,
      })
      setIsRestoreModalOpen(false)
      setSelectedFile(null)
      if (fileInputRef.current) fileInputRef.current.value = ''
      refetch()
    } catch (err: any) {
      setFeedback({ type: 'error', message: parseError(err) || 'Restore failed. Check backup archive integrity.' })
      setIsRestoreModalOpen(false)
    }
  }

  const handleDelete = async (id: number) => {
    try {
      await deleteBackup(id).unwrap()
      setDeleteConfirmId(null)
      refetch()
    } catch (err: any) {
      alert(parseError(err) || 'Failed to delete backup file')
    }
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
            <Database className="w-7 h-7 text-primary" />
            Database Backup & Restore
          </h1>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Export complete website data archives and safely restore system backups with automatic safety snapshots.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <PermissionGate permission="backup.restore">
            <button
              type="button"
              onClick={handleRequestRestore}
              disabled={isRestoring}
              className="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-xl cursor-pointer transition shadow-sm active:scale-95 disabled:opacity-50"
            >
              <Upload className="w-4 h-4 text-primary" />
              Upload & Restore
            </button>
            <input
              ref={fileInputRef}
              type="file"
              accept=".zip"
              className="hidden"
              onChange={handleFileSelect}
            />
          </PermissionGate>

          <PermissionGate permission="backup.create">
            <button
              type="button"
              onClick={handleRequestCreateBackup}
              disabled={isCreating}
              className="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary/90 disabled:opacity-50 rounded-xl transition shadow-sm active:scale-95"
            >
              {isCreating ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Download className="w-4 h-4" />}
              {isCreating ? 'Generating Backup...' : 'Create Full Backup'}
            </button>
          </PermissionGate>
        </div>
      </div>

      {feedback && (
        <div
          className={`p-4 rounded-xl text-sm font-medium flex items-center gap-3 ${
            feedback.type === 'success'
              ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
              : 'bg-rose-50 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800'
          }`}
        >
          {feedback.type === 'success' ? <CheckCircle2 className="w-5 h-5 shrink-0" /> : <AlertTriangle className="w-5 h-5 shrink-0" />}
          {feedback.message}
        </div>
      )}

      {/* Overview Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
            <FileArchive className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Archives</p>
            <p className="text-xl font-bold text-slate-900 dark:text-white">{backups.length}</p>
          </div>
        </div>

        <div className="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <ShieldCheck className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Safety Status</p>
            <p className="text-xl font-bold text-emerald-600 dark:text-emerald-400">Protected</p>
          </div>
        </div>

        <div className="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
            <Calendar className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Latest Backup</p>
            <p className="text-sm font-bold text-slate-900 dark:text-white truncate max-w-[150px]">
              {backups[0] ? new Date(backups[0].createdAt).toLocaleDateString() : 'None yet'}
            </p>
          </div>
        </div>

        <div className="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
            <HardDrive className="w-6 h-6" />
          </div>
          <div>
            <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tables Covered</p>
            <p className="text-xl font-bold text-slate-900 dark:text-white">40+ Tables</p>
          </div>
        </div>
      </div>

      {/* Safety Banner */}
      <div className="p-4 rounded-2xl bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 flex items-start gap-3">
        <ShieldCheck className="w-5 h-5 text-primary shrink-0 mt-0.5" />
        <div className="text-xs text-slate-600 dark:text-slate-300 space-y-1">
          <p className="font-semibold text-slate-900 dark:text-white">
            Dual-Layer Safety Architecture:
          </p>
          <p>
            1. <strong>Sensitive Data Sanitization:</strong> Backup archives contain complete JSON relational table dumps with sensitive password hashes safely managed.
          </p>
          <p>
            2. <strong>Auto Pre-Restore Snapshot:</strong> Whenever you restore a backup archive, MamaBazar immediately generates an automatic pre-restore safety snapshot so you can roll back at any time.
          </p>
        </div>
      </div>

      {/* Backup History Table */}
      <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div className="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <h3 className="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <Layers className="w-4 h-4 text-primary" /> Backup History & Snapshots
          </h3>
          <button
            onClick={() => refetch()}
            className="text-xs text-slate-500 hover:text-primary flex items-center gap-1 transition"
          >
            <RefreshCw className="w-3.5 h-3.5" /> Refresh List
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
              <tr>
                <th className="px-5 py-3.5">Filename</th>
                <th className="px-5 py-3.5">Type</th>
                <th className="px-5 py-3.5">Archive Size</th>
                <th className="px-5 py-3.5">Tables / Records</th>
                <th className="px-5 py-3.5">Created At</th>
                <th className="px-5 py-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60 font-normal">
              {loadingBackups ? (
                <tr>
                  <td colSpan={6} className="px-5 py-8 text-center text-slate-400">
                    Loading backup archives...
                  </td>
                </tr>
              ) : backups.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-5 py-12 text-center text-slate-400 space-y-2">
                    <Database className="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600" />
                    <p className="font-medium text-slate-600 dark:text-slate-300">No backups generated yet</p>
                    <p className="text-xs text-slate-400">Click &quot;Create Full Backup&quot; to generate your first system snapshot.</p>
                  </td>
                </tr>
              ) : (
                backups.map((backup) => {
                  const isAuto = backup.type === 'safety_auto'

                  return (
                    <tr key={backup.id} className="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                      <td className="px-5 py-4">
                        <div className="flex items-center gap-3">
                          <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${
                            isAuto ? 'bg-amber-100 dark:bg-amber-950/40 text-amber-600' : 'bg-primary/10 text-primary'
                          }`}>
                            <FileArchive className="w-4 h-4" />
                          </div>
                          <div>
                            <p className="font-mono text-xs font-semibold text-slate-900 dark:text-white truncate max-w-[280px]">
                              {backup.filename}
                            </p>
                            <p className="text-[11px] text-slate-400">ID #{backup.id}</p>
                          </div>
                        </div>
                      </td>

                      <td className="px-5 py-4">
                        <span
                          className={`inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full ${
                            isAuto
                              ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 ring-1 ring-amber-300 dark:ring-amber-800'
                              : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                          }`}
                        >
                          {isAuto ? 'Auto Pre-Restore' : 'Manual Full'}
                        </span>
                      </td>

                      <td className="px-5 py-4 font-mono text-xs text-slate-700 dark:text-slate-300">
                        {formatBytes(backup.size)}
                      </td>

                      <td className="px-5 py-4 text-xs text-slate-600 dark:text-slate-400">
                        <span className="font-medium text-slate-900 dark:text-white">{backup.tableCount} tables</span>
                        <span className="text-slate-400"> ({backup.recordCount.toLocaleString()} rows)</span>
                      </td>

                      <td className="px-5 py-4 text-xs text-slate-500 whitespace-nowrap">
                        {new Date(backup.createdAt).toLocaleString()}
                      </td>

                      <td className="px-5 py-4 text-right">
                        <div className="flex items-center justify-end gap-2">
                          <button
                            onClick={() => downloadBackupFile(backup.id, backup.filename)}
                            className="p-1.5 text-slate-600 dark:text-slate-400 hover:text-primary hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition"
                            title="Download Archive (.zip)"
                          >
                            <Download className="w-4 h-4" />
                          </button>

                          <PermissionGate permission="backup.restore">
                            <button
                              onClick={() => setDeleteConfirmId(backup.id)}
                              className="p-1.5 text-slate-600 dark:text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition"
                              title="Delete Archive"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </PermissionGate>
                        </div>
                      </td>
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* RESTORE CONFIRMATION MODAL */}
      {isRestoreModalOpen && selectedFile && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div className="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 animate-in fade-in zoom-in-95">
            <div className="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto">
              <AlertTriangle className="w-6 h-6" />
            </div>

            <div className="text-center">
              <h3 className="text-lg font-bold text-slate-900 dark:text-white">Confirm Database Restore</h3>
              <p className="text-xs text-slate-500 mt-1">
                Archive: <span className="font-mono font-bold text-slate-700 dark:text-slate-300">{selectedFile.name}</span> ({formatBytes(selectedFile.size)})
              </p>
            </div>

            <div className="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 text-xs text-amber-800 dark:text-amber-300 space-y-1.5">
              <p className="font-bold flex items-center gap-1.5">
                <ShieldCheck className="w-4 h-4 text-amber-600 dark:text-amber-400" />
                Automatic Safety Backup Enabled
              </p>
              <p>
                MamaBazar will automatically create a pre-restore safety backup before applying the archive data. This ensures your current database state remains safely preserved.
              </p>
            </div>

            <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
              <button
                type="button"
                onClick={() => {
                  setIsRestoreModalOpen(false)
                  setSelectedFile(null)
                  if (fileInputRef.current) fileInputRef.current.value = ''
                }}
                disabled={isRestoring}
                className="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
              >
                Cancel
              </button>

              <button
                type="button"
                onClick={handleRestoreSubmit}
                disabled={isRestoring}
                className="px-5 py-2 text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 disabled:opacity-50 rounded-xl transition shadow-sm flex items-center gap-2"
              >
                {isRestoring ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Upload className="w-4 h-4" />}
                {isRestoring ? 'Restoring Database...' : 'Proceed with Restore'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* SECURITY PIN CHECK MODAL */}
      <SecurityPinModal
        isOpen={isPinModalOpen}
        onClose={() => {
          setIsPinModalOpen(false)
          setPinAction(null)
        }}
        onSuccess={handlePinSuccess}
        onVerify={handleVerifyPin}
        title={pinAction === 'restore' ? 'Authorize Database Restore' : 'Authorize Backup Creation'}
        subtitle="For your safety, enter the backup security PIN before continuing."
        actionLabel={pinAction === 'restore' ? 'Unlock & Choose File' : 'Start Backup'}
      />

      {/* DELETE MODAL */}
      {deleteConfirmId !== null && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div className="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl text-center space-y-4 animate-in fade-in zoom-in-95">
            <div className="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
              <Trash2 className="w-6 h-6" />
            </div>
            <h3 className="text-lg font-bold text-slate-900 dark:text-white">Delete Backup Archive?</h3>
            <p className="text-sm text-slate-500 dark:text-slate-400">
              Are you sure you want to permanently delete this backup archive from server storage?
            </p>
            <div className="flex items-center justify-center gap-3 pt-2">
              <button
                onClick={() => setDeleteConfirmId(null)}
                className="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
              >
                Cancel
              </button>
              <button
                onClick={() => handleDelete(deleteConfirmId)}
                disabled={isDeleting}
                className="px-5 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 rounded-xl transition shadow-sm"
              >
                {isDeleting ? 'Deleting...' : 'Yes, Delete'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default AdminBackupPage
