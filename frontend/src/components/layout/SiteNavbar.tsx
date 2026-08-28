import { AnimatePresence, motion } from 'framer-motion'
import { ChevronDown, ChevronRight, Heart, LayoutDashboard, LogIn, LogOut, Menu, Package, ShoppingBag, User, X, Zap, MapPin, Settings } from 'lucide-react'
import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import { useAppDispatch, useAppSelector } from '../../store/hooks'
import { logout } from '../../store/slices/authSlice'
import { openCart } from '../../store/slices/uiSlice'
import { useGetCategoriesQuery, useGetCurrentUserQuery, useGetHomepageConfigQuery } from '../../store/services/commerceApi'
import SearchBar from '../common/SearchBar'
import { formatPrice } from '../../lib/format'

const SiteNavbar = () => {
  const dispatch = useAppDispatch()
  const navigate = useNavigate()
  const cartItems = useAppSelector((state) => state.cart.items)
  const cartCount = useMemo(() => cartItems.reduce((sum, item) => sum + item.quantity, 0), [cartItems])
  const subtotal = useMemo(() => cartItems.reduce((sum, item) => sum + Number(item.product.price) * item.quantity, 0), [cartItems])
  const wishlistCount = useAppSelector((state) => state.ui.wishlist.length)
  const token = useAppSelector((state) => state.auth.token)
  const authUser = useAppSelector((state) => state.auth.user)

  const [mobileOpen, setMobileOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const [showLogoutModal, setShowLogoutModal] = useState(false)
  const userMenuRef = useRef<HTMLDivElement>(null)

  const categoriesQuery = useGetCategoriesQuery()
  const categories = categoriesQuery.data || []
  const { data: homepageConfig } = useGetHomepageConfigQuery()
  const announcement = homepageConfig?.announcement

  useGetCurrentUserQuery(undefined, { skip: !token })

  const parentCategories = categories.filter((c) => !c.parentId)

  const isAdmin = useMemo(
    () => authUser?.role === 'admin' || authUser?.role === 'manager' || authUser?.role === 'super_admin',
    [authUser],
  )

  const getUserInitials = useCallback((name: string) => {
    return name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2)
  }, [])

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  useEffect(() => {
    document.body.style.overflow = mobileOpen ? 'hidden' : ''
    return () => { document.body.style.overflow = '' }
  }, [mobileOpen])

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (userMenuRef.current && !userMenuRef.current.contains(event.target as Node)) {
        setUserMenuOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  useEffect(() => {
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setUserMenuOpen(false)
        setShowLogoutModal(false)
      }
    }
    document.addEventListener('keydown', handleEscape)
    return () => document.removeEventListener('keydown', handleEscape)
  }, [])

  const handleLogout = () => {
    dispatch(logout())
    setShowLogoutModal(false)
    setUserMenuOpen(false)
    navigate('/', { replace: true })
  }

  const iconButton =
    'relative flex h-11 w-11 items-center justify-center rounded-full border border-brand-green-100 bg-brand-green-50 text-brand-green-600 transition hover:border-brand-green-400 hover:bg-brand-green-100 hover:text-brand-green-700'

  const cartButtonClass =
    'relative flex h-11 items-center gap-2 rounded-full border border-brand-orange-200 bg-brand-orange-50 px-3.5 text-brand-orange-600 transition hover:border-brand-orange-400 hover:bg-brand-orange-100 hover:text-brand-orange-700'

  return (
    <>
      <header
        className={`sticky top-0 z-50 border-b transition-all duration-300 ${
          scrolled
            ? 'border-brand-green-100 bg-white/95 shadow-soft backdrop-blur-xl'
            : 'border-brand-green-100 bg-white'
        }`}
      >
        {announcement?.enabled && announcement.text && (
          <div
            style={{ backgroundColor: announcement.backgroundColor || '#0F4D2C', color: announcement.textColor || '#ffffff' }}
          >
            <div className="mx-auto flex max-w-7xl items-center justify-center gap-2 px-4 py-1.5 text-center text-[11px] font-medium sm:text-xs">
              <Zap size={12} className="fill-brand-orange-400 text-brand-orange-400" />
              <span className="truncate">{announcement.text}</span>
            </div>
          </div>
        )}

        <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
          <div className="flex items-center gap-3">
            <button
              aria-label="Toggle menu"
              className="flex h-11 w-11 items-center justify-center rounded-full border border-brand-green-100 bg-brand-green-50 text-brand-green-700 transition hover:bg-brand-green-100 lg:hidden"
              onClick={() => setMobileOpen((prev) => !prev)}
              type="button"
            >
              {mobileOpen ? <X size={18} /> : <Menu size={18} />}
            </button>

            <NavLink aria-label="Mama Bazar home" className="flex items-center gap-2" to="/">
              <img
                alt="Mama Bazar logo"
                className="h-10 w-10 object-contain"
                width="40"
                height="40"
                decoding="async"
                src="/brandlogo.png"
              />
              <span className="hidden font-headline text-lg font-light tracking-tight text-slate-900 sm:block">
               <span className="text-brand-green-500 font-bold gap-2">Mama</span>
               <span className="text-brand-orange-500 font-bold">{" "}Bazar</span>
              </span>
            </NavLink>
          </div>

          <div className="hidden lg:block w-full max-w-xs mx-4">
            <SearchBar />
          </div>

          <div className="flex items-center gap-2">
            <Link
              to="/track"
              className="hidden xl:inline-flex items-center text-xs font-bold uppercase tracking-wider text-brand-green-500 hover:text-brand-green-700 mr-2 transition"
            >
              Track Order
            </Link>

            <NavLink aria-label="Wishlist" className={`${iconButton} hidden sm:flex`} to="/shop">
              <Heart size={17} />
              {wishlistCount > 0 && (
                <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-orange-500 px-1 text-[10px] font-bold text-white">
                  {wishlistCount}
                </span>
              )}
            </NavLink>

            <button aria-label="Open cart" className={cartButtonClass} onClick={() => dispatch(openCart())} type="button">
              <ShoppingBag size={17} />
              <span className="hidden md:inline text-xs font-bold tracking-tight">{formatPrice(subtotal)}</span>
              {cartCount > 0 && (
                <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-orange-500 px-1 text-[10px] font-bold text-white">
                  {cartCount}
                </span>
              )}
            </button>

            {authUser ? (
              <div className="relative" ref={userMenuRef}>
                <button
                  aria-label="Account menu"
                  aria-expanded={userMenuOpen}
                  className="flex items-center gap-2 rounded-full border border-brand-green-200 bg-brand-green-50 px-3 py-2 transition hover:border-brand-green-400 hover:bg-brand-green-100"
                  onClick={() => setUserMenuOpen(!userMenuOpen)}
                  type="button"
                >
                  <div className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-green-500 text-xs font-bold text-white">
                    {getUserInitials(authUser.name)}
                  </div>
                  <span className="hidden md:block text-sm font-semibold text-brand-green-800 max-w-[120px] truncate">
                    {authUser.name.split(' ')[0]}
                  </span>
                  <ChevronDown size={14} className={`text-brand-green-600 transition-transform ${userMenuOpen ? 'rotate-180' : ''}`} />
                </button>

                <AnimatePresence>
                  {userMenuOpen && (
                    <motion.div
                      initial={{ opacity: 0, y: 10, scale: 0.95 }}
                      animate={{ opacity: 1, y: 0, scale: 1 }}
                      exit={{ opacity: 0, y: 10, scale: 0.95 }}
                      transition={{ duration: 0.15, ease: 'easeOut' }}
                      className="absolute right-0 top-full z-50 mt-2 w-64 overflow-hidden rounded-xl border border-brand-green-100 bg-white shadow-card"
                    >
                      <div className="border-b border-brand-green-100 bg-brand-green-50 px-4 py-3">
                        <div className="flex items-center gap-3">
                          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-brand-green-500 text-sm font-bold text-white">
                            {getUserInitials(authUser.name)}
                          </div>
                          <div className="min-w-0">
                            <p className="text-sm font-semibold text-brand-green-900 truncate">{authUser.name}</p>
                            <p className="text-xs text-brand-green-600">{authUser.phone}</p>
                          </div>
                        </div>
                      </div>

                      <div className="py-2">
                        {isAdmin && (
                          <>
                            <Link
                              className="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-800 transition hover:bg-brand-green-50 hover:text-brand-green-700"
                              onClick={() => setUserMenuOpen(false)}
                              to="/admin/dashboard"
                            >
                              <LayoutDashboard size={16} className="text-brand-green-500" />
                              Admin Dashboard
                            </Link>
                            <div className="my-1 border-t border-slate-100" />
                          </>
                        )}
                        <Link
                          className="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-800 transition hover:bg-brand-green-50 hover:text-brand-green-700"
                          onClick={() => setUserMenuOpen(false)}
                          to="/dashboard"
                        >
                          <Settings size={16} className="text-brand-green-500" />
                          My Account
                        </Link>
                        <Link
                          className="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-800 transition hover:bg-brand-green-50 hover:text-brand-green-700"
                          onClick={() => setUserMenuOpen(false)}
                          to="/dashboard/orders"
                        >
                          <Package size={16} className="text-brand-green-500" />
                          My Orders
                        </Link>
                        <Link
                          className="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-800 transition hover:bg-brand-green-50 hover:text-brand-green-700"
                          onClick={() => setUserMenuOpen(false)}
                          to="/dashboard/addresses"
                        >
                          <MapPin size={16} className="text-brand-green-500" />
                          My Addresses
                        </Link>
                        <Link
                          className="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-800 transition hover:bg-brand-green-50 hover:text-brand-green-700"
                          onClick={() => setUserMenuOpen(false)}
                          to="/dashboard/profile"
                        >
                          <User size={16} className="text-brand-green-500" />
                          Profile Settings
                        </Link>
                      </div>

                      <div className="border-t border-slate-100" />

                      <div className="py-2">
                        <button
                          className="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-red-600 transition hover:bg-red-50"
                          onClick={() => {
                            setUserMenuOpen(false)
                            setShowLogoutModal(true)
                          }}
                          type="button"
                        >
                          <LogOut size={16} />
                          Logout
                        </button>
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>
            ) : (
              <NavLink
                aria-label="Login / Register"
                className={`${iconButton} hidden sm:flex`}
                to="/auth/login"
              >
                <LogIn size={17} />
              </NavLink>
            )}
          </div>
        </div>

        <div className="px-4 pb-3 lg:hidden">
          <SearchBar onNavigate={() => setMobileOpen(false)} />
        </div>

        <div className="hidden lg:block border-t border-brand-green-100 bg-brand-green-600">
          <div className="mx-auto flex h-11 w-full max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8 text-sm">
            <div className="relative group py-2">
              <button className="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white hover:text-brand-orange-300 transition">
                <Menu size={15} />
                All Categories
                <ChevronDown size={13} />
              </button>
              <div className="absolute left-0 top-full z-50 hidden group-hover:block group-focus-within:block w-64 rounded-2xl border border-brand-green-100 bg-white p-2.5 shadow-card">
                <div className="max-h-[350px] overflow-y-auto pr-1">
                  {parentCategories.map((cat) => (
                    <Link
                      key={cat.id}
                      to={`/shop?category=${cat.slug}`}
                      className="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-brand-green-50 hover:text-brand-green-700 transition"
                    >
                      {cat.image ? (
                        <img alt="" className="h-5 w-5 rounded object-cover shrink-0" src={cat.image} />
                      ) : (
                        <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-brand-green-100 text-[9px] font-black text-brand-green-700">
                          {cat.name.slice(0, 1)}
                        </span>
                      )}
                      <span className="truncate">{cat.name}</span>
                    </Link>
                  ))}
                </div>
              </div>
            </div>

            <span className="h-4 w-px bg-white/30" />

            <nav className="flex flex-1 items-center gap-5" aria-label="Main Navigation">
              <NavLink to="/" className={({ isActive }) => `text-xs font-bold uppercase tracking-wider transition ${isActive ? 'text-white underline underline-offset-4' : 'text-white/80 hover:text-white'}`}>
                Home
              </NavLink>
              <NavLink to="/shop" className={({ isActive }) => `text-xs font-bold uppercase tracking-wider transition ${isActive ? 'text-white underline underline-offset-4' : 'text-white/80 hover:text-white'}`}>
                Shop
              </NavLink>
              <NavLink to="/shop?sale=true" className="text-xs font-bold uppercase tracking-wider text-brand-orange-300 hover:text-brand-orange-200 transition">
                🔥 Deals
              </NavLink>
              <NavLink to="/shop" className="text-xs font-bold uppercase tracking-wider text-white/80 hover:text-white transition">
                Brands
              </NavLink>
              <NavLink to="/contact" className="text-xs font-bold uppercase tracking-wider text-white/80 hover:text-white transition">
                About
              </NavLink>
              <NavLink to="/contact" className="text-xs font-bold uppercase tracking-wider text-white/80 hover:text-white transition">
                Contact
              </NavLink>
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
              className="fixed inset-0 z-[200] bg-black/70 backdrop-blur-sm lg:hidden"
              exit={{ opacity: 0 }}
              initial={{ opacity: 0 }}
              onClick={() => setMobileOpen(false)}
            />
            <motion.aside
              animate={{ x: 0 }}
              className="fixed inset-y-0 left-0 z-[200] w-[82%] max-w-sm overflow-y-auto bg-white shadow-card lg:hidden"
              exit={{ x: '-100%' }}
              initial={{ x: '-100%' }}
              transition={{ duration: 0.3, ease: [0.22, 1, 0.36, 1] }}
            >
              {/* Drawer header */}
              <div className="flex items-center justify-between border-b border-brand-green-100 bg-brand-green-600 p-5">
                <NavLink className="flex items-center gap-2" onClick={() => setMobileOpen(false)} to="/">
                  <img
                    alt="Mama Bazar logo"
                    className="h-8 w-8 object-contain"
                    width="32"
                    height="32"
                    decoding="async"
                    src="/brandlogo.png"
                  />
                  <span className="font-headline text-lg font-light text-white">
                    Mama<span className="text-brand-orange-300">Bazar</span>
                  </span>
                </NavLink>
                <button
                  aria-label="Close menu"
                  className="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 transition"
                  onClick={() => setMobileOpen(false)}
                  type="button"
                >
                  <X size={16} />
                </button>
              </div>

              {authUser && (
                <div className="border-b border-brand-green-100 bg-brand-green-50 p-5">
                  <div className="flex items-center gap-3">
                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-brand-green-500 text-sm font-bold text-white">
                      {getUserInitials(authUser.name)}
                    </div>
                    <div>
                      <p className="font-semibold text-brand-green-900">{authUser.name}</p>
                      <p className="text-sm text-brand-green-600">{authUser.phone}</p>
                    </div>
                  </div>
                </div>
              )}

              <nav className="p-5 border-b border-brand-green-100" aria-label="Mobile Navigation">
                {[
                  { label: 'Home', to: '/' },
                  { label: 'Shop', to: '/shop' },
                  { label: '🔥 Deals / Offers', to: '/shop?sale=true' },
                  { label: 'Track Order', to: '/track' },
                  { label: 'Wishlist', to: '/shop' },
                ].map((link) => (
                  <NavLink
                    className={({ isActive }) =>
                      `mb-1 flex items-center justify-between rounded-full px-4 py-2.5 text-[14px] font-semibold transition ${
                        isActive
                          ? 'bg-brand-green-50 text-brand-green-700'
                          : 'text-slate-800 hover:bg-brand-green-50 hover:text-brand-green-700'
                      }`
                    }
                    key={link.label}
                    onClick={() => setMobileOpen(false)}
                    to={link.to}
                  >
                    {link.label}
                    <ChevronRight size={16} className="text-brand-green-400" />
                  </NavLink>
                ))}
              </nav>

              <div className="p-5">
                <p className="mb-3 text-[10px] font-bold uppercase tracking-[0.2em] text-brand-green-600">Categories</p>
                <div className="grid grid-cols-2 gap-2">
                  {parentCategories.map((category) => (
                    <button
                      className="flex items-center gap-2 rounded-full border border-brand-green-100 px-3 py-2.5 text-left text-xs font-semibold text-slate-800 transition hover:border-brand-green-300 hover:bg-brand-green-50 hover:text-brand-green-700"
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
                        <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-brand-green-100 text-[9px] font-black text-brand-green-700">
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
                      className="flex-1 rounded-full border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-600 transition hover:bg-red-100"
                      onClick={() => {
                        dispatch(logout())
                        setMobileOpen(false)
                        navigate('/', { replace: true })
                      }}
                      type="button"
                    >
                      Logout
                    </button>
                  ) : (
                    <NavLink
                      className="flex-1 rounded-full bg-brand-green-600 px-4 py-3 text-center text-sm font-bold text-white transition hover:bg-brand-green-700"
                      onClick={() => setMobileOpen(false)}
                      to="/auth/login"
                    >
                      Login / Register
                    </NavLink>
                  )}
                </div>
              </div>
            </motion.aside>
          </>
        )}
      </AnimatePresence>

      {/* Logout Confirmation Modal */}
      <AnimatePresence>
        {showLogoutModal && (
          <>
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 z-[200] bg-black/70 backdrop-blur-sm"
              onClick={() => setShowLogoutModal(false)}
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              transition={{ duration: 0.2 }}
              className="fixed inset-0 z-[201] flex items-center justify-center p-4"
            >
              <div className="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
                <div className="text-center">
                  <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <LogOut size={24} className="text-red-600" />
                  </div>
                  <h3 className="mt-4 text-lg font-bold text-slate-900">Logout from Mama Bazar?</h3>
                  <p className="mt-2 text-sm text-slate-500">
                    Are you sure you want to logout? You will need to login again to access your account.
                  </p>
                </div>
                <div className="mt-6 flex gap-3">
                  <button
                    className="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    onClick={() => setShowLogoutModal(false)}
                    type="button"
                  >
                    Cancel
                  </button>
                  <button
                    className="flex-1 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                    onClick={handleLogout}
                    type="button"
                  >
                    Logout
                  </button>
                </div>
              </div>
            </motion.div>
          </>
        )}
      </AnimatePresence>
    </>
  )
}

export default SiteNavbar
