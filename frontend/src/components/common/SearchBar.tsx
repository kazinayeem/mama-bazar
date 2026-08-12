import { AnimatePresence, motion } from 'framer-motion'
import { Clock, Flame, Mic, Search, X } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useGetCategoriesQuery, useGetHomepageQuery, useGetProductsQuery } from '../../store/services/commerceApi'
import { formatPrice } from '../../lib/format'

interface SearchBarProps {
  onNavigate?: () => void
}

const RECENT_KEY = 'mamabazar:recentSearches'

const loadRecent = (): string[] => {
  try {
    return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]') as string[]
  } catch {
    return []
  }
}

const SearchBar = ({ onNavigate }: SearchBarProps) => {
  const navigate = useNavigate()
  const [query, setQuery] = useState('')
  const [open, setOpen] = useState(false)
  const [recentSearches, setRecentSearches] = useState<string[]>(loadRecent)
  const containerRef = useRef<HTMLDivElement>(null)

  const categoriesQuery = useGetCategoriesQuery()
  const categories = categoriesQuery.data || []
  const { data: homepageData } = useGetHomepageQuery()
  const popularSearches = homepageData?.popularSearches || []

  const [debouncedQuery, setDebouncedQuery] = useState('')
  useEffect(() => {
    const timer = setTimeout(() => setDebouncedQuery(query.trim()), 300)
    return () => clearTimeout(timer)
  }, [query])

  const suggestionsQuery = useGetProductsQuery({ search: debouncedQuery, limit: 5 }, { skip: !debouncedQuery })
  const suggestions = suggestionsQuery.data?.data || []

  useEffect(() => {
    const onClickOutside = (event: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setOpen(false)
      }
    }
    document.addEventListener('mousedown', onClickOutside)
    return () => document.removeEventListener('mousedown', onClickOutside)
  }, [])

  const saveRecent = (term: string) => {
    setRecentSearches((prev) => {
      const next = [term, ...prev.filter((entry) => entry !== term)].slice(0, 5)
      localStorage.setItem(RECENT_KEY, JSON.stringify(next))
      return next
    })
  }

  const submit = (value?: string) => {
    const term = (value ?? query).trim()
    if (!term) return
    saveRecent(term)
    setOpen(false)
    setQuery('')
    navigate(`/shop?search=${encodeURIComponent(term)}`)
    onNavigate?.()
  }

  return (
    <div className="relative w-full" ref={containerRef}>
      <div className="flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2.5 transition focus-within:border-white/60 focus-within:ring-2 focus-within:ring-white/30">
        <Search size={16} className="shrink-0 text-white/60" />
        <input
          aria-label="Search products"
          className="w-full bg-transparent text-sm text-white placeholder:text-white/50"
          onFocus={() => setOpen(true)}
          onChange={(event) => {
            setQuery(event.target.value)
            setOpen(true)
          }}
          onKeyDown={(event) => event.key === 'Enter' && submit()}
          placeholder="Search TVs, laptops, gadgets..."
          type="search"
          value={query}
        />
        {query && (
          <button aria-label="Clear search" className="shrink-0 text-white/60 hover:text-white" onClick={() => setQuery('')} type="button">
            <X size={14} />
          </button>
        )}
        <button aria-label="Voice search" className="shrink-0 rounded-full h-11 w-11 text-white/60 transition hover:bg-white/10 hover:text-white" type="button">
          <Mic size={16} />
        </button>
      </div>

      <AnimatePresence>
        {open && (
          <motion.div
            animate={{ opacity: 1, y: 0 }}
            className="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-[18px] border border-white/10 bg-canvas-night-elevated shadow-dark-card"
            exit={{ opacity: 0, y: -6 }}
            initial={{ opacity: 0, y: -6 }}
            transition={{ duration: 0.18 }}
          >
            <div className="max-h-[22rem] overflow-y-auto p-2">
              {debouncedQuery ? (
                <>
                  <p className="px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-[0.18em] text-white/50">Suggestions</p>
                  {suggestionsQuery.isLoading ? (
                    <div className="space-y-2 p-2">
                      {Array.from({ length: 3 }).map((_, index) => (
                        <div className="h-9 animate-pulse rounded-xl bg-white/10" key={index} />
                      ))}
                    </div>
                  ) : suggestions.length > 0 ? (
                    suggestions.map((product) => (
                      <button
                        className="flex w-full items-center gap-3 rounded-full px-3 py-2 text-left text-sm text-white/85 transition hover:bg-white/10 hover:text-white"
                        key={product.id}
                        onClick={() => submit(product.title)}
                        type="button"
                      >
                        {product.images[0] && (
                          <span className="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-white/10">
                            <img alt="" className="h-full w-full object-cover" src={product.images[0]} />
                          </span>
                        )}
                        <span className="min-w-0 flex-1">
                          <span className="block truncate">{product.title}</span>
                          <span className="mt-0.5 block text-xs font-bold text-white">
                            {formatPrice(Number(product.salePrice || product.price))}
                          </span>
                        </span>
                        <Search size={13} className="shrink-0 text-white/40" />
                      </button>
                    ))
                  ) : (
                    <button
                      className="flex w-full items-center gap-2 rounded-full px-3 py-2.5 text-left text-sm text-white/85 transition hover:bg-white/10 hover:text-white"
                      onClick={() => submit()}
                      type="button"
                    >
                      <Search size={14} className="text-white/50" />
                      Search for &ldquo;{query}&rdquo;
                    </button>
                  )}
                </>
              ) : (
                <>
                  {recentSearches.length > 0 && (
                    <>
                      <p className="flex items-center gap-1.5 px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-[0.18em] text-white/50">
                        <Clock size={11} /> Recent Searches
                      </p>
                      {recentSearches.map((term) => (
                        <button
                          className="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-primary/5 hover:text-primary-foreground dark:text-slate-200"
                          key={term}
                          onClick={() => submit(term)}
                          type="button"
                        >
                          <Clock size={14} className="text-white/50" />
                          {term}
                        </button>
                      ))}
                    </>
                  )}

                  {popularSearches.length > 0 && (
                    <>
                      <p className="flex items-center gap-1.5 px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-[0.18em] text-white/50">
                        <Flame size={11} className="text-white/70" /> Popular Searches
                      </p>
                      <div className="flex flex-wrap gap-1.5 px-2 pb-2">
                        {popularSearches.slice(0, 8).map((term) => (
                          <button
                            className="rounded-full border border-white/20 px-3 py-1.5 text-xs font-semibold text-white/75 transition hover:border-white/50 hover:bg-white/10 hover:text-white"
                            key={term}
                            onClick={() => submit(term)}
                            type="button"
                          >
                            {term}
                          </button>
                        ))}
                      </div>
                    </>
                  )}

                  <p className="px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-[0.18em] text-white/50">Categories</p>
                  <div className="grid grid-cols-2 gap-1 px-1 pb-2">
                    {categories.slice(0, 6).map((category) => (
                      <button
                        className="flex items-center gap-2 rounded-full px-3 py-2 text-left text-sm text-white/85 transition hover:bg-white/10 hover:text-white"
                        key={category.slug}
                        onClick={() => {
                          setOpen(false)
                          navigate(`/shop?category=${category.slug}`)
                          onNavigate?.()
                        }}
                        type="button"
                      >
                        {category.image ? (
                          <img alt="" className="h-5 w-5 rounded-md object-cover" src={category.image} />
                        ) : (
                          <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-white/10 text-[9px] font-black text-white">
                            {category.name.slice(0, 1)}
                          </span>
                        )}
                        <span className="truncate">{category.name}</span>
                      </button>
                    ))}
                  </div>
                </>
              )}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}

export default SearchBar
