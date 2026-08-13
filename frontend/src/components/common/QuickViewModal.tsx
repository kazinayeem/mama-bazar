import { AnimatePresence, motion } from 'framer-motion'
import { ShoppingBag, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useToast } from './ToastProvider'
import { formatNumber, formatPrice } from '../../lib/format'
import type { Product } from '../../types'
import { findVariantByOptions, getVariantEffectivePrice } from '../../types'
import { useAppDispatch } from '../../store/hooks'
import { addToCart } from '../../store/slices/cartSlice'
import StarRating from './StarRating'

interface QuickViewModalProps {
  product: Product | null
  onClose: () => void
}

const QuickViewModal = ({ product, onClose }: QuickViewModalProps) => {
  const dispatch = useAppDispatch()
  const toast = useToast()
  const [activeImage, setActiveImage] = useState(0)
  const [activeColor] = useState<string | undefined>(product?.colorOptions?.[0]?.name)
  const [activeSize] = useState<string | undefined>(product?.sizeOptions?.[0])

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [onClose])

  if (!product) return null

  const activeVariant = product.variants ? findVariantByOptions(product.variants, activeColor, activeSize) : undefined

  const price = Number(product.price)
  const discount = Number(product.discount || 0)
  const salePriceValue = activeVariant
    ? getVariantEffectivePrice(activeVariant, product.price, product.discount)
    : Math.round(price - (price * discount) / 100)

  const handleAdd = () => {
    dispatch(
      addToCart({
        product: {
          id: product.id,
          title: product.title,
          slug: product.slug,
          brand: product.brandInfo?.name || product.brand || '',
          price: salePriceValue,
          images: product.images,
          stock: product.stock,
        },
        variantId: activeVariant?.id,
        size: activeSize,
        color: activeColor,
        image: product.images[activeImage],
      }),
    )
    toast.success('Added to cart')
    onClose()
  }

  return (
    <AnimatePresence>
      <motion.div
        animate={{ opacity: 1 }}
        className="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        exit={{ opacity: 0 }}
        initial={{ opacity: 0 }}
        onClick={onClose}
        role="dialog"
        aria-modal="true"
        aria-label={`Quick view ${product.title}`}
      >
        <motion.div
          animate={{ opacity: 1, scale: 1, y: 0 }}
          className="relative grid max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-[18px] bg-white shadow-lift md:grid-cols-2"
          exit={{ opacity: 0, scale: 0.96, y: 12 }}
          initial={{ opacity: 0, scale: 0.96, y: 12 }}
          onClick={(event) => event.stopPropagation()}
          transition={{ duration: 0.25, ease: [0.22, 1, 0.36, 1] }}
        >
          <button
            aria-label="Close quick view"
            className="absolute right-3 top-3 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-soft transition hover:bg-accent hover:text-accent-foreground"
            onClick={onClose}
            type="button"
          >
            <X size={16} />
          </button>

          <div className="bg-slate-100">
            <img
              alt={product.title}
              className="h-64 w-full object-cover md:h-full md:max-h-[70vh]"
              src={product.images[activeImage]}
            />
          </div>

          <div className="flex flex-col gap-3 p-6">
            <p className="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600">{product.brandInfo?.name || product.brand || ''}</p>
            <Link className="font-headline text-xl font-extrabold leading-tight text-slate-900 hover:text-emerald-600" to={`/products/${product.slug}`}>
              {product.title}
            </Link>
            <div className="flex items-center gap-2">
              <StarRating rating={product.rating || 0} />
              <span className="text-xs text-slate-500">({formatNumber(product.reviewCount || 0)} reviews)</span>
            </div>

            <div className="mt-1 flex items-end gap-2">
              <p className="text-2xl font-extrabold text-slate-900">{formatPrice(salePriceValue)}</p>
              {discount > 0 && <p className="pb-1 text-sm text-slate-400 line-through">{formatPrice(price)}</p>}
              {discount > 0 && <span className="mb-1 rounded-full bg-accent/10 px-2 py-0.5 text-[11px] font-bold text-accent-foreground">-{discount}%</span>}
            </div>

            <p className="text-sm leading-6 text-slate-500">{product.description}</p>

            {product.images.length > 1 && (
              <div className="flex gap-2">
                {product.images.map((image, index) => (
                  <button
                    aria-label={`View image ${index + 1}`}
                    className={`overflow-hidden rounded-lg ring-2 transition ${activeImage === index ? 'ring-primary' : 'ring-transparent'}`}
                    key={image}
                    onClick={() => setActiveImage(index)}
                    type="button"
                  >
                    <img alt={`${product.title} ${index + 1}`} className="h-14 w-14 object-cover" src={image} />
                  </button>
                ))}
              </div>
            )}

            <div className="mt-auto flex flex-col gap-2 pt-3">
              <button
                className="inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary px-5 py-3 text-sm font-bold text-primary-foreground transition hover:bg-primary-700 active:scale-95"
                onClick={handleAdd}
                type="button"
              >
                <ShoppingBag size={16} /> Add to Cart
              </button>
              <Link
                className="inline-flex w-full items-center justify-center gap-2 rounded-full border border-slate-200 px-5 py-3 text-sm font-bold text-slate-800 transition hover:border-primary hover:text-emerald-600"
                to={`/products/${product.slug}`}
                onClick={onClose}
              >
                View Full Details
              </Link>
            </div>

            <div className="flex flex-wrap gap-x-4 gap-y-1 pt-2 text-[11px] font-semibold text-slate-500">
            </div>
          </div>
        </motion.div>
      </motion.div>
    </AnimatePresence>
  )
}

export default QuickViewModal
