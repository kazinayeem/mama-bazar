import { Link, useLocation } from 'react-router-dom'
import { ChevronDown, Store } from 'lucide-react'
import { useState } from 'react'
import { adminNavSections } from './adminNav'
import { cn } from '@/lib/utils'
import { Separator } from '@/components/ui/separator'

interface SidebarProps {
  collapsed: boolean
  onNavigate?: () => void
}

const Sidebar = ({ collapsed, onNavigate }: SidebarProps) => {
  const location = useLocation()
  const [openSections, setOpenSections] = useState<Record<string, boolean>>({})

  const isActive = (href: string) => {
    if (href === '/admin/dashboard') return location.pathname === href
    return location.pathname.startsWith(href)
  }

  const toggleSection = (label: string) => {
    setOpenSections((prev) => ({ ...prev, [label]: !prev[label] }))
  }

  const sectionOpen = (label: string) => {
    if (openSections[label] !== undefined) return openSections[label]
    return !collapsed
  }

  return (
    <div className="flex h-full flex-col overflow-y-auto">
      <div className={cn('flex h-16 items-center gap-3 border-b px-4', collapsed && 'justify-center px-0')}>
        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary text-white">
          <Store className="h-5 w-5" />
        </div>
        {!collapsed && (
          <div className="min-w-0">
            <p className="truncate text-sm font-bold leading-tight">MamaBazar</p>
            <p className="truncate text-[11px] text-muted-foreground">Admin Panel</p>
          </div>
        )}
      </div>

      <nav className="flex-1 space-y-4 px-3 py-4">
        {adminNavSections.map((section) => {
          const activeInSection = section.items.some((item) => isActive(item.href))
          const isOpen = sectionOpen(section.label)
          return (
            <div key={section.label}>
              <button
                type="button"
                onClick={() => toggleSection(section.label)}
                className={cn(
                  'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground transition-colors hover:text-foreground',
                  collapsed && 'justify-center',
                  activeInSection && !collapsed && 'text-primary',
                )}
              >
                {!collapsed && <span className="flex-1 text-left">{section.label}</span>}
                {!collapsed && (
                  <ChevronDown className={cn('h-3 w-3 transition-transform', isOpen && 'rotate-180')} />
                )}
              </button>

              {isOpen && (
                <div className="mt-1 space-y-1">
                  {section.items.map((item) => {
                    const active = isActive(item.href)
                    return (
                      <Link
                        key={item.href}
                        to={item.href}
                        onClick={onNavigate}
                        title={collapsed ? item.label : undefined}
                        className={cn(
                          'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                          collapsed && 'justify-center px-0',
                          active
                            ? 'bg-primary/10 text-primary'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        )}
                      >
                        <item.icon className={cn('h-[18px] w-[18px] shrink-0', active && 'text-primary')} />
                        {!collapsed && <span className="truncate">{item.label}</span>}
                        {item.badge !== undefined && !collapsed && (
                          <span className="ml-auto rounded-full bg-primary/15 px-2 py-0.5 text-[10px] font-bold text-primary">
                            {item.badge}
                          </span>
                        )}
                      </Link>
                    )
                  })}
                </div>
              )}
            </div>
          )
        })}
      </nav>

      <div className="px-3 pb-4">
        <Separator className="mb-4" />
        <div className={cn('flex items-center gap-3 rounded-lg bg-muted/60 px-3 py-2', collapsed && 'justify-center px-0')}>
          <span className="text-xs font-medium text-muted-foreground">
            {collapsed ? 'v1.0' : 'MamaBazar Admin v1.0'}
          </span>
        </div>
      </div>
    </div>
  )
}

export default Sidebar
