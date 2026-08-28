import { useEffect, useState } from 'react'
import { Wifi, WifiOff } from 'lucide-react'
import { useAppDispatch } from '@/store/hooks'
import { baseApi } from '@/store/services/api'

export const NetworkStatus = () => {
  const [isOnline, setIsOnline] = useState(typeof navigator !== 'undefined' ? navigator.onLine : true)
  const [showReconnected, setShowReconnected] = useState(false)
  const dispatch = useAppDispatch()

  useEffect(() => {
    const handleOnline = () => {
      setIsOnline(true)
      setShowReconnected(true)
      // Invalidate RTK Query tags to refresh stale data smoothly in the background
      dispatch(baseApi.util.invalidateTags(['Products', 'Categories', 'Brands', 'Settings']))
      const timer = window.setTimeout(() => setShowReconnected(false), 3500)
      return () => clearTimeout(timer)
    }

    const handleOffline = () => {
      setIsOnline(false)
      setShowReconnected(false)
    }

    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)

    return () => {
      window.removeEventListener('online', handleOnline)
      window.removeEventListener('offline', handleOffline)
    }
  }, [dispatch])

  if (isOnline && !showReconnected) return null

  return (
    <div
      aria-live="polite"
      className={`fixed bottom-20 left-1/2 z-[300] -translate-x-1/2 transform rounded-full px-4 py-2 text-xs font-semibold shadow-lift transition-all duration-300 sm:bottom-6 ${
        !isOnline
          ? 'flex items-center gap-2 border border-amber-300 bg-amber-500 text-amber-950'
          : 'flex items-center gap-2 border border-emerald-300 bg-emerald-500 text-white'
      }`}
    >
      {!isOnline ? (
        <>
          <WifiOff className="h-4 w-4 animate-pulse" />
          <span>Offline mode &bull; Viewing cached content</span>
        </>
      ) : (
        <>
          <Wifi className="h-4 w-4" />
          <span>Back online &bull; Synchronized latest data</span>
        </>
      )}
    </div>
  )
}

export default NetworkStatus
