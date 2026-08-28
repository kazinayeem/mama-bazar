import { AnimatePresence } from 'framer-motion'
import { Outlet, useLocation } from 'react-router-dom'
import CartDrawer from '../common/CartDrawer'
import MobileBottomNav from '../common/MobileBottomNav'
import PageTransition from '../common/PageTransition'
import { useThemeSync } from '../../lib/useThemeSync'
import PixelTracker from '../common/PixelTracker'
import WhatsAppButton from '../common/WhatsAppButton'
import SiteFooter from './SiteFooter'
import SiteNavbar from './SiteNavbar'

const MainLayout = () => {
  useThemeSync()
  const location = useLocation()

  return (
    <div className="flex min-h-screen flex-col bg-background font-body text-foreground transition-colors">
      <a className="skip-link" href="#main-content">
        Skip to main content
      </a>
      <SiteNavbar />
      <main className="flex-1 overflow-x-hidden" id="main-content" tabIndex={-1}>
        <AnimatePresence initial={false} mode="wait">
          <PageTransition key={location.pathname}>
            <Outlet />
          </PageTransition>
        </AnimatePresence>
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
