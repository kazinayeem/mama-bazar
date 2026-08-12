import { motion } from 'framer-motion'
import { Eye, Heart, ImageOff, Plus, ShoppingBag } from 'lucide-react'
import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useToast } from './ToastProvider'
import { formatPrice } from '../../lib/format'
import { useAppDispatch, useAppSelector } from '../../store/hooks'
import { addToCart } from '../../store/slices/cartSlice'
import { toggleWishlist } from '../../store/slices/uiSlice'
import { resolveUrl } from '@/lib/apiConfig'
import {
  findVariantByOptions,
  getVariantDiscountPercent,
  getVariantEffectivePrice,
  type Product,
} from '../../types'
import StarRating from './StarRating'

interface ProductCardProps {
  product: Product
  onQuickView: (product: Product) => void
  index?: number
}

const ProductCard = ({ product, onQuickView, index = 0 }: ProductCardProps) => {
  const dispatch = useAppDispatch()
  const toast = useToast()
  const wishlisted = useAppSelector((state) => state.ui.wishlist.includes(product.id))
  const [activeColor, setActiveColor] = useState<string | undefined>(product.colorOptions?.[0]?.name)
  const [activeSize, setActiveSize] = useState<string | undefined>(product.sizeOptions?.[0])
  const [imageFailed, setImageFailed] = useState(false)

  const hasColorOptions = Boolean(product.colorOptions?.length)
  const hasSizeOptions = Boolean(product.sizeOptions?.length)

  const activeVariant = useMemo(
    () => (product.variants ? findVariantByOptions(product.variants, activeColor, activeSize) : undefined),
    [product.variants, activeColor, activeSize],
  )

  const price = Number(product.price)
  const baseDiscount = Number(product.discount || 0)
  const variantDiscount = activeVariant ? getVariantDiscountPercent(activeVariant) : 0
  const salePriceValue = activeVariant
    ? getVariantEffectivePrice(activeVariant, product.price, product.discount)
    : Number(product.salePrice) > 0
      ? Math.round(Number(product.salePrice))
      : baseDiscount > 0
        ? Math.round(price - (price * baseDiscount) / 100)
        : price

  const brandName = product.brandInfo?.name || product.brand || ''
  const activeColorOption = product.colorOptions?.find((c) => c.name === activeColor)
  const activeSizeLabel = activeSize || ''
  const activeImage =
    (activeVariant?.images?.[0] ? resolveUrl(activeVariant.images[0]) : null) ||
    (activeVariant?.thumbnail ? resolveUrl(activeVariant.thumbnail) : null) ||
    (activeColorOption?.image ? resolveUrl(activeColorOption.image) : null) ||
    (product.images.length > 0 ? resolveUrl(product.images[0]) : '')

  const isOutOfStock = product.stock <= 0
  const isLowStock = product.stock > 0 && product.stock <= 10

  const handleAddToCart = () => {
    if (isOutOfStock) return
    dispatch(
      addToCart({
        product: {
          id: product.id,
          title: product.title,
          slug: product.slug,
          brand: brandName,
          price: salePriceValue,
          images: product.images,
          stock: product.stock,
        },
          variantId: activeVariant?.id,
          size: activeSizeLabel || undefined,
        color: activeColor,
        image: activeImage,
      }),
    )
    toast.success('Added to cart')
  }

  const handleWishlist = (e: React.MouseEvent) => {
    e.preventDefault()
    e.stopPropagation()
    dispatch(toggleWishlist(product.id))
    toast.success(wishlisted ? 'Removed from wishlist' : 'Saved to wishlist')
  }

  // Badge priority: sale > flash sale > new arrival > best seller
  const badge = variantDiscount > 0
    ? { text: `-${variantDiscount}%`, className: 'bg-red-500 text-white' }
    : baseDiscount > 0
      ? { text: `-${baseDiscount}%`, className: 'bg-red-500 text-white' }
    : product.isFlashSale
      ? { text: 'Flash Sale', className: 'bg-accent text-accent-foreground' }
      : product.isNewArrival
        ? { text: 'New', className: 'bg-emerald-500 text-white' }
        : product.isBestSeller
          ? { text: 'Best Seller', className: 'bg-amber-500 text-white' }
          : null

  return (
    <motion.article
      className="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-soft transition-all duration-200 hover:-translate-y-1 hover:border-slate-200 hover:shadow-card dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
      initial={{ opacity: 0, y: 16 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: '-20px' }}
      transition={{ duration: 0.35, delay: Math.min((index % 5) * 0.05, 0.2), ease: [0.22, 1, 0.36, 1] }}
    >
      {/* Image area — fixed height, white bg, object-contain */}
      <div className="relative h-[190px] shrink-0 overflow-hidden bg-white sm:h-[210px] dark:bg-slate-800">
        <Link to={`/products/${product.slug}`} aria-label={product.title} tabIndex={-1}>
          {activeImage && !imageFailed ? (
            <img
              alt={product.title}
              className="h-full w-full object-contain p-3 transition-transform duration-500 ease-out group-hover:scale-[1.06]"
              loading="lazy"
              onError={() => setImageFailed(true)}
              src={activeImage}
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center bg-slate-50 dark:bg-slate-800">
              <ImageOff size={28} className="text-slate-300 dark:text-slate-600" />
            </div>
          )}
        </Link>

        {/* Badge */}
        {badge && (
          <span className={`absolute left-2.5 top-2.5 inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold shadow-sm ${badge.className}`}>
            {badge.text}
          </span>
        )}

        {(activeVariant || hasColorOptions || hasSizeOptions) && (
          <span className="absolute left-2.5 bottom-2.5 rounded-full bg-slate-950/75 px-2.5 py-1 text-[10px] font-semibold text-white backdrop-blur">
            {activeVariant ? 'Variant selected' : 'Options available'}
          </span>
        )}

        {/* Out of stock overlay */}
        {isOutOfStock && (
          <div className="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[1px] dark:bg-slate-900/70">
            <span className="rounded-md bg-slate-800 px-3 py-1 text-xs font-bold text-white">Out of Stock</span>
          </div>
        )}

        {/* Hover actions */}
        <div className="absolute right-2.5 top-2.5 flex flex-col gap-1.5 sm:translate-x-2 sm:opacity-0 sm:transition-all sm:duration-200 sm:group-hover:translate-x-0 sm:group-hover:opacity-100 sm:group-focus-within:translate-x-0 sm:group-focus-within:opacity-100">
          <button
            aria-label="Quick view"
            className="flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-600 shadow-md transition hover:bg-primary hover:text-primary-foreground dark:bg-slate-800 dark:text-slate-300"
            onClick={(e) => { e.preventDefault(); onQuickView(product) }}
            type="button"
          >
            <Eye size={14} />
          </button>
          <button
            aria-label={wishlisted ? 'Remove from wishlist' : 'Add to wishlist'}
            className={`flex h-11 w-11 items-center justify-center rounded-full shadow-md transition ${
              wishlisted ? 'bg-red-500 text-white' : 'bg-white text-slate-600 hover:bg-red-500 hover:text-white dark:bg-slate-800 dark:text-slate-300'
            }`}
            onClick={handleWishlist}
            type="button"
          >
            <Heart size={14} className={wishlisted ? 'fill-white' : ''} />
          </button>
        </div>
      </div>

      {/* Card body */}
      <div className="flex flex-1 flex-col gap-1.5 p-3">
        {/* Brand + rating row */}
        <div className="flex items-center justify-between gap-2">
          <p className="min-h-[14px] truncate text-[10px] font-bold uppercase tracking-wider text-primary-foreground">
            {brandName || '\u00A0'}
          </p>
          {(product.rating || product.reviewCount) ? (
            <span className="flex shrink-0 items-center gap-1">
              <StarRating rating={product.rating || 0} size={10} />
              {product.reviewCount ? (
                <span className="text-[10px] text-slate-400">({product.reviewCount})</span>
              ) : null}
            </span>
          ) : null}
        </div>

        <Link
          className="line-clamp-2 min-h-[2.4rem] text-[13px] font-semibold leading-snug text-slate-800 transition hover:text-primary-foreground dark:text-slate-100"
          to={`/products/${product.slug}`}
        >
          {product.title}
        </Link>

        {/* Price row */}
        <div className="flex flex-wrap items-baseline gap-x-1.5 pt-0.5">
          <span className="text-base font-extrabold text-slate-900 dark:text-white">{formatPrice(salePriceValue)}</span>
          {baseDiscount > 0 && (
            <span className="text-xs text-slate-400 line-through">{formatPrice(price)}</span>
          )}
          {isLowStock && !isOutOfStock && (
            <span className="ml-auto shrink-0 text-[10px] font-bold text-accent-foreground">Only {product.stock} left</span>
          )}
        </div>

        {/* Color variants — fixed height with overflow handling */}
        <div className="flex min-h-[18px] flex-wrap items-center gap-1 overflow-hidden">
          {hasColorOptions ? (
            product.colorOptions?.slice(0, 6).map((color) => (
              <button
                aria-label={`Select color ${color.name}`}
                className={`h-4 w-4 shrink-0 rounded-full border-2 transition ${
                  activeColor === color.name ? 'border-primary scale-110' : 'border-slate-200 hover:border-slate-400'
                }`}
                key={color.name}
                onClick={() => setActiveColor(color.name)}
                style={{ backgroundColor: color.value || '#cccccc' }}
                type="button"
              />
              ))
          ) : null}
          {hasSizeOptions ? (
            <div className="ml-1 flex flex-wrap gap-1.5">
              {product.sizeOptions?.slice(0, 4).map((size) => (
                <button
                  className={`rounded-full border px-2.5 py-0.5 text-[10px] font-semibold transition ${
                    activeSize === size
                      ? 'border-primary bg-primary text-primary-foreground'
                      : 'border-slate-200 text-slate-500 hover:border-primary hover:text-primary-foreground dark:border-slate-700 dark:text-slate-400'
                  }`}
                  key={size}
                  onClick={() => setActiveSize(size)}
                  type="button"
                >
                  {size}
                </button>
              ))}
            </div>
          ) : null}
        </div>

        {/* Action buttons */}
        <div className="mt-auto flex items-center gap-2 pt-2">
          <button
            className={`inline-flex flex-1 items-center justify-center gap-1 rounded-lg py-2 text-xs font-bold transition active:scale-95 ${
              isOutOfStock
                ? 'cursor-not-allowed bg-slate-100 text-slate-400 dark:bg-slate-800'
                : 'bg-accent text-accent-foreground hover:bg-accent-600'
            }`}
            disabled={isOutOfStock}
            onClick={handleAddToCart}
            type="button"
          >
            <Plus size={13} />
            {isOutOfStock ? 'Unavailable' : 'Add to Cart'}
          </button>
          <Link
            className="inline-flex flex-1 items-center justify-center gap-1 rounded-lg border border-accent bg-white py-2 text-xs font-bold text-accent-foreground transition hover:bg-accent hover:text-accent-foreground active:scale-95 dark:bg-slate-800 dark:hover:bg-accent"
            to={`/products/${product.slug}`}
          >
            <ShoppingBag size={13} />
            Buy Now
          </Link>
        </div>
      </div>
    </motion.article>
  )
}

export default ProductCard
