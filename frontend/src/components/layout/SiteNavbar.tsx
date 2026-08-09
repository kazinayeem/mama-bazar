import { AnimatePresence, motion } from 'framer-motion'
import { ChevronDown, ChevronRight, Heart, LogIn, Menu, Moon, ShoppingBag, Sun, User, X, Zap } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import { useAppDispatch, useAppSelector } from '../../store/hooks'
import { fetchMyProfile, logout } from '../../store/slices/authSlice'
import { openCart, toggleTheme } from '../../store/slices/uiSlice'
import { useGetCategoriesQuery, useGetHomepageQuery } from '../../store/services/commerceApi'
import SearchBar from '../common/SearchBar'
import { formatPrice } from '../../lib/format'

const SiteNavbar = () => {
  const dispatch = useAppDispatch()
  const navigate = useNavigate()
  const theme = useAppSelector((state) => state.ui.theme)
  const cartCount = useAppSelector((state) => state.cart.items.reduce((sum, item) => sum + item.quantity, 0))
  const subtotal = useAppSelector((state) => state.cart.items.reduce((sum, item) => sum + Number(item.product.price) * item.quantity, 0))
  const wishlistCount = useAppSelector((state) => state.ui.wishlist.length)
  const token = useAppSelector((state) => state.auth.token)
  const authUser = useAppSelector((state) => state.auth.user)

  const [mobileOpen, setMobileOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)

  const categoriesQuery = useGetCategoriesQuery()
  const categories = categoriesQuery.data || []
  const { data: homepageData } = useGetHomepageQuery()
  const announcement = homepageData?.announcement

  // Filter top-level parent categories
  const parentCategories = categories.filter((c) => !c.parentId)

  useEffect(() => {
    if (token && !authUser) {
      dispatch(fetchMyProfile())
    }
  }, [authUser, dispatch, token])

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  useEffect(() => {
    document.body.style.overflow = mobileOpen ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [mobileOpen])

  const iconButton =
    'relative flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200'

  const cartButtonClass =
    'relative flex h-10 items-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 text-slate-700 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200'

  return (
    <>
      <header
        className={`sticky top-0 z-50 border-b transition-all duration-300 ${
          scrolled
            ? 'border-slate-200/80 bg-white/95 shadow-soft backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-900/95'
            : 'border-slate-100 bg-white dark:border-slate-800 dark:bg-slate-900'
        }`}
      >
        {/* Announcement bar */}
        {announcement?.enabled && announcement.text && (
          <div
            className="text-white dark:bg-slate-950"
            style={{
              backgroundColor: announcement.backgroundColor || '#1e293b',
              color: announcement.textColor || '#ffffff',
            }}
          >
            <div className="mx-auto flex max-w-7xl items-center justify-center gap-2 px-4 py-1.5 text-center text-[11px] font-medium sm:text-xs">
              <Zap size={12} className="fill-accent text-accent" />
              <span className="truncate">{announcement.text}</span>
            </div>
          </div>
        )}

        {/* Top Header */}
        <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
          <div className="flex items-center gap-3">
            <button
              aria-label="Toggle menu"
              className="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-700 lg:hidden dark:border-slate-700 dark:text-slate-200"
              onClick={() => setMobileOpen((prev) => !prev)}
              type="button"
            >
              {mobileOpen ? <X size={18} /> : <Menu size={18} />}
            </button>

            <NavLink aria-label="Mama Bazar home" className="flex items-center gap-2" to="/">
              <img alt="Mama Bazar logo" className="h-24 w-auto object-contain" src="/brandlogo.png" />
             
            </NavLink>
          </div>

          {/* Search bar - Desktop */}
          <div className="hidden lg:block w-full max-w-xl mx-4">
            <SearchBar />
          </div>

          {/* Quick links & Cart */}
          <div className="flex items-center gap-2">
            <Link
              to="/dashboard/orders"
              className="hidden xl:inline-flex items-center text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-primary dark:text-slate-400 dark:hover:text-white mr-2"
            >
              Track Order
            </Link>

            <button
              aria-label={theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'}
              className={iconButton}
              onClick={() => dispatch(toggleTheme())}
              type="button"
            >
              {theme === 'light' ? <Moon size={17} /> : <Sun size={17} />}
            </button>

            <NavLink aria-label="Wishlist" className={`${iconButton} hidden sm:flex`} to="/shop">
              <Heart size={17} />
              {wishlistCount > 0 && (
                <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-accent px-1 text-[10px] font-bold text-white">
                  {wishlistCount}
                </span>
              )}
            </NavLink>

            <button aria-label="Open cart" className={cartButtonClass} onClick={() => dispatch(openCart())} type="button">
              <ShoppingBag size={17} />
              <span className="hidden md:inline text-xs font-bold tracking-tight">{formatPrice(subtotal)}</span>
              {cartCount > 0 && (
                <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-white">
                  {cartCount}
                </span>
              )}
            </button>

            {authUser ? (
              <button
                aria-label="Account"
                className={iconButton}
                onClick={() => navigate(authUser.role === 'admin' || authUser.role === 'manager' ? '/admin/dashboard' : '/dashboard')}
                type="button"
              >
                <User size={17} />
              </button>
            ) : (
              <NavLink aria-label="Sign in" className={`${iconButton} hidden sm:flex`} to="/auth/login">
                <LogIn size={17} />
              </NavLink>
            )}
          </div>
        </div>

        {/* Mobile search */}
        <div className="px-4 pb-3 lg:hidden">
          <SearchBar onNavigate={() => setMobileOpen(false)} />
        </div>

        {/* Second Navigation - Desktop */}
        <div className="hidden lg:block border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/40">
          <div className="mx-auto flex h-11 w-full max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8 text-sm">
            {/* Categories Dropdown */}
            <div className="relative group py-2">
              <button className="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 hover:text-primary transition">
                <Menu size={15} />
                All Categories
                <ChevronDown size={13} />
              </button>
              {/* Dropdown Menu */}
              <div className="absolute left-0 top-full z-50 hidden group-hover:block w-64 rounded-2xl border border-slate-100 bg-white p-2.5 shadow-lift dark:border-slate-850 dark:bg-slate-900">
                <div className="max-h-[350px] overflow-y-auto pr-1">
                  {parentCategories.map((cat) => (
                    <Link
                      key={cat.id}
                      to={`/shop?category=${cat.slug}`}
                      className="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-primary/5 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800 transition"
                    >
                      {cat.image ? (
                        <img alt="" className="h-5 w-5 rounded object-cover shrink-0" src={cat.image} />
                      ) : (
                        <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-primary/10 text-[9px] font-black text-primary">
                          {cat.name.slice(0, 1)}
                        </span>
                      )}
                      <span className="truncate">{cat.name}</span>
                    </Link>
                  ))}
                </div>
              </div>
            </div>

            <span className="h-4 w-px bg-slate-200 dark:bg-slate-800" />

            {/* Menu items */}
            <nav className="flex flex-1 items-center gap-5" aria-label="Main Navigation">
              <NavLink
                to="/"
                className={({ isActive }) =>
                  `text-xs font-bold uppercase tracking-wider transition ${
                    isActive ? 'text-primary' : 'text-slate-600 hover:text-primary dark:text-slate-350 dark:hover:text-white'
                  }`
                }
              >
                Home
              </NavLink>
              <NavLink
                to="/shop"
                className={({ isActive }) =>
                  `text-xs font-bold uppercase tracking-wider transition ${
                    isActive ? 'text-primary' : 'text-slate-600 hover:text-primary dark:text-slate-350 dark:hover:text-white'
                  }`
                }
              >
                Shop
              </NavLink>
              <NavLink
                to="/shop?sale=true"
                className="text-xs font-bold uppercase tracking-wider text-slate-600 hover:text-primary dark:text-slate-350 dark:hover:text-white transition"
              >
                Deals
              </NavLink>
              <NavLink
                to="/brands"
                className="text-xs font-bold uppercase tracking-wider text-slate-600 hover:text-primary dark:text-slate-350 dark:hover:text-white transition"
              >
                Brands
              </NavLink>
              <a
                href="#about"
                className="text-xs font-bold uppercase tracking-wider text-slate-600 hover:text-primary dark:text-slate-350 dark:hover:text-white transition"
              >
                About
              </a>
              <a
                href="#contact"
                className="text-xs font-bold uppercase tracking-wider text-slate-600 hover:text-primary dark:text-slate-350 dark:hover:text-white transition"
              >
                Contact
              </a>
            </nav>
          </div>
        </div>
      </header>

      {/* Mobile drawer */}
      <AnimatePresence>
        {mobileOpen && (
          <>
            <motion.div
              animate={{ opacity: 1 }}
              className="fixed inset-0 z-[200] bg-slate-950/50 backdrop-blur-sm lg:hidden"
              exit={{ opacity: 0 }}
              initial={{ opacity: 0 }}
              onClick={() => setMobileOpen(false)}
            />
            <motion.aside
              animate={{ x: 0 }}
              className="fixed inset-y-0 left-0 z-[200] w-[82%] max-w-sm overflow-y-auto bg-white shadow-lift dark:bg-slate-900 lg:hidden"
              exit={{ x: '-100%' }}
              initial={{ x: '-100%' }}
              transition={{ duration: 0.3, ease: [0.22, 1, 0.36, 1] }}
            >
              <div className="flex items-center justify-between border-b border-slate-100 p-5 dark:border-slate-800">
                <NavLink className="flex items-center gap-2" onClick={() => setMobileOpen(false)} to="/">
                  <img alt="Mama Bazar logo" className="h-8 w-auto object-contain" src="/brandlogo.png" />
                  <span className="font-headline text-lg font-extrabold text-slate-900 dark:text-white">
                    Mama<span className="text-primary">Bazar</span>
                  </span>
                </NavLink>
                <button
                  aria-label="Close menu"
                  className="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                  onClick={() => setMobileOpen(false)}
                  type="button"
                >
                  <X size={16} />
                </button>
              </div>

              {/* Mobile Drawer Main Nav Links */}
              <nav className="p-5 border-b border-slate-100 dark:border-slate-800" aria-label="Mobile Navigation">
                {[
                  { label: 'Home', to: '/' },
                  { label: 'Shop', to: '/shop' },
                  { label: 'Deals/Offers', to: '/shop?sale=true' },
                  { label: 'Track Order', to: '/dashboard/orders' },
                  { label: 'Wishlist', to: '/shop' },
                ].map((link) => (
                  <NavLink
                    className={({ isActive }) =>
                      `mb-1 flex items-center justify-between rounded-xl px-4 py-2.5 text-[14px] font-semibold transition ${
                        isActive ? 'bg-primary/10 text-primary' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800'
                      }`
                    }
                    key={link.label}
                    onClick={() => setMobileOpen(false)}
                    to={link.to}
                  >
                    {link.label}
                    <ChevronRight size={16} className="text-slate-400" />
                  </NavLink>
                ))}
              </nav>

              {/* Mobile Drawer Categories */}
              <div className="p-5">
                <p className="mb-3 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Categories</p>
                <div className="grid grid-cols-2 gap-2">
                  {parentCategories.map((category) => (
                    <button
                      className="flex items-center gap-2 rounded-xl border border-slate-100 px-3 py-2.5 text-left text-xs font-semibold text-slate-700 transition hover:border-primary hover:text-primary dark:border-slate-800 dark:text-slate-200"
                      key={category.slug}
                      onClick={() => {
                        navigate(`/shop?category=${category.slug}`)
                        setMobileOpen(false)
                      }}
                      type="button"
                    >
                      {category.image ? (
                        <img alt="" className="h-5 w-5 rounded object-cover" src={category.image} />
                      ) : (
                        <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-primary/10 text-[9px] font-black text-primary">
                          {category.name.slice(0, 1)}
                        </span>
                      )}
                      <span className="truncate">{category.name}</span>
                    </button>
                  ))}
                </div>

                <div className="mt-6 flex gap-3">
                  {authUser ? (
                    <button
                      className="flex-1 rounded-full bg-slate-900 px-4 py-3 text-sm font-bold text-white dark:bg-white dark:text-slate-900"
                      onClick={() => {
                        dispatch(logout())
                        setMobileOpen(false)
                      }}
                      type="button"
                    >
                      Logout
                    </button>
                  ) : (
                    <NavLink
                      className="flex-1 rounded-full bg-primary px-4 py-3 text-center text-sm font-bold text-white"
                      onClick={() => setMobileOpen(false)}
                      to="/auth/login"
                    >
                      Sign In
                    </NavLink>
                  )}
                </div>
              </div>
            </motion.aside>
          </>
        )}
      </AnimatePresence>
    </>
  )
}

export default SiteNavbar
