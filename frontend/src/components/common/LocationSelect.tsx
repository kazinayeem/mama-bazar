import { useEffect, useMemo, useRef, useState } from 'react'
import { Check, ChevronDown, Search } from 'lucide-react'

interface LocationSelectProps {
  label: string
  value: string
  options: string[]
  onChange: (value: string) => void
  placeholder?: string
  searchPlaceholder?: string
  disabled?: boolean
}

// Searchable, English-only combobox used for Bangladesh location selection.
const LocationSelect = ({
  label,
  value,
  options,
  onChange,
  placeholder = 'Select',
  searchPlaceholder = 'Search...',
  disabled = false,
}: LocationSelectProps) => {
  const [open, setOpen] = useState(false)
  const [query, setQuery] = useState('')
  const [activeIndex, setActiveIndex] = useState(0)
  const rootRef = useRef<HTMLDivElement>(null)
  const inputRef = useRef<HTMLInputElement>(null)

  // Close when clicking/tapping outside
  useEffect(() => {
    const handlePointerDown = (event: MouseEvent | TouchEvent) => {
      if (rootRef.current && !rootRef.current.contains(event.target as Node)) {
        setOpen(false)
      }
    }
    document.addEventListener('mousedown', handlePointerDown)
    document.addEventListener('touchstart', handlePointerDown)
    return () => {
      document.removeEventListener('mousedown', handlePointerDown)
      document.removeEventListener('touchstart', handlePointerDown)
    }
  }, [])

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase()
    if (!q) return options
    return options.filter((option) => option.toLowerCase().includes(q))
  }, [options, query])

  // Allow keeping a typed/legacy value that is not present in the dataset
  const showUseQuery = query.trim() !== '' && !options.some((option) => option.toLowerCase() === query.trim().toLowerCase())
  const list = useMemo(() => {
    const base = showUseQuery ? [query.trim()] : []
    return [...base, ...filtered]
  }, [filtered, query, showUseQuery])

  const select = (item: string) => {
    onChange(item)
    setQuery('')
    setOpen(false)
  }

  const openList = () => {
    if (disabled) return
    setQuery(value)
    setActiveIndex(0)
    setOpen(true)
  }

  useEffect(() => {
    if (!open) return
    const id = window.setTimeout(() => inputRef.current?.focus(), 0)
    return () => window.clearTimeout(id)
  }, [open])

  const handleKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'Escape') {
      setOpen(false)
      return
    }
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      setActiveIndex((index) => (index + 1) % Math.max(list.length, 1))
      return
    }
    if (event.key === 'ArrowUp') {
      event.preventDefault()
      setActiveIndex((index) => (index - 1 + Math.max(list.length, 1)) % Math.max(list.length, 1))
      return
    }
    if (event.key === 'Enter') {
      event.preventDefault()
      const item = list[activeIndex]
      if (item) select(item)
    }
  }

  return (
    <div ref={rootRef} className="relative">
      <label className="mb-1.5 block text-sm font-medium text-slate-700">{label}</label>
      <button
        aria-expanded={open}
        aria-haspopup="listbox"
        className={`flex w-full items-center justify-between gap-2 rounded-lg border bg-white px-3.5 py-2.5 text-left text-sm transition focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100 ${
          disabled
            ? 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400'
            : value
              ? 'border-slate-200 text-slate-900 hover:border-brand-green-500'
              : 'border-slate-200 text-slate-400 hover:border-brand-green-500'
        }`}
        disabled={disabled}
        onClick={openList}
        type="button"
      >
        <span className="truncate">{value || placeholder}</span>
        <ChevronDown size={16} className={`shrink-0 text-slate-400 transition-transform duration-150 ${open ? 'rotate-180' : ''}`} />
      </button>

      {open && (
        <div className="absolute left-0 right-0 z-30 mt-1.5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card">
          <div className="flex items-center gap-2 border-b border-slate-100 px-3">
            <Search size={14} className="shrink-0 text-slate-400" />
            <input
              aria-label={`Search ${label.toLowerCase()}`}
              className="w-full border-0 bg-transparent py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none"
              onChange={(event) => {
                setQuery(event.target.value)
                setActiveIndex(0)
              }}
              onKeyDown={handleKeyDown}
              placeholder={searchPlaceholder}
              ref={inputRef}
              value={query}
            />
          </div>
          <ul aria-label={label} className="max-h-56 overflow-y-auto py-1" role="listbox">
            {list.length > 0 ? (
              list.map((item, index) => (
                <li aria-selected={item === value} key={`${item}-${index}`} role="option">
                  <button
                    className={`flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm transition ${
                      index === activeIndex ? 'bg-brand-green-50' : ''
                    } ${item === value ? 'font-semibold text-brand-green-700' : 'text-slate-700'}`}
                    onClick={() => select(item)}
                    onMouseEnter={() => setActiveIndex(index)}
                    type="button"
                  >
                    <span className="truncate">{item}</span>
                    {item === value && <Check size={15} className="shrink-0 text-brand-green-600" />}
                  </button>
                </li>
              ))
            ) : (
              <li className="px-3 py-2 text-sm text-slate-400">No results found</li>
            )}
          </ul>
        </div>
      )}
    </div>
  )
}

export default LocationSelect