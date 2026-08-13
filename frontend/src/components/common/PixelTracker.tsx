import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'
import { initPixel, trackPageView } from '../../lib/pixel'
import { useGetTrackingConfigQuery } from '../../store/services/commerceApi'

const PixelTracker = () => {
  const location = useLocation()
  const { data: config } = useGetTrackingConfigQuery()

  useEffect(() => {
    initPixel(config)
  }, [config])

  useEffect(() => {
    trackPageView(location.pathname + location.search)
  }, [location.pathname, location.search])

  return null
}

export default PixelTracker