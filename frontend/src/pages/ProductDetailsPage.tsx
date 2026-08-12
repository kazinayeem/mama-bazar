import { ChevronRight, GitCompareArrows, Heart, Minus, Plus, Play, ShoppingBag, Star, Truck, Shield, Zap } from 'lucide-react'
import { useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import ProductCard from '../components/common/ProductCard'
import { SEO, getProductSEO } from '../components/common/SEO'
import StarRating from '../components/common/StarRating'
import { useToast } from '../components/common/ToastProvider'
import { authStorage } from '../lib/authStorage'
import { formatPrice } from '../lib/format'
import { useAppDispatch, useAppSelector } from '../store/hooks'
import { addToCart } from '../store/slices/cartSlice'
import { openCart, toggleCompare, toggleWishlist } from '../store/slices/uiSlice'
import {
  useAddReviewMutation,
  useGetProductBySlugQuery,
  useGetRelatedProductsQuery,
  useGetReviewsQuery,
} from '../store/services/commerceApi'
import {
  type Product,
  type ProductVariant,
  findVariantByOptions,
  getVariantEffectivePrice,
  getVariantDiscountPercent,
  hasVariantPriceRange,
} from '../types'

const infoRow = 'flex items-center justify-between py-2.5 border-b border-slate-100 dark:border-slate-800 last:border-0'
const infoLabel = 'text-sm font-semibold text-slate-500'
const infoValue = 'text-sm font-bold text-slate-900 dark:text-white'

function getStockLabel(stock: number, product: Product): { text: string; className: string } {
  if (product.unlimitedStock) return { text: 'In Stock', className: 'bg-success/10 text-success' }
  if (stock <= 0) {
    if (product.backorder) return { text: 'Available on Backorder', className: 'bg-amber-100 text-amber-700' }
    return { text: 'Out of Stock', className: 'bg-slate-100 text-slate-500' }
  }
  const alert = product.lowStockAlert || 5
  if (stock <= alert) return { text: `Only ${stock} left`, className: 'bg-accent/10 text-accent-foreground' }
  return { text: 'In Stock', className: 'bg-success/10 text-success' }
}

function resolvePriceText(product: Product, activeVariant?: ProductVariant): {
  finalPrice: number
  displayPrice: number | null
  discount: number
  showDiscount: boolean
  priceLabel: string
  showOriginalPrice: boolean
} {
  if (activeVariant) {
    const effPrice = getVariantEffectivePrice(activeVariant, product.price, product.discount)
    const variantDiscount = getVariantDiscountPercent(activeVariant)
    const hasRegPrice = !!activeVariant.price
    const hasDiscPrice = !!activeVariant.discountPrice
    const discount = variantDiscount || 0

    return {
      finalPrice: effPrice,
      displayPrice: (hasRegPrice && hasDiscPrice) ? Number(activeVariant.price) : null,
      discount,
      showDiscount: discount > 0,
      priceLabel: '',
      showOriginalPrice: hasRegPrice && hasDiscPrice,
    }
  }

  if (product.variants && product.variants.length > 0 && hasVariantPriceRange(product.variants, product.price, product.discount)) {
    return {
      finalPrice: 0,
      displayPrice: null,
      discount: 0,
      showDiscount: false,
      priceLabel: 'Choose options',
      showOriginalPrice: false,
    }
  }

  const displayPrice = Number(product.price)
  const displaySalePrice = Number(product.salePrice)
  const discountFromDb = Number(product.discount || 0)
  const effectivePrice = displaySalePrice > 0
    ? displaySalePrice
    : discountFromDb > 0
      ? Math.round(displayPrice - (displayPrice * discountFromDb) / 100)
      : displayPrice

  return {
    finalPrice: effectivePrice,
    displayPrice: displaySalePrice > 0 ? displayPrice : (discountFromDb > 0 ? displayPrice : null),
    discount: displaySalePrice > 0
      ? Math.round(((displayPrice - displaySalePrice) / displayPrice) * 100)
      : discountFromDb,
    showDiscount: displaySalePrice > 0 || discountFromDb > 0,
    priceLabel: '',
    showOriginalPrice: displaySalePrice > 0 || discountFromDb > 0,
  }
}

const ProductDetailsPage = () => {
  const { slug } = useParams()
  const dispatch = useAppDispatch()
  const toast = useToast()

  const productQuery = useGetProductBySlugQuery(slug || '')
  const product = productQuery.data

  const [activeImage, setActiveImage] = useState(0)
  const [activeColor, setActiveColor] = useState<string | undefined>(product?.colorOptions?.[0]?.name)
  const [activeSize, setActiveSize] = useState<string | undefined>(product?.sizeOptions?.[0])
  const [quantity, setQuantity] = useState(1)
  const relatedQuery = useGetRelatedProductsQuery(product?.id || 0, { skip: !product })
  const reviewsQuery = useGetReviewsQuery({ productId: product?.id, limit: 20 }, { skip: !product })
  const [addReview, { isLoading: submittingReview }] = useAddReviewMutation()

  const [reviewRating, setReviewRating] = useState(0)
  const [reviewTitle, setReviewTitle] = useState('')
  const [reviewComment, setReviewComment] = useState('')
  const isLoggedIn = Boolean(authStorage.getToken())

  const wishlisted = useAppSelector((state) => (product ? state.ui.wishlist.includes(product.id) : false))
  const compared = useAppSelector((state) => (product ? state.ui.compare.includes(product.id) : false))

  const seoProps = useMemo(
    () => (product ? getProductSEO(product) : { title: 'Loading Product...', description: 'Loading product details...' }),
    [product],
  )

  const hasColor = Boolean(product?.colorOptions && product.colorOptions.length > 0)
  const hasSize = Boolean(product?.sizeOptions && product.sizeOptions.length > 0)
  const needsOptions = hasColor || hasSize

  const activeVariant = useMemo(
    () => (product?.variants ? findVariantByOptions(product.variants, activeColor, activeSize) : undefined),
    [product?.variants, activeColor, activeSize],
  )

  const variantImage = useMemo(() => {
    if (activeVariant?.images?.[0]) return activeVariant.images[0]
    if (activeVariant?.thumbnail) return activeVariant.thumbnail
    const colorOption = product?.colorOptions?.find((c) => c.name === activeColor)
    if (colorOption?.image) return colorOption.image
    return undefined
  }, [activeVariant, activeColor, product?.colorOptions])

  const priceInfo = useMemo(() => {
    if (!product) return { finalPrice: 0, displayPrice: null as number | null, discount: 0, showDiscount: false, priceLabel: '', showOriginalPrice: false }
    return resolvePriceText(product, activeVariant)
  }, [product, activeVariant])

  const { finalPrice, displayPrice, discount, showDiscount, priceLabel, showOriginalPrice } = priceInfo

  const effectiveStock = useMemo(() => {
    if (!product) return 0
    if (activeVariant) return activeVariant.stock
    if (product.variants && product.variants.length > 0) {
      return product.variants.filter((v) => v.status !== 'inactive' && v.availability !== false).reduce((s, v) => s + v.stock, 0)
    }
    return product.stock
  }, [product, activeVariant])

  const variantOutOfStock = useMemo(() => {
    if (!activeVariant) return false
    if (product?.unlimitedStock) return false
    return activeVariant.stock <= 0
  }, [activeVariant, product?.unlimitedStock])

  const outOfStock = useMemo(() => {
    if (!product) return true
    if (product.unlimitedStock) return false
    return effectiveStock <= 0
  }, [product, effectiveStock])

  const allOptionsSelected = useMemo(() => {
    if (!product) return false
    const colorNeeded = hasColor
    const sizeNeeded = hasSize
    if (colorNeeded && sizeNeeded) return !!activeColor && !!activeSize
    if (colorNeeded) return !!activeColor
    if (sizeNeeded) return !!activeSize
    return true
  }, [product, hasColor, hasSize, activeColor, activeSize])

  const stockLabel = useMemo(() => {
    if (!product) return { text: '', className: '' }
    return getStockLabel(effectiveStock, product)
  }, [product, effectiveStock])

  const reviews = reviewsQuery.data || []
  const related = relatedQuery.data || []
  const specs = product?.specs || []

  const maxQty = product?.maxOrder || (activeVariant ? activeVariant.stock : product?.stock || 1)
  const minQty = product?.minOrder || 1

  const showSelectOptionsPrice = priceLabel === 'Choose options'

  const handleAddToCart = (buyNow = false) => {
    if (!product) return
    dispatch(
      addToCart({
        product: {
          id: product.id,
          title: product.title,
          slug: product.slug,
          brand: product.brandInfo?.name || product.brand || '',
          price: finalPrice,
          images: product.images,
          stock: effectiveStock,
        },
        variantId: activeVariant?.id,
        color: activeColor,
        size: activeSize,
        image: variantImage || product.images[activeImage] || product.images[0],
      }),
    )
    toast.success('Added to cart')
    if (buyNow) {
      window.setTimeout(() => dispatch(openCart()), 300)
    }
  }

  const handleWishlist = () => {
    if (!product) return
    dispatch(toggleWishlist(product.id))
    toast.success(wishlisted ? 'Removed from wishlist' : 'Saved to wishlist')
  }

  const handleCompare = () => {
    if (!product) return
    dispatch(toggleCompare(product.id))
    toast.success(compared ? 'Removed from compare' : 'Added to compare')
  }

  const handleSubmitReview = async (event: React.FormEvent) => {
    event.preventDefault()
    if (!product) return
    if (!reviewRating) {
      toast.error('Please select a star rating')
      return
    }
    try {
      await addReview({
        productId: product.id,
        rating: reviewRating,
        title: reviewTitle || undefined,
        comment: reviewComment,
      }).unwrap()
      toast.success('Review submitted! It will appear after approval.')
      setReviewRating(0)
      setReviewTitle('')
      setReviewComment('')
      reviewsQuery.refetch()
    } catch (error) {
      const message = (error as { data?: { message?: string } })?.data?.message || 'Failed to submit review'
      toast.error(message)
    }
  }

  if (productQuery.isLoading) {
    return (
      <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <SEO title="Loading Product..." description="Loading product details..." noIndex />
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:gap-14">
          <div className="animate-pulse">
            <div className="aspect-square rounded-[18px] bg-slate-100 dark:bg-slate-800" />
            <div className="mt-4 grid grid-cols-4 gap-3">
              {Array.from({ length: 4 }).map((_, i) => (
                <div className="aspect-square rounded-xl bg-slate-100 dark:bg-slate-800" key={i} />
              ))}
            </div>
          </div>
          <div className="animate-pulse space-y-4">
            <div className="h-6 w-24 rounded bg-slate-100 dark:bg-slate-800" />
            <div className="h-10 w-3/4 rounded bg-slate-100 dark:bg-slate-800" />
            <div className="h-5 w-40 rounded bg-slate-100 dark:bg-slate-800" />
            <div className="h-12 w-52 rounded bg-slate-100 dark:bg-slate-800" />
            <div className="h-24 w-full rounded bg-slate-100 dark:bg-slate-800" />
          </div>
        </div>
      </main>
    )
  }

  if (productQuery.isError || !product) {
    return (
      <main className="mx-auto max-w-7xl px-4 py-24 text-center sm:px-6 lg:px-8">
        <SEO title="Product Not Found" description="The product you are looking for doesn't exist or is no longer available." noIndex />
        <span className="text-5xl">🔍</span>
        <h1 className="mt-4 font-headline text-2xl font-extrabold text-slate-900 dark:text-white">Product not found</h1>
        <p className="mt-2 text-sm text-slate-500">The product you are looking for doesn&apos;t exist or is no longer available.</p>
        <Link className="mt-6 inline-block rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground" to="/shop">
          Back to Shop
        </Link>
      </main>
    )
  }

  const disableAddToCart = outOfStock || variantOutOfStock || (needsOptions && !allOptionsSelected)

  return (
    <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <SEO {...seoProps} />

      <nav aria-label="Breadcrumb" className="mb-8 flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-400">
        <Link className="transition hover:text-primary-foreground" to="/">Home</Link>
        <ChevronRight size={13} />
        {product.category && (
          <>
            <Link className="transition hover:text-primary-foreground" to={`/shop?category=${product.category.slug}`}>
              {product.category.name}
            </Link>
            <ChevronRight size={13} />
          </>
        )}
        {product.subCategory && (
          <>
            <Link className="transition hover:text-primary-foreground" to={`/shop?category=${product.subCategory.slug}`}>
              {product.subCategory.name}
            </Link>
            <ChevronRight size={13} />
          </>
        )}
        {product.childCategory && (
          <>
            <Link className="transition hover:text-primary-foreground" to={`/shop?category=${product.childCategory.slug}`}>
              {product.childCategory.name}
            </Link>
            <ChevronRight size={13} />
          </>
        )}
        <span className="text-slate-700 dark:text-slate-300">{product.title.slice(0, 30)}...</span>
      </nav>

      <div className="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:gap-14">
        <div>
          <div className="overflow-hidden rounded-[18px] bg-slate-100 dark:bg-slate-800">
            {product.images.length > 0 ? (
              <img
                alt={`${product.title} - Mama Bazar`}
                className="aspect-square w-full object-cover transition-transform duration-700 hover:scale-105"
                src={variantImage || product.images[activeImage] || product.images[0]}
              />
            ) : (
              <div className="flex aspect-square w-full items-center justify-center text-sm font-semibold text-slate-400">
                No image available
              </div>
            )}
          </div>
          {product.images.length > 1 && (
            <div className="mt-4 grid grid-cols-4 gap-3">
              {product.images.map((image, index) => (
                <button
                  aria-label={`View image ${index + 1}`}
                  className={`overflow-hidden rounded-xl transition ${(variantImage ? false : activeImage === index) ? 'ring-2 ring-primary ring-offset-2' : 'opacity-70 hover:opacity-100'}`}
                  key={image}
                  onClick={() => setActiveImage(index)}
                  type="button"
                >
                  <img alt={`${product.title} ${index + 1}`} className="aspect-square w-full object-cover" src={image} />
                </button>
              ))}
            </div>
          )}
          {product.videoUrl && (
            <div className="mt-4">
              <a
                className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-primary hover:text-primary-foreground dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                href={product.videoUrl}
                target="_blank"
                rel="noopener noreferrer"
              >
                <Play size={14} className="fill-current" /> Watch Video
              </a>
            </div>
          )}
        </div>

        <div className="flex flex-col">
          <div className="flex flex-wrap items-center gap-2">
            {product.brandInfo?.name && (
              <span className="rounded-full bg-primary/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-primary-foreground">
                {product.brandInfo.name}
              </span>
            )}
            {product.isNewArrival && (
              <span className="rounded-full bg-emerald-500/10 px-3 py-1 text-[11px] font-bold text-emerald-600">New Arrival</span>
            )}
            {product.isBestSeller && (
              <span className="rounded-full bg-amber-500/10 px-3 py-1 text-[11px] font-bold text-amber-600">Best Seller</span>
            )}
            {product.isLimitedEdition && (
              <span className="rounded-full bg-purple-500/10 px-3 py-1 text-[11px] font-bold text-purple-600">Limited Edition</span>
            )}
            {product.isOfficial && (
              <span className="rounded-full bg-blue-500/10 px-3 py-1 text-[11px] font-bold text-blue-600">Official</span>
            )}
            {showDiscount && (
              <span className="inline-flex items-center gap-1 rounded-full bg-accent/10 px-3 py-1 text-[11px] font-bold text-accent-foreground">
                <Zap size={11} className="fill-accent" /> Save {discount}%
              </span>
            )}
          </div>

          <h1 className="mt-4 font-headline text-3xl font-extrabold leading-tight tracking-tight text-slate-900 dark:text-white sm:text-4xl">
            {product.title}
          </h1>

          <div className="mt-4 flex items-center gap-3">
            <StarRating rating={product.rating || 0} size={16} />
            <span className="text-sm font-semibold text-slate-700 dark:text-slate-300">
              {product.rating ? product.rating.toFixed(1) : 'No rating'}
            </span>
            <span className="text-sm text-slate-400">({product.reviewCount || 0} reviews)</span>
          </div>

          <div className="mt-6 flex flex-wrap items-end gap-3">
            {showSelectOptionsPrice ? (
              <p className="font-headline text-2xl font-bold text-slate-400 dark:text-slate-500">
                Select options to see price
              </p>
            ) : (
              <>
                <p className="font-headline text-4xl font-black tracking-tight text-slate-900 dark:text-white">
                  {formatPrice(finalPrice)}
                </p>
                {showOriginalPrice && displayPrice && (
                  <p className="pb-1 text-lg text-slate-400 line-through">{formatPrice(displayPrice)}</p>
                )}
              </>
            )}
            {activeVariant?.sku && (
              <span className="pb-1 text-xs text-slate-400">SKU: {activeVariant.sku}</span>
            )}
          </div>

          {(product.shortDescription || product.description) && (
            <p className="mt-4 text-[15px] leading-8 text-slate-600 dark:text-slate-300">
              {product.shortDescription || product.description}
            </p>
          )}

          {hasColor && (
            <div className="mt-7">
              <p className="mb-3 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                Color: <span className="text-slate-900 dark:text-white">{activeColor || 'Select'}</span>
              </p>
              <div className="flex flex-wrap gap-2">
                {product.colorOptions!.map((color) => (
                  <button
                    aria-label={`Select color ${color.name}`}
                    className={`flex h-11 w-11 items-center justify-center rounded-full border-2 transition ${
                      activeColor === color.name
                        ? 'border-primary ring-2 ring-primary/30'
                        : 'border-slate-200 hover:border-slate-300 dark:border-slate-700'
                    }`}
                    key={color.name}
                    onClick={() => setActiveColor(color.name)}
                    style={color.value ? { backgroundColor: color.value } : undefined}
                    title={color.name}
                    type="button"
                  >
                    {!color.value && <span className="text-[9px] font-bold text-slate-500">{color.name.slice(0, 3)}</span>}
                  </button>
                ))}
              </div>
            </div>
          )}

          {hasSize && (
            <div className="mt-5">
              <p className="mb-3 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                Size: <span className="text-slate-900 dark:text-white">{activeSize || 'Select'}</span>
              </p>
              <div className="flex flex-wrap gap-2">
                {product.sizeOptions!.map((size) => {
                  const variantForSize = hasColor && activeColor
                    ? findVariantByOptions(product.variants, activeColor, size)
                    : findVariantByOptions(product.variants, undefined, size)
                  const isUnavailable = Boolean(hasColor && activeColor && !variantForSize)
                  const isOos = Boolean(variantForSize && variantForSize.stock <= 0)
                  return (
                    <button
                      aria-label={`Select size ${size}`}
                      className={`flex h-11 min-w-[3rem] items-center justify-center rounded-lg border px-4 transition ${
                        activeSize === size
                          ? 'border-primary bg-primary/5 text-primary-foreground ring-1 ring-primary/30'
                          : isUnavailable
                            ? 'border-slate-100 text-slate-300 cursor-not-allowed line-through dark:border-slate-700 dark:text-slate-600'
                            : isOos
                              ? 'border-amber-200 text-amber-600 bg-amber-50 dark:border-amber-800 dark:text-amber-400 dark:bg-amber-950'
                              : 'border-slate-200 text-slate-700 hover:border-slate-300 dark:border-slate-700 dark:text-slate-200'
                      }`}
                      disabled={isUnavailable}
                      key={size}
                      onClick={() => !isUnavailable && setActiveSize(size)}
                      type="button"
                    >
                      <span className="text-sm font-bold uppercase">{size}</span>
                    </button>
                  )
                })}
              </div>
            </div>
          )}

          {needsOptions && allOptionsSelected && variantOutOfStock && (
            <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-400">
              This variant is currently out of stock
            </div>
          )}

          <div className="mt-7 flex items-center gap-5">
            <div>
              <p className="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Quantity</p>
              <div className="flex items-center gap-1 rounded-full border border-slate-200 px-2 py-1.5 dark:border-slate-700">
                <button
                  aria-label="Decrease quantity"
                  className="flex h-8 w-8 items-center justify-center rounded-full text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                  disabled={quantity <= minQty}
                  onClick={() => setQuantity((q) => Math.max(minQty, q - 1))}
                  type="button"
                >
                  <Minus size={15} />
                </button>
                <span className="w-10 text-center font-bold">{quantity}</span>
                <button
                  aria-label="Increase quantity"
                  className="flex h-8 w-8 items-center justify-center rounded-full text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                  disabled={outOfStock || variantOutOfStock || quantity >= maxQty}
                  onClick={() => setQuantity((q) => Math.min(maxQty, q + 1))}
                  type="button"
                >
                  <Plus size={15} />
                </button>
              </div>
            </div>
            <div className="flex-1">
              <p className="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Availability</p>
              <span className={`inline-flex rounded-full px-3 py-1.5 text-xs font-bold ${stockLabel.className}`}>
                {stockLabel.text}
              </span>
            </div>
          </div>

          <div className="mt-8 flex flex-col gap-3 sm:flex-row">
            <button
              className="inline-flex flex-1 items-center justify-center gap-2 rounded-full border-2 border-accent text-accent-foreground px-6 py-4 text-sm font-bold transition hover:bg-accent hover:text-accent-foreground active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
              disabled={Boolean(disableAddToCart)}
              onClick={() => handleAddToCart()}
              type="button"
            >
              <ShoppingBag size={17} /> Add to Cart
            </button>
            <button
              className="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-accent px-6 py-4 text-sm font-bold text-accent-foreground transition hover:bg-accent-600 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
              disabled={Boolean(disableAddToCart)}
              onClick={() => handleAddToCart(true)}
              type="button"
            >
              <Zap size={16} className="fill-white" /> Buy Now
            </button>
          </div>

          <div className="mt-4 flex gap-3">
            <button
              className={`inline-flex flex-1 items-center justify-center gap-2 rounded-full border px-5 py-3 text-sm font-bold transition ${
                wishlisted
                  ? 'border-accent bg-accent/10 text-accent-foreground'
                  : 'border-slate-200 text-slate-700 hover:border-accent hover:text-accent-foreground dark:border-slate-700 dark:text-slate-200'
              }`}
              onClick={handleWishlist}
              type="button"
            >
              <Heart size={16} className={wishlisted ? 'fill-accent' : ''} /> {wishlisted ? 'Saved' : 'Wishlist'}
            </button>
            <button
              className={`inline-flex flex-1 items-center justify-center gap-2 rounded-full border px-5 py-3 text-sm font-bold transition ${
                compared
                  ? 'border-primary bg-primary/10 text-primary-foreground'
                  : 'border-slate-200 text-slate-700 hover:border-primary hover:text-primary-foreground dark:border-slate-700 dark:text-slate-200'
              }`}
              onClick={handleCompare}
              type="button"
            >
              <GitCompareArrows size={16} /> {compared ? 'Comparing' : 'Compare'}
            </button>
          </div>

          <div className="mt-6 rounded-[18px] border border-slate-100 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
            {product.warranty && (
              <div className={infoRow}>
                <span className={`${infoLabel} flex items-center gap-1.5`}><Shield size={14} /> Warranty</span>
                <span className={infoValue}>{product.warranty}</span>
              </div>
            )}
            {product.countryOfOrigin && (
              <div className={infoRow}>
                <span className={infoLabel}>Country of Origin</span>
                <span className={infoValue}>{product.countryOfOrigin}</span>
              </div>
            )}
            {product.sku && !activeVariant?.sku && (
              <div className={infoRow}>
                <span className={infoLabel}>SKU</span>
                <span className={infoValue}>{product.sku}</span>
              </div>
            )}
            {product.brandInfo?.name && (
              <div className={infoRow}>
                <span className={infoLabel}>Brand</span>
                <span className={infoValue}>{product.brandInfo.name}</span>
              </div>
            )}
            {product.category && (
              <div className={infoRow}>
                <span className={infoLabel}>Category</span>
                <span className={infoValue}>{product.category.name}</span>
              </div>
            )}
            {product.collection && (
              <div className={infoRow}>
                <span className={infoLabel}>Collection</span>
                <span className={infoValue}>{product.collection.name}</span>
              </div>
            )}
          </div>
        </div>
      </div>

      <section className="mt-16">
        <div className="rounded-[18px] border border-slate-100 bg-white p-6 shadow-soft dark:border-slate-800 dark:bg-slate-900 sm:p-8">
          {product.description && (
            <div className="mb-8">
              <h2 className="mb-4 font-headline text-xl font-extrabold text-slate-900 dark:text-white">Description</h2>
              <div
                className="prose prose-sm max-w-none text-slate-600 dark:text-slate-300
                  [&_h2]:mt-6 [&_h2]:text-lg [&_h2]:font-bold [&_h2]:text-slate-900 [&_h2]:dark:text-white
                  [&_h3]:mt-4 [&_h3]:text-base [&_h3]:font-semibold
                  [&_p]:mt-3 [&_p]:leading-7
                  [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-5
                  [&_ol]:mt-3 [&_ol]:list-decimal [&_ol]:pl-5
                  [&_li]:mt-1 [&_li]:leading-7
                  [&_strong]:font-semibold [&_strong]:text-slate-900 [&_strong]:dark:text-white
                  [&_img]:mt-4 [&_img]:rounded-lg [&_img]:max-w-full
                  [&_table]:mt-4 [&_table]:w-full [&_table]:border [&_table]:border-slate-200
                  [&_th]:bg-slate-50 [&_th]:px-3 [&_th]:py-2 [&_th]:text-left [&_th]:text-sm [&_th]:font-semibold
                  [&_td]:border-t [&_td]:border-slate-200 [&_td]:px-3 [&_td]:py-2 [&_td]:text-sm"
                dangerouslySetInnerHTML={{ __html: product.description }}
              />
            </div>
          )}

          {specs.length > 0 && (
            <div className="mb-8">
              <h2 className="mb-4 font-headline text-xl font-extrabold text-slate-900 dark:text-white">Specifications</h2>
              <div className="overflow-hidden rounded-xl border border-slate-100 dark:border-slate-800">
                {specs.map((spec, i) => (
                  <div
                    className={`flex items-center ${i % 2 === 0 ? 'bg-slate-50 dark:bg-slate-800/50' : 'bg-white dark:bg-slate-900'}`}
                    key={spec.id}
                  >
                    <span className="w-1/3 px-4 py-3 text-sm font-semibold text-slate-500">{spec.label}</span>
                    <span className="w-2/3 px-4 py-3 text-sm font-bold text-slate-900 dark:text-white">{spec.value}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {product.features && product.features.length > 0 && (
            <div className="mb-8">
              <h2 className="mb-4 font-headline text-xl font-extrabold text-slate-900 dark:text-white">Features</h2>
              <ul className="space-y-2">
                {product.features.map((feature, i) => (
                  <li className="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300" key={i}>
                    <span className="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                    {feature}
                  </li>
                ))}
              </ul>
            </div>
          )}

          {product.tags && product.tags.length > 0 && (
            <div>
              <h2 className="mb-3 font-headline text-xl font-extrabold text-slate-900 dark:text-white">Tags</h2>
              <div className="flex flex-wrap gap-2">
                {product.tags.map((tag) => (
                  <span
                    className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    key={tag}
                  >
                    {tag}
                  </span>
                ))}
              </div>
            </div>
          )}
        </div>
      </section>

      {product.returnPolicy && (
        <section className="mt-6">
          <div className="rounded-[18px] border border-slate-100 bg-white p-6 shadow-soft dark:border-slate-800 dark:bg-slate-900">
            <h2 className="mb-3 font-headline text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
              <Truck size={18} /> Return Policy
            </h2>
            <p className="text-sm leading-7 text-slate-600 dark:text-slate-300">{product.returnPolicy}</p>
          </div>
        </section>
      )}

      <section className="mt-16">
        <div className="mb-8">
          <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-primary-foreground">Customer feedback</p>
          <h2 className="mt-2 font-headline text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Reviews ({product.reviewCount || reviews.length})
          </h2>
        </div>

        <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <div>
            <h3 className="mb-4 font-headline text-lg font-extrabold text-slate-900 dark:text-white">Write a review</h3>
            {isLoggedIn ? (
              <form className="rounded-[18px] border border-slate-100 bg-white p-5 shadow-soft dark:border-slate-800 dark:bg-slate-900" onSubmit={handleSubmitReview}>
                <div className="mb-4 flex items-center gap-1">
                  {[1, 2, 3, 4, 5].map((value) => (
                    <button
                      aria-label={`Rate ${value} star${value > 1 ? 's' : ''}`}
                      className="transition hover:scale-110"
                      key={value}
                      onClick={() => setReviewRating(value)}
                      type="button"
                    >
                      <Star size={22} className={value <= reviewRating ? 'fill-accent text-accent-foreground' : 'text-slate-300 dark:text-slate-600'} />
                    </button>
                  ))}
                </div>
                <input
                  className="mb-3 w-full rounded-full border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-primary dark:border-slate-700 dark:bg-slate-800"
                  onChange={(event) => setReviewTitle(event.target.value)}
                  placeholder="Review title (optional)"
                  type="text"
                  value={reviewTitle}
                />
                <textarea
                  className="mb-3 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary dark:border-slate-700 dark:bg-slate-800"
                  maxLength={5000}
                  onChange={(event) => setReviewComment(event.target.value)}
                  placeholder="Share your experience with this product..."
                  required
                  rows={4}
                  value={reviewComment}
                />
                <button
                  className="w-full rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
                  disabled={submittingReview}
                  type="submit"
                >
                  {submittingReview ? 'Submitting...' : 'Submit Review'}
                </button>
              </form>
            ) : (
              <div className="rounded-[18px] border border-slate-100 bg-white p-6 text-center shadow-soft dark:border-slate-800 dark:bg-slate-900">
                <p className="text-sm text-slate-500">Please log in to write a review.</p>
                <Link className="mt-4 inline-block rounded-full bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground" to="/auth/login">
                  Login
                </Link>
              </div>
            )}
          </div>

          <div className="lg:col-span-2">
            {reviewsQuery.isLoading ? (
              <div className="space-y-4">
                {Array.from({ length: 3 }).map((_, index) => (
                  <div className="animate-pulse rounded-[18px] border border-slate-100 bg-white p-5 dark:border-slate-800 dark:bg-slate-900" key={index}>
                    <div className="h-4 w-32 rounded bg-slate-100 dark:bg-slate-800" />
                    <div className="mt-3 h-3 w-full rounded bg-slate-100 dark:bg-slate-800" />
                    <div className="mt-2 h-3 w-2/3 rounded bg-slate-100 dark:bg-slate-800" />
                  </div>
                ))}
              </div>
            ) : reviews.length === 0 ? (
              <div className="flex flex-col items-center justify-center rounded-[18px] border border-dashed border-slate-200 bg-white py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                <span className="text-4xl">💬</span>
                <p className="mt-4 font-headline text-lg font-extrabold text-slate-900 dark:text-white">No reviews yet</p>
                <p className="mt-2 text-sm text-slate-500">Be the first to share your experience with this product.</p>
              </div>
            ) : (
              <div className="space-y-4">
                {reviews.map((review) => (
                  <article className="rounded-[18px] border border-slate-100 bg-white p-5 shadow-soft dark:border-slate-800 dark:bg-slate-900" key={review.id}>
                    <div className="flex items-center justify-between gap-3">
                      <div className="flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-sm font-black text-primary-foreground">
                          {(review.customerName || 'Anonymous').slice(0, 1)}
                        </span>
                        <div>
                          <p className="text-sm font-bold text-slate-900 dark:text-white">{review.customerName || 'Anonymous'}</p>
                          <p className="text-xs text-slate-400">
                            {new Date(review.createdAt).toLocaleDateString('en-BD', { year: 'numeric', month: 'long', day: 'numeric' })}
                          </p>
                        </div>
                      </div>
                      <StarRating rating={review.rating} size={13} />
                    </div>
                    {review.title && <h4 className="mt-4 text-sm font-extrabold text-slate-900 dark:text-white">{review.title}</h4>}
                    <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{review.comment}</p>
                  </article>
                ))}
              </div>
            )}
          </div>
        </div>
      </section>

      {related.length > 0 && (
        <section className="mt-20">
          <div className="mb-8 flex items-end justify-between">
            <div>
              <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-primary-foreground">You may also like</p>
              <h2 className="mt-2 font-headline text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">Related Products</h2>
            </div>
            <Link className="text-sm font-bold text-primary-foreground hover:underline" to={`/shop?category=${product.category?.slug || ''}`}>
              View more
            </Link>
          </div>
          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {related.map((relatedProduct, index) => (
              <ProductCard index={index} key={relatedProduct.id} onQuickView={() => undefined} product={relatedProduct} />
            ))}
          </div>
        </section>
      )}
    </main>
  )
}

export default ProductDetailsPage