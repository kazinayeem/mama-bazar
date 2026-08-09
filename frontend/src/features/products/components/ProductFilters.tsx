import { CalendarRange, RotateCcw, Search } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type { Category } from '@/types'
import type { Brand, Collection, Supplier, Vendor } from '@/types/admin'
import { SORT_OPTIONS, STATUS_OPTIONS, STOCK_FILTER_OPTIONS, LABEL_OPTIONS, isFiltered, type ListFilters } from '../lib/types'

interface ReferenceData {
  categories: Category[]
  brands: Brand[]
  suppliers: Supplier[]
  vendors: Vendor[]
  collections: Collection[]
  loading: boolean
}

interface ProductFiltersProps {
  filters: ListFilters
  onChange: (patch: Partial<ListFilters>) => void
  onClear: () => void
  reference: ReferenceData
}

const FilterSelect = ({
  value,
  onValueChange,
  placeholder,
  options,
  className,
  disabled,
}: {
  value: string
  onValueChange: (value: string) => void
  placeholder: string
  options: Array<{ value: string; label: string }>
  className?: string
  disabled?: boolean
}) => (
  <Select value={value} onValueChange={onValueChange} disabled={disabled}>
    <SelectTrigger className={className}>
      <SelectValue placeholder={placeholder} />
    </SelectTrigger>
    <SelectContent>
      <SelectItem value="all">All</SelectItem>
      {options.map((o) => (
        <SelectItem key={o.value} value={o.value}>
          {o.label}
        </SelectItem>
      ))}
    </SelectContent>
  </Select>
)

const ProductFilters = ({ filters, onChange, onClear, reference }: ProductFiltersProps) => {
  const refOptions = (items: Array<{ id: number; name: string; slug?: string }>) =>
    items.map((i) => ({ value: i.slug || String(i.id), label: i.name }))

  return (
    <div className="space-y-3">
      <div className="flex flex-col gap-2 lg:flex-row lg:items-center">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search by name, SKU, barcode, brand…"
            value={filters.search}
            onChange={(e) => onChange({ search: e.target.value, page: 1 })}
            className="pl-9"
          />
        </div>
        <Select value={filters.sort} onValueChange={(v) => onChange({ sort: v as ListFilters['sort'], page: 1 })}>
          <SelectTrigger className="w-full lg:w-48">
            <SelectValue placeholder="Sort" />
          </SelectTrigger>
          <SelectContent>
            {SORT_OPTIONS.map((o) => (
              <SelectItem key={o.value} value={o.value}>
                {o.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <FilterSelect
          value={filters.category}
          onValueChange={(v) => onChange({ category: v === 'all' ? '' : v, page: 1 })}
          placeholder="Category"
          options={refOptions(reference.categories)}
          disabled={reference.loading}
          className="w-full sm:w-40"
        />
        <FilterSelect
          value={filters.brand}
          onValueChange={(v) => onChange({ brand: v === 'all' ? '' : v, page: 1 })}
          placeholder="Brand"
          options={refOptions(reference.brands)}
          disabled={reference.loading}
          className="w-full sm:w-36"
        />
        <FilterSelect
          value={filters.supplier}
          onValueChange={(v) => onChange({ supplier: v === 'all' ? '' : v, page: 1 })}
          placeholder="Supplier"
          options={refOptions(reference.suppliers)}
          disabled={reference.loading}
          className="w-full sm:w-36"
        />
        <FilterSelect
          value={filters.vendor}
          onValueChange={(v) => onChange({ vendor: v === 'all' ? '' : v, page: 1 })}
          placeholder="Vendor"
          options={refOptions(reference.vendors)}
          disabled={reference.loading}
          className="w-full sm:w-32"
        />
        <FilterSelect
          value={filters.collection}
          onValueChange={(v) => onChange({ collection: v === 'all' ? '' : v, page: 1 })}
          placeholder="Collection"
          options={refOptions(reference.collections)}
          disabled={reference.loading}
          className="w-full sm:w-36"
        />
        <FilterSelect
          value={filters.stock}
          onValueChange={(v) => onChange({ stock: v === 'all' ? '' : v, page: 1 })}
          placeholder="Stock"
          options={STOCK_FILTER_OPTIONS}
          className="w-full sm:w-32"
        />
        <FilterSelect
          value={filters.productStatus}
          onValueChange={(v) => onChange({ productStatus: v === 'all' ? '' : v, page: 1 })}
          placeholder="Status"
          options={STATUS_OPTIONS}
          className="w-full sm:w-32"
        />
        <FilterSelect
          value={filters.label}
          onValueChange={(v) => onChange({ label: v === 'all' ? '' : v, page: 1 })}
          placeholder="Label"
          options={LABEL_OPTIONS}
          className="w-full sm:w-36"
        />

        <div className="flex items-center gap-2 rounded-md border px-2 py-1">
          <Input
            type="number"
            placeholder="Min ৳"
            value={filters.minPrice}
            onChange={(e) => onChange({ minPrice: e.target.value, page: 1 })}
            className="h-8 w-24 border-0 px-1 shadow-none focus-visible:ring-0"
          />
          <span className="text-muted-foreground">–</span>
          <Input
            type="number"
            placeholder="Max ৳"
            value={filters.maxPrice}
            onChange={(e) => onChange({ maxPrice: e.target.value, page: 1 })}
            className="h-8 w-24 border-0 px-1 shadow-none focus-visible:ring-0"
          />
        </div>

        <div className="flex items-center gap-1.5 rounded-md border px-2 py-1">
          <CalendarRange className="h-4 w-4 shrink-0 text-muted-foreground" />
          <Input
            type="date"
            value={filters.dateFrom}
            onChange={(e) => onChange({ dateFrom: e.target.value, page: 1 })}
            className="h-8 w-32 border-0 px-1 shadow-none focus-visible:ring-0"
          />
          <span className="text-muted-foreground">–</span>
          <Input
            type="date"
            value={filters.dateTo}
            onChange={(e) => onChange({ dateTo: e.target.value, page: 1 })}
            className="h-8 w-32 border-0 px-1 shadow-none focus-visible:ring-0"
          />
        </div>

        {isFiltered(filters) && (
          <Button variant="ghost" size="sm" onClick={onClear} className="text-muted-foreground">
            <RotateCcw className="mr-1 h-3.5 w-3.5" /> Clear
          </Button>
        )}
      </div>
    </div>
  )
}

export default ProductFilters
