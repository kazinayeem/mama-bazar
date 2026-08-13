import { Heart, Home, LayoutGrid, ShoppingBag, User } from 'lucide-react'
import { NavLink } from 'react-router-dom'
import { useAppDispatch, useAppSelector } from '../../store/hooks'
import { openCart } from '../../store/slices/uiSlice'

const MobileBottomNav = () => {
  const dispatch = useAppDispatch()
  const cartCount = useAppSelector((state) => state.cart.items.reduce((sum, item) => sum + item.quantity, 0))
  const wishlistCount = useAppSelector((state) => state.ui.wishlist.length)

  const itemClass = ({ isActive }: { isActive: boolean }) =>
    `relative flex flex-1 flex-col items-center gap-1 py-1.5 text-[10px] font-semibold transition ${
      isActive ? 'text-brand-green-600' : 'text-slate-400 hover:text-brand-green-500'
    }`

  return (
    <nav
      aria-label="Mobile navigation"
      className="fixed inset-x-0 bottom-0 z-40 border-t border-brand-green-100 bg-white pb-[env(safe-area-inset-bottom)] md:hidden"
    >
      <div className="mx-auto flex max-w-lg items-center px-2">
        <NavLink className={itemClass} to="/">
          <Home size={20} /> Home
        </NavLink>
        <NavLink className={itemClass} to="/shop">
          <LayoutGrid size={20} /> Categories
        </NavLink>
        <NavLink className={itemClass} to="/shop">
          <Heart size={20} /> Wishlist
          {wishlistCount > 0 && (
            <span className="absolute right-3 top-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-green-500 px-1 text-[9px] font-bold text-white">
              {wishlistCount}
            </span>
          )}
        </NavLink>
        <button
          aria-label="Open cart"
          className="relative flex flex-1 flex-col items-center gap-1 py-1.5 text-[10px] font-semibold text-slate-400 transition hover:text-brand-orange-500"
          onClick={() => dispatch(openCart())}
          type="button"
        >
          <ShoppingBag size={20} /> Cart
          {cartCount > 0 && (
            <span className="absolute right-3 top-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-orange-500 px-1 text-[9px] font-bold text-white">
              {cartCount}
            </span>
          )}
        </button>
        <NavLink className={itemClass} to="/auth/login">
          <User size={20} /> Account
        </NavLink>
      </div>
    </nav>
  )
}

export default MobileBottomNav
