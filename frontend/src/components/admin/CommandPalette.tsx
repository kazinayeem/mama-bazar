import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { CornerDownLeft, Search } from 'lucide-react'
import { Dialog, DialogContent } from '@/components/ui/dialog'
import { allAdminNavItems } from './adminNav'
import { cn } from '@/lib/utils'

interface CommandPaletteProps {
  open: boolean
  onOpenChange: (open: boolean) => void
}

const CommandPalette = ({ open, onOpenChange }: CommandPaletteProps) => {
  const navigate = useNavigate()
  const [query, setQuery] = useState('')
  const [selected, setSelected] = useState(0)
  const inputRef = useRef<HTMLInputElement>(null)

  const results = useMemo(() => {
    const q = query.trim().toLowerCase()
    const nav = allAdminNavItems
      .filter((item) => !q || item.label.toLowerCase().includes(q) || item.section.toLowerCase().includes(q))
      .map((item) => ({ label: item.label, section: item.section, href: item.href }))
    return nav
  }, [query])

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onOpenChange(false)
      if (e.key === 'ArrowDown') {
        e.preventDefault()
        setSelected((s) => Math.min(s + 1, results.length - 1))
      }
      if (e.key === 'ArrowUp') {
        e.preventDefault()
        setSelected((s) => Math.max(s - 1, 0))
      }
      if (e.key === 'Enter' && results[selected]) {
        navigate(results[selected].href)
        onOpenChange(false)
      }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [results, selected, navigate, onOpenChange])

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        className="top-[15%] max-w-lg translate-y-0 overflow-hidden p-0"
        showCloseButton={false}
        onOpenAutoFocus={(e) => {
          e.preventDefault()
          setQuery('')
          setSelected(0)
          setTimeout(() => inputRef.current?.focus(), 0)
        }}
      >
        <div className="flex items-center gap-3 border-b px-4">
          <Search className="h-4 w-4 shrink-0 text-muted-foreground" />
          <input
            ref={inputRef}
            value={query}
            onChange={(e) => {
              setQuery(e.target.value)
              setSelected(0)
            }}
            placeholder="Search pages and actions..."
            className="h-12 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
          />
          <kbd className="rounded border bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground">ESC</kbd>
        </div>

        <div className="max-h-80 overflow-y-auto p-2">
          {results.length === 0 && (
            <p className="px-3 py-6 text-center text-sm text-muted-foreground">No results found</p>
          )}
          {results.map((result, index) => (
            <button
              key={result.href}
              type="button"
              onMouseEnter={() => setSelected(index)}
              onClick={() => {
                navigate(result.href)
                onOpenChange(false)
              }}
              className={cn(
                'flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm transition-colors',
                index === selected ? 'bg-primary/10 text-primary' : 'text-foreground',
              )}
            >
              <span className="flex-1">
                <span className="font-medium">{result.label}</span>
                <span className="ml-2 text-xs text-muted-foreground">{result.section}</span>
              </span>
              {index === selected && <CornerDownLeft className="h-3.5 w-3.5 text-muted-foreground" />}
            </button>
          ))}
        </div>
      </DialogContent>
    </Dialog>
  )
}

export default CommandPalette
