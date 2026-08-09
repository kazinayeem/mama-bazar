import { ChevronDown, ChevronLeft, ChevronRight, SlidersHorizontal, Star, X } from 'lucide-react'
import { useMemo, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import ProductCard from '../components/common/ProductCard'
import QuickViewModal from '../components/common/QuickViewModal'
import { SEO } from '../components/common/SEO'
import StarRating from '../components/common/StarRating'
import { useGetBrandsQuery, useGetCategoriesQuery, useGetProductsQuery } from '../store/services/commerceApi'
import type { Product } from '../types'

const sortOptions = [
  { value: 'newest', label: 'Newest' },
  { value: 'price_asc', label: 'Price: Low to High' },
  { value: 'price_desc', label: 'Price: High to Low' },
  { value: 'rating_desc', label: 'Top Rated' },
  { value: 'title_asc', label: 'Title: A to Z' },
  { value: 'title_desc', label: 'Title: Z to A' },
  { value: 'oldest', label: 'Oldest' },
]

const PAGE_SIZE = 12

const ShopPage = () => {
  const [searchParams, setSearchParams] = useSearchParams()
  const [mobileFiltersOpen, setMobileFiltersOpen] = useState(false)
  const [quickViewProduct, setQuickViewProduct] = useState<Product | null>(null)

  const selectedCategory = searchParams.get('category') || ''
  const search = searchParams.get('search') || ''
  const sort = searchParams.get('sort') || ''
  const selectedBrand = searchParams.get('brand') || ''
  const minPrice = Number(searchParams.get('minPrice') || 0)
  const maxPrice = Number(searchParams.get('maxPrice') || 0)
  const minRating = Number(searchParams.get('rating') || 0)
  const inStockOnly = searchParams.get('stock') === '1'
  const onSaleOnly = searchParams.get('sale') === '1'
  const page = Math.max(1, Number(searchParams.get('page') || 1))

  const setParam = (key: string, value?: string | number) => {
    const next = new URLSearchParams(searchParams)
    if (value === undefined || value === '' || value === 'all' || value === 0) {
      next.delete(key)
    } else {
      next.set(key, String(value))
    }
    if (key !== 'page') next.delete('page')
    setSearchParams(next, { replace: true })
  }

  const toggleParam = (key: string) => {
    const next = new URLSearchParams(searchParams)
    if (next.get(key) === '1') next.delete(key)
    else next.set(key, '1')
    next.delete('page')
    setSearchParams(next, { replace: true })
  }

  const goToPage = (nextPage: number) => {
    const next = new URLSearchParams(searchParams)
    if (nextPage <= 1) next.delete('page')
    else next.set('page', String(nextPage))
    setSearchParams(next, { replace: true })
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  const categoriesQuery = useGetCategoriesQuery()
  const brandsQuery = useGetBrandsQuery()

  const productsQuery = useGetProductsQuery({
    page,
    limit: PAGE_SIZE,
    category: selectedCategory || undefined,
    search: search || undefined,
    brand: selectedBrand || undefined,
    sort: sort || undefined,
    minPrice: minPrice || undefined,
    maxPrice: maxPrice || undefined,
    minRating: minRating || undefined,
    inStock: inStockOnly || undefined,
    sale: onSaleOnly || undefined,
  })

  const products = productsQuery.data?.data || []
  const total = productsQuery.data?.total || 0
  const totalPages = productsQuery.data?.totalPages || 1
  const isLoading = productsQuery.isLoading
  const isError = productsQuery.isError

  const categories = categoriesQuery.data || []
  const brands = brandsQuery.data || []
  const selectedCategoryName = categories.find((c) => c.slug === selectedCategory)?.name

  const activeFilterCount =
    (selectedCategory ? 1 : 0) +
    (search ? 1 : 0) +
    (selectedBrand ? 1 : 0) +
    (minPrice > 0 || maxPrice > 0 ? 1 : 0) +
    (minRating ? 1 : 0) +
    (inStockOnly ? 1 : 0) +
    (onSaleOnly ? 1 : 0)

  const seoData = useMemo(() => {
    const brandName = selectedBrand ? brands.find((b) => b.slug === selectedBrand)?.name : ''
    const catName = selectedCategoryName || ''

    if (search) {
      return {
        title: `Search results for "${search}"`,
        description: `Find products matching "${search}" on Mama Bazar. Browse our collection and find the best deals.`,
        url: `/shop?search=${encodeURIComponent(search)}`,
      }
    }
    if (catName && brandName) {
      return {
        title: `${catName} - ${brandName} Products`,
        description: `Shop ${catName} products by ${brandName} at Mama Bazar. Browse our collection.`,
        url: `/shop?category=${selectedCategory}&brand=${selectedBrand}`,
      }
    }
    if (catName) {
      return {
        title: `${catName} Products`,
        description: `Shop ${catName} products at Mama Bazar. Browse our collection of ${catName}.`,
        url: `/shop?category=${selectedCategory}`,
      }
    }
    if (brandName) {
      return {
        title: `${brandName} Products`,
        description: `Shop ${brandName} products at Mama Bazar. Browse our collection of ${brandName}.`,
        url: `/shop?brand=${selectedBrand}`,
      }
    }
    return {
      title: 'Shop All Products',
      description: 'Browse all products at Mama Bazar. Find the best deals on premium products.',
      url: '/shop',
    }
  }, [search, selectedCategory, selectedBrand, selectedCategoryName, brands])

  const clearAll = () => setSearchParams(new URLSearchParams(), { replace: true })

  const [localMax, setLocalMax] = useState(maxPrice || '')
  const [localMin, setLocalMin] = useState(minPrice || '')

  const filterSection =
    'rounded-[18px] border border-slate-100 bg-white p-5 shadow-soft dark:border-slate-800 dark:bg-slate-900'
  const filterTitle = 'mb-3 text-xs font-extrabold uppercase tracking-[0.16em] text-slate-900 dark:text-white'
  const filterRow = 'flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition'

  const sidebar = (
    <div className="space-y-5">
      <div className={filterSection}>
        <h3 className={filterTitle}>Categories</h3>
        <div className="space-y-1">
          <button
            className={`${filterRow} ${
              !selectedCategory ? 'bg-primary/10 text-primary' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
            }`}
            onClick={() => setParam('category', '')}
            type="button"
          >
            All Products
          </button>
          {categories.map((category) => (
            <button
              className={`${filterRow} ${
                selectedCategory === category.slug
                  ? 'bg-primary/10 text-primary'
                  : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
              }`}
              key={category.slug}
              onClick={() => setParam('category', category.slug)}
              type="button"
            >
              <span className="flex items-center gap-2.5">
                {category.image ? (
                  <img alt="" className="h-5 w-5 rounded-md object-cover" src={category.image} />
                ) : (
                  <span className="flex h-5 w-5 items-center justify-center rounded-md bg-primary/10 text-[9px] font-black text-primary">
                    {category.name.slice(0, 1)}
                  </span>
                )}
                {category.name}
              </span>
            </button>
          ))}
        </div>
      </div>

      <div className={filterSection}>
        <h3 className={filterTitle}>Price Range</h3>
        <div className="mt-3 flex gap-2">
          <input
            aria-label="Minimum price"
            className="w-full rounded-full border border-slate-200 px-3 py-2 text-sm outline-none focus:border-primary dark:border-slate-700 dark:bg-slate-800"
            min="0"
            onChange={(event) => setLocalMin(event.target.value)}
            onBlur={() => setParam('minPrice', localMin === '' ? undefined : Number(localMin))}
            placeholder="Min Tk"
            type="number"
            value={localMin}
          />
          <input
            aria-label="Maximum price"
            className="w-full rounded-full border border-slate-200 px-3 py-2 text-sm outline-none focus:border-primary dark:border-slate-700 dark:bg-slate-800"
            min="0"
            onChange={(event) => setLocalMax(event.target.value)}
            onBlur={() => setParam('maxPrice', localMax === '' ? undefined : Number(localMax))}
            placeholder="Max Tk"
            type="number"
            value={localMax}
          />
        </div>
      </div>

      <div className={filterSection}>
        <h3 className={filterTitle}>Brand</h3>
        <div className="space-y-1">
          {brands.map((brand) => {
            const active = selectedBrand === brand.slug
            return (
              <button
                className={`${filterRow} ${
                  active ? 'bg-primary/10 text-primary' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                }`}
                key={brand.slug}
                onClick={() => setParam('brand', active ? undefined : brand.slug)}
                type="button"
              >
                <span className="flex items-center gap-2.5">
                  {brand.logo ? (
                    <img alt="" className="h-5 w-5 rounded-md object-contain" src={brand.logo} />
                  ) : (
                    <span className="flex h-5 w-5 items-center justify-center rounded-md bg-primary/10 text-[9px] font-black text-primary">
                      {brand.name.slice(0, 1)}
                    </span>
                  )}
                  {brand.name}
                </span>
              </button>
            )
          })}
        </div>
      </div>

      <div className={filterSection}>
        <h3 className={filterTitle}>Rating</h3>
        <div className="space-y-1">
          {[4.5, 4, 3].map((rating) => (
            <button
              className={`${filterRow} ${
                minRating === rating ? 'bg-primary/10 text-primary' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
              }`}
              key={rating}
              onClick={() => setParam('rating', minRating === rating ? undefined : rating)}
              type="button"
            >
              <StarRating rating={rating} />
              <span>& up</span>
            </button>
          ))}
        </div>
      </div>

      <div className={filterSection}>
        <h3 className={filterTitle}>Availability</h3>
        <div className="space-y-2">
          <label className="flex cursor-pointer items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800">
            <span>In Stock Only</span>
            <input checked={inStockOnly} className="h-4 w-4 accent-primary" onChange={() => toggleParam('stock')} type="checkbox" />
          </label>
          <label className="flex cursor-pointer items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800">
            <span className="flex items-center gap-1.5">
              <Star size={13} className="text-accent" /> On Sale
            </span>
            <input checked={onSaleOnly} className="h-4 w-4 accent-primary" onChange={() => toggleParam('sale')} type="checkbox" />
          </label>
        </div>
      </div>

      {activeFilterCount > 0 && (
        <button
          className="w-full rounded-full border border-slate-200 px-4 py-3 text-sm font-bold text-slate-600 transition hover:border-accent hover:text-accent dark:border-slate-700 dark:text-slate-300"
          onClick={clearAll}
          type="button"
        >
          Clear all filters ({activeFilterCount})
        </button>
      )}
    </div>
  )

  return (
    <main className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
      <SEO
        title={seoData.title}
        description={seoData.description}
        url={seoData.url}
      />
      <div className="mb-8">
        <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-primary">Mama Bazar</p>
        <h1 className="mt-2 font-headline text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
          {search ? `Results for "${search}"` : selectedCategoryName ? selectedCategoryName : 'Shop All Products'}
        </h1>
        <p className="mt-2 text-sm text-slate-500">
          {isLoading ? 'Loading products…' : `${total.toLocaleString('en-BD')} products found`}
          {selectedBrand && !isLoading ? ` from ${brands.find((b) => b.slug === selectedBrand)?.name || ''}` : ''}
        </p>
      </div>

      <div className="flex flex-col gap-8 lg:flex-row">
        {/* Desktop sidebar */}
        <aside className="hidden w-72 shrink-0 lg:block">
          <div className="sticky top-32 max-h-[calc(100vh-9rem)] overflow-y-auto pr-1">{sidebar}</div>
        </aside>

        {/* Mobile filter drawer */}
        {mobileFiltersOpen && (
          <div className="fixed inset-0 z-[200] lg:hidden">
            <div className="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" onClick={() => setMobileFiltersOpen(false)} />
            <div className="absolute inset-y-0 left-0 w-[86%] max-w-sm overflow-y-auto bg-slate-50 p-5 shadow-lift dark:bg-slate-950">
              <div className="mb-5 flex items-center justify-between">
                <h2 className="flex items-center gap-2 font-headline text-lg font-extrabold text-slate-900 dark:text-white">
                  <SlidersHorizontal size={18} className="text-primary" /> Filters
                </h2>
                <button
                  aria-label="Close filters"
                  className="flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-600 shadow-soft dark:bg-slate-800 dark:text-slate-300"
                  onClick={() => setMobileFiltersOpen(false)}
                  type="button"
                >
                  <X size={16} />
                </button>
              </div>
              {sidebar}
            </div>
          </div>
        )}

        <section className="min-w-0 flex-1">
          <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <button
              className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 lg:hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
              onClick={() => setMobileFiltersOpen(true)}
              type="button"
            >
              <SlidersHorizontal size={16} /> Filters {activeFilterCount > 0 && <span className="rounded-full bg-primary px-1.5 text-[10px] text-white">{activeFilterCount}</span>}
            </button>

            <div className="relative">
              <select
                aria-label="Sort products"
                className="w-full appearance-none rounded-full border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm font-semibold text-slate-700 outline-none focus:border-primary sm:w-auto dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                onChange={(event) => setParam('sort', event.target.value)}
                value={sort || 'newest'}
              >
                {sortOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
              <ChevronDown size={15} className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            </div>
          </div>

          {isError ? (
            <div className="flex flex-col items-center justify-center rounded-[18px] border border-dashed border-slate-200 bg-white py-20 text-center dark:border-slate-700 dark:bg-slate-900">
              <span className="text-5xl">⚠️</span>
              <p className="mt-4 font-headline text-xl font-extrabold text-slate-900 dark:text-white">Couldn&apos;t load products</p>
              <p className="mt-2 text-sm text-slate-500">Please check your connection and try again.</p>
              <button className="mt-5 rounded-full bg-primary px-6 py-3 text-sm font-bold text-white" onClick={() => productsQuery.refetch()} type="button">
                Try Again
              </button>
            </div>
          ) : isLoading ? (
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
              {Array.from({ length: 6 }).map((_, index) => (
                <div className="animate-pulse rounded-[18px] border border-slate-100 bg-white p-4 dark:border-slate-800 dark:bg-slate-900" key={index}>
                  <div className="aspect-square rounded-xl bg-slate-100 dark:bg-slate-800" />
                  <div className="mt-4 h-4 w-3/4 rounded bg-slate-100 dark:bg-slate-800" />
                  <div className="mt-2 h-3 w-1/2 rounded bg-slate-100 dark:bg-slate-800" />
                </div>
              ))}
            </div>
          ) : products.length === 0 ? (
            <div className="flex flex-col items-center justify-center rounded-[18px] border border-dashed border-slate-200 bg-white py-20 text-center dark:border-slate-700 dark:bg-slate-900">
              <span className="text-5xl">🔍</span>
              <p className="mt-4 font-headline text-xl font-extrabold text-slate-900 dark:text-white">No products found</p>
              <p className="mt-2 text-sm text-slate-500">Try adjusting your filters or search terms.</p>
              <button className="mt-5 rounded-full bg-primary px-6 py-3 text-sm font-bold text-white" onClick={clearAll} type="button">
                Clear Filters
              </button>
            </div>
          ) : (
            <>
              <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                {products.map((product, index) => (
                  <ProductCard index={index} key={product.id} onQuickView={setQuickViewProduct} product={product} />
                ))}
              </div>

              {totalPages > 1 && (
                <div className="mt-10 flex items-center justify-center gap-2">
                  <button
                    aria-label="Previous page"
                    className="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300"
                    disabled={page <= 1}
                    onClick={() => goToPage(page - 1)}
                    type="button"
                  >
                    <ChevronLeft size={16} />
                  </button>
                  {Array.from({ length: totalPages }).map((_, index) => {
                    const pageNumber = index + 1
                    const isActive = pageNumber === page
                    return (
                      <button
                        aria-label={`Go to page ${pageNumber}`}
                        aria-current={isActive ? 'page' : undefined}
                        className={`flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold transition ${
                          isActive ? 'bg-primary text-white' : 'border border-slate-200 text-slate-600 hover:border-primary hover:text-primary dark:border-slate-700 dark:text-slate-300'
                        }`}
                        key={pageNumber}
                        onClick={() => goToPage(pageNumber)}
                        type="button"
                      >
                        {pageNumber}
                      </button>
                    )
                  })}
                  <button
                    aria-label="Next page"
                    className="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300"
                    disabled={page >= totalPages}
                    onClick={() => goToPage(page + 1)}
                    type="button"
                  >
                    <ChevronRight size={16} />
                  </button>
                </div>
              )}
            </>
          )}
        </section>
      </div>

      <QuickViewModal onClose={() => setQuickViewProduct(null)} product={quickViewProduct} />
    </main>
  )
}

export default ShopPage
