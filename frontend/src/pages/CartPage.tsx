import { ArrowRight, Minus, Plus, ShoppingBag, Trash2, Truck } from 'lucide-react'
import { Link } from 'react-router-dom'
import { SEO } from '../components/common/SEO'
import { formatPrice } from '../lib/format'
import { useAppDispatch, useAppSelector } from '../store/hooks'
import { clearCart, removeFromCart, updateQuantity } from '../store/slices/cartSlice'

const CartPage = () => {
  const dispatch = useAppDispatch()
  const items = useAppSelector((state) => state.cart.items)

  const subtotal = items.reduce((sum, item) => {
    const price = typeof item.product.price === 'string' ? Number(item.product.price) : item.product.price
    return sum + price * item.quantity
  }, 0)

  if (items.length === 0) {
    return (
      <main className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <SEO title="Shopping Cart" description="Your shopping cart at Mama Bazar." url="/cart" />
        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
          <span className="flex h-24 w-24 items-center justify-center rounded-full bg-brand-green-50 text-brand-green-300">
            <ShoppingBag size={40} />
          </span>
          <h1 className="font-headline text-2xl font-extrabold text-slate-900 bangla-text">আপনার কার্ট খালি / Your cart is empty</h1>
          <p className="max-w-sm text-sm text-slate-500">
            আপনার পছন্দের প্রোডাক্টগুলো কার্টে যোগ করুন। / Add your favourite products to the cart and check out quickly.
          </p>
          <Link
            className="mt-2 inline-flex items-center gap-2 rounded-xl bg-brand-orange-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-orange-600 active:scale-95"
            to="/shop"
          >
            কেনাকাটা শুরু করুন / Start Shopping <ArrowRight size={16} />
          </Link>
        </div>
      </main>
    )
  }

  return (
    <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <SEO title="Shopping Cart" description="Review and manage the items in your cart at Mama Bazar." url="/cart" />

      <div className="mb-8 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="font-headline text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Shopping Cart</h1>
          <p className="mt-1 text-sm text-slate-500 bangla-text">
            {items.reduce((sum, item) => sum + item.quantity, 0)} টি আইটেম আপনার কার্টে / {items.reduce((sum, item) => sum + item.quantity, 0)} items in your cart
          </p>
        </div>
        <button
          className="self-start rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:border-destructive/40 hover:text-destructive sm:self-auto"
          onClick={() => dispatch(clearCart())}
          type="button"
        >
          কার্ট খালি করুন / Clear Cart
        </button>
      </div>

      <div className="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
        {/* ── Product list ── */}
        <div className="space-y-4 lg:col-span-8">
          {items.map((item) => {
            const price = typeof item.product.price === 'string' ? Number(item.product.price) : item.product.price
            return (
              <div className="flex gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-soft transition sm:gap-5 sm:p-5" key={item.key}>
                <Link className="shrink-0" to={`/products/${item.product.slug}`}>
                  <img
                    alt={item.product.title}
                    className="h-24 w-24 rounded-xl border border-slate-100 object-cover sm:h-28 sm:w-28"
                    src={item.image || item.product.images[0]}
                  />
                </Link>

                <div className="flex min-w-0 flex-1 flex-col">
                  <div className="flex items-start justify-between gap-3">
                    <Link
                      className="line-clamp-2 text-sm font-bold leading-snug text-slate-900 transition hover:text-brand-green-600 sm:text-[15px]"
                      to={`/products/${item.product.slug}`}
                    >
                      {item.product.title}
                    </Link>
                    <button
                      aria-label="Remove item"
                      className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-destructive/10 hover:text-destructive"
                      onClick={() => dispatch(removeFromCart(item.key))}
                      type="button"
                    >
                      <Trash2 size={15} />
                    </button>
                  </div>

                  {(item.color || item.size) && (
                    <p className="mt-1 text-xs text-slate-500">
                      {item.color ? `Color: ${item.color}` : ''}
                      {item.size ? `${item.color ? ' · ' : ''}Size: ${item.size}` : ''}
                    </p>
                  )}

                  <div className="mt-auto flex items-center justify-between pt-3">
                    <div className="flex items-center rounded-lg border border-slate-200">
                      <button
                        aria-label="Decrease quantity"
                        className="flex h-9 w-9 items-center justify-center rounded-l-lg text-slate-600 transition hover:text-brand-green-600"
                        onClick={() => dispatch(updateQuantity({ key: item.key, quantity: item.quantity - 1 }))}
                        type="button"
                      >
                        <Minus size={14} />
                      </button>
                      <span className="w-8 text-center text-sm font-bold text-slate-900">{item.quantity}</span>
                      <button
                        aria-label="Increase quantity"
                        className="flex h-9 w-9 items-center justify-center rounded-r-lg text-slate-600 transition hover:text-brand-green-600"
                        onClick={() => dispatch(updateQuantity({ key: item.key, quantity: item.quantity + 1 }))}
                        type="button"
                      >
                        <Plus size={14} />
                      </button>
                    </div>
                    <p className="text-base font-extrabold text-slate-900 sm:text-lg">{formatPrice(price * item.quantity)}</p>
                  </div>
                </div>
              </div>
            )
          })}
        </div>

        {/* ── Order summary ── */}
        <aside className="lg:col-span-4">
          <div className="rounded-2xl border border-slate-200 bg-white shadow-soft">
            <h2 className="border-b border-slate-100 px-6 py-5 font-headline text-lg font-bold text-slate-900 bangla-text">অর্ডার সারাংশ / Order Summary</h2>
            <div className="space-y-3 px-6 py-5 text-sm">
              <div className="flex justify-between">
                <span className="text-slate-500">সাবটোটাল / Subtotal</span>
                <span className="font-semibold text-slate-900">{formatPrice(subtotal)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">ডেলিভারি চার্জ / Shipping</span>
                <span className="font-medium text-slate-400">চেকআউটে হিসাব হবে / At checkout</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">ডিসকাউন্ট / Discount</span>
                <span className="font-medium text-slate-400">কুপনে প্রযোজ্য / Via coupon</span>
              </div>
              <div className="flex items-end justify-between border-t border-slate-100 pt-4">
                <span className="font-headline text-base font-bold text-slate-900">মোট / Total</span>
                <span className="font-headline text-xl font-extrabold text-brand-green-600">{formatPrice(subtotal)}</span>
              </div>

              <Link
                className="mt-3 flex w-full items-center justify-center gap-2 rounded-xl bg-brand-orange-500 px-5 py-3.5 text-sm font-bold text-white transition hover:bg-brand-orange-600 active:scale-95"
                to="/checkout"
              >
                চেকআউটে যান / Proceed to Checkout <ArrowRight size={16} />
              </Link>

              <div className="flex items-center gap-2 pt-1 text-xs text-slate-400">
                <Truck size={14} className="shrink-0" />
                <span className="bangla-text">ডেলিভারি চার্জ ও ডিসকাউন্ট চেকআউটে নির্ধারিত হয়। / Shipping and discounts are calculated at checkout.</span>
              </div>
            </div>
          </div>

          <div className="mt-4 rounded-2xl border border-slate-200 bg-white px-6 py-4">
            <Link className="text-sm font-semibold text-brand-green-600 transition hover:text-brand-green-700" to="/shop">
              ← কেনাকাটা চালিয়ে যান / Continue Shopping
            </Link>
          </div>
        </aside>
      </div>
    </main>
  )
}

export default CartPage