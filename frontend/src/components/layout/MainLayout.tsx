import { Outlet } from 'react-router-dom'
import CartDrawer from '../common/CartDrawer'
import MobileBottomNav from '../common/MobileBottomNav'
import { useThemeSync } from '../../lib/useThemeSync'
import SiteFooter from './SiteFooter'
import SiteNavbar from './SiteNavbar'

const MainLayout = () => {
  useThemeSync()

  return (
    <div className="min-h-screen overflow-x-hidden bg-white font-body text-slate-900 transition-colors dark:bg-slate-950 dark:text-white">
      <SiteNavbar />
      <Outlet />
      <SiteFooter />
      <CartDrawer />
      <MobileBottomNav />
    </div>
  )
}

export default MainLayout
