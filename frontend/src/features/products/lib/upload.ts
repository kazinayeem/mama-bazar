import { authStorage } from '@/lib/authStorage'
import type { MediaAsset } from '@/types/admin'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:5000'

export interface UploadProgress {
  loaded: number
  total: number
  percent: number
}

/** Upload files to the backend media endpoint (Cloudinary-backed) with real progress tracking. */
export const uploadFilesWithProgress = (
  files: File[],
  folder: string,
  onProgress?: (percent: number, file: File) => void,
): Promise<MediaAsset[]> =>
  new Promise((resolve, reject) => {
    const formData = new FormData()
    formData.append('folder', folder)
    files.forEach((f) => formData.append('files', f))

    const xhr = new XMLHttpRequest()
    xhr.open('POST', `${API_BASE}/api/media/upload/multiple`)

    const token = authStorage.getToken()
    if (token) xhr.setRequestHeader('Authorization', `Bearer ${token}`)

    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable && onProgress) {
        onProgress(Math.round((e.loaded / e.total) * 100), files[0])
      }
    }
    xhr.onerror = () => reject(new Error('Upload failed — network error'))
    xhr.ontimeout = () => reject(new Error('Upload timed out'))
    xhr.onload = () => {
      let parsed: { success?: boolean; data?: MediaAsset | MediaAsset[]; message?: string }
      try {
        parsed = JSON.parse(xhr.responseText)
      } catch {
        reject(new Error('Server returned invalid JSON'))
        return
      }
      if (xhr.status >= 200 && xhr.status < 300 && parsed.success) {
        const data = parsed.data
        resolve(Array.isArray(data) ? data : data ? [data] : [])
      } else {
        reject(new Error(parsed.message || `Upload failed (${xhr.status})`))
      }
    }
    xhr.send(formData)
  })
