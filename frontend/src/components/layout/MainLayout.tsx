import { Outlet } from 'react-router-dom'
import CartDrawer from '../common/CartDrawer'
import MobileBottomNav from '../common/MobileBottomNav'
import { useThemeSync } from '../../lib/useThemeSync'
import PixelTracker from '../common/PixelTracker'
import WhatsAppButton from '../common/WhatsAppButton'
import SiteFooter from './SiteFooter'
import SiteNavbar from './SiteNavbar'

const MainLayout = () => {
  useThemeSync()

  return (
    <div className="flex min-h-screen flex-col overflow-x-hidden bg-background font-body text-foreground transition-colors">
      <a className="skip-link" href="#main-content">
        Skip to main content
      </a>
      <SiteNavbar />
      <main className="flex-1" id="main-content" tabIndex={-1}>
        <Outlet />
      </main>
      <SiteFooter />
      <CartDrawer />
      <MobileBottomNav />
      <WhatsAppButton />
      <PixelTracker />
    </div>
  )
}

export default MainLayout
