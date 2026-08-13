import { AnimatePresence, motion } from 'framer-motion'
import { ArrowRight, Minus, Plus, ShoppingBag, Trash2, X } from 'lucide-react'
import { useEffect } from 'react'
import { Link } from 'react-router-dom'
import { formatPrice } from '../../lib/format'
import { useAppDispatch, useAppSelector } from '../../store/hooks'
import { removeFromCart, updateQuantity } from '../../store/slices/cartSlice'
import { useGetProductsQuery } from '../../store/services/commerceApi'
import { closeCart } from '../../store/slices/uiSlice'

const CartDrawer = () => {
  const dispatch = useAppDispatch()
  const items = useAppSelector((state) => state.cart.items)
  const open = useAppSelector((state) => state.ui.cartOpen)

  const recommendationsQuery = useGetProductsQuery({ label: 'featured', limit: 3 })
  const recommendations = (recommendationsQuery.data?.data || []).filter(
    (product) => !items.some((item) => item.product.id === product.id),
  )

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') dispatch(closeCart())
    }
    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [dispatch])

  const subtotal = items.reduce((sum, item) => {
    const price = typeof item.product.price === 'string' ? Number(item.product.price) : item.product.price
    return sum + price * item.quantity
  }, 0)

  return (
    <AnimatePresence>
      {open && (
        <>
          <motion.div
            animate={{ opacity: 1 }}
            className="fixed inset-0 z-[200] bg-black/50 backdrop-blur-sm"
            exit={{ opacity: 0 }}
            initial={{ opacity: 0 }}
            onClick={() => dispatch(closeCart())}
          />
          <motion.aside
            animate={{ x: 0 }}
            className="fixed inset-y-0 right-0 z-[200] flex w-full max-w-md flex-col bg-white shadow-lift"
            exit={{ x: '100%' }}
            initial={{ x: '100%' }}
            transition={{ duration: 0.32, ease: [0.22, 1, 0.36, 1] }}
            role="dialog"
            aria-label="Shopping cart"
          >
            <header className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
              <h2 className="flex items-center gap-2 font-headline text-lg font-extrabold text-slate-900">
                <ShoppingBag size={20} className="text-emerald-600" /> Your Cart
                <span className="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-bold text-slate-900">{items.length}</span>
              </h2>
              <button
                aria-label="Close cart"
                className="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-accent hover:text-accent-foreground"
                onClick={() => dispatch(closeCart())}
                type="button"
              >
                <X size={16} />
              </button>
            </header>

            {items.length === 0 ? (
              <div className="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center">
                <span className="flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                  <ShoppingBag size={32} />
                </span>
                <p className="text-lg font-bold text-slate-900">Your cart is empty</p>
                <p className="text-sm text-slate-500">Discover premium gadgets and appliances waiting for you.</p>
                <Link
                  className="mt-2 inline-flex items-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground transition hover:bg-primary-700"
                  onClick={() => dispatch(closeCart())}
                  to="/shop"
                >
                  Start Shopping <ArrowRight size={16} />
                </Link>
              </div>
            ) : (
              <>
                <div className="flex-1 overflow-y-auto px-5 py-4">
                  <div className="space-y-4">
                    {items.map((item) => {
                      const price = typeof item.product.price === 'string' ? Number(item.product.price) : item.product.price
                      return (
                        <div className="flex gap-3 rounded-[18px] bg-slate-50 p-3" key={item.key}>
                          <Link className="shrink-0" to={`/products/${item.product.slug}`} onClick={() => dispatch(closeCart())}>
                            <img alt={item.product.title} className="h-20 w-20 rounded-xl object-cover" src={item.image || item.product.images[0]} />
                          </Link>
                          <div className="flex min-w-0 flex-1 flex-col">
                            <div className="flex items-start justify-between gap-2">
                              <Link
                                className="line-clamp-2 text-sm font-bold text-slate-900 hover:text-emerald-600"
                                to={`/products/${item.product.slug}`}
                                onClick={() => dispatch(closeCart())}
                              >
                                {item.product.title}
                              </Link>
                              <button
                                aria-label="Remove item"
                                className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-accent hover:text-accent-foreground"
                                onClick={() => dispatch(removeFromCart(item.key))}
                                type="button"
                              >
                                <Trash2 size={14} />
                              </button>
                            </div>
                            {item.color && <p className="mt-0.5 text-xs text-slate-500">Color: {item.color}</p>}
                            {item.size && <p className="mt-0.5 text-xs text-slate-500">Size: {item.size}</p>}
                            <div className="mt-auto flex items-center justify-between pt-2">
                              <div className="flex items-center gap-1 rounded-full border border-slate-200">
                                <button
                                  aria-label="Decrease quantity"
                                  className="flex h-11 w-11 items-center justify-center rounded-full text-slate-600 transition hover:text-emerald-600"
                                  onClick={() => dispatch(updateQuantity({ key: item.key, quantity: item.quantity - 1 }))}
                                  type="button"
                                >
                                  <Minus size={13} />
                                </button>
                                <span className="w-6 text-center text-sm font-bold">{item.quantity}</span>
                                <button
                                  aria-label="Increase quantity"
                                  className="flex h-11 w-11 items-center justify-center rounded-full text-slate-600 transition hover:text-emerald-600"
                                  onClick={() => dispatch(updateQuantity({ key: item.key, quantity: item.quantity + 1 }))}
                                  type="button"
                                >
                                  <Plus size={13} />
                                </button>
                              </div>
                              <p className="text-sm font-extrabold text-slate-900">{formatPrice(price * item.quantity)}</p>
                            </div>
                          </div>
                        </div>
                      )
                    })}
                  </div>

                  {recommendations.length > 0 && (
                    <div className="mt-6">
                      <p className="mb-2 text-xs font-bold uppercase tracking-[0.15em] text-slate-500">You may also like</p>
                      <div className="grid grid-cols-3 gap-3">
                        {recommendations.map((product) => (
                          <Link
                            className="group flex flex-col overflow-hidden rounded-xl bg-slate-50 transition hover:-translate-y-0.5 hover:shadow-soft"
                            key={product.id}
                            onClick={() => dispatch(closeCart())}
                            to={`/products/${product.slug}`}
                          >
                            <img alt={product.title} className="aspect-square w-full object-cover" loading="lazy" src={product.images[0]} />
                            <p className="line-clamp-2 p-2 text-[11px] font-semibold leading-snug text-slate-700 group-hover:text-emerald-600">
                              {product.title}
                            </p>
                          </Link>
                        ))}
                      </div>
                    </div>
                  )}
                </div>

                <footer className="border-t border-slate-100 p-5">
                  <div className="space-y-1.5 text-sm">
                    <div className="flex justify-between text-slate-500">
                      <span>Subtotal</span>
                      <span className="font-semibold text-slate-800">{formatPrice(subtotal)}</span>
                    </div>
                    <div className="flex justify-between border-t border-dashed border-slate-200 pt-2 text-base font-extrabold text-slate-900">
                      <span>Total</span>
                      <span>{formatPrice(subtotal)}</span>
                    </div>
                    <p className="pt-1 text-xs text-slate-400">Delivery cost is calculated at checkout.</p>
                  </div>

                  <Link
                    className="mt-4 flex w-full items-center justify-center gap-2 rounded-full bg-accent px-5 py-3.5 text-sm font-bold text-accent-foreground transition hover:bg-accent-600 active:scale-95"
                    onClick={() => dispatch(closeCart())}
                    to="/checkout"
                  >
                    Proceed to Checkout <ArrowRight size={16} />
                  </Link>
                </footer>
              </>
            )}
          </motion.aside>
        </>
      )}
    </AnimatePresence>
  )
}

export default CartDrawer
