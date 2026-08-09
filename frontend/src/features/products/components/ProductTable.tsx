import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  Copy,
  Eye,
  MoreHorizontal,
  Package,
  Pencil,
  Trash2,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { Skeleton } from '@/components/ui/skeleton'
import { Switch } from '@/components/ui/switch'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { formatDate } from '../lib/formatDate'
import { currency } from '@/lib/format'
import type { AdminProduct } from '@/types/admin'

const STATUS_LABELS: Record<string, string> = {
  draft: 'Draft',
  published: 'Published',
  hidden: 'Hidden',
  archived: 'Archived',
  active: 'Active',
  inactive: 'Inactive',
  coming_soon: 'Coming soon',
  out_of_stock: 'Out of stock',
}

const STATUS_VARIANTS: Record<string, 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning' | 'muted'> = {
  published: 'success',
  active: 'success',
  draft: 'secondary',
  hidden: 'warning',
  archived: 'muted',
  inactive: 'muted',
}

interface ProductTableProps {
  products: AdminProduct[]
  loading: boolean
  error?: string
  onRetry?: () => void
  selected: Set<number>
  onToggleSelected: (id: number) => void
  onToggleAll: (checked: boolean) => void
  allSelected: boolean
  someSelected: boolean
  onView: (product: AdminProduct) => void
  onEdit: (product: AdminProduct) => void
  onDuplicate: (product: AdminProduct) => void
  onDelete: (product: AdminProduct) => void
  onToggleFeatured: (product: AdminProduct, featured: boolean) => void
}

const SkeletonRow = () => (
  <TableRow>
    <TableCell colSpan={13}>
      <Skeleton className="h-12 w-full" />
    </TableCell>
  </TableRow>
)

const ProductTable = ({
  products,
  loading,
  error,
  onRetry,
  selected,
  onToggleSelected,
  onToggleAll,
  allSelected,
  someSelected,
  onView,
  onEdit,
  onDuplicate,
  onDelete,
  onToggleFeatured,
}: ProductTableProps) => {
  const navigate = useNavigate()
  const [pendingFeatured, setPendingFeatured] = useState<number | null>(null)

  const statusLabel = (p: AdminProduct) => p.productStatus || p.status
  const badgeVariant = (p: AdminProduct) => STATUS_VARIANTS[statusLabel(p)] || 'secondary'
  const brandName = (p: AdminProduct) => p.brandInfo?.name || p.brand || '—'

  return (
    <div className="overflow-hidden rounded-lg border">
      <div className="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-10">
                <Checkbox
                  checked={someSelected ? 'indeterminate' : allSelected}
                  onCheckedChange={(v) => onToggleAll(!!v)}
                  aria-label="Select all products"
                />
              </TableHead>
              <TableHead className="w-14">Image</TableHead>
              <TableHead className="min-w-56">Product Name</TableHead>
              <TableHead className="w-28">SKU</TableHead>
              <TableHead className="w-32">Brand</TableHead>
              <TableHead className="w-36">Category</TableHead>
              <TableHead className="w-28 text-right">Price</TableHead>
              <TableHead className="w-28 text-right">Sale Price</TableHead>
              <TableHead className="w-24 text-center">Stock</TableHead>
              <TableHead className="w-28">Status</TableHead>
              <TableHead className="w-20 text-center">Featured</TableHead>
              <TableHead className="w-28">Created</TableHead>
              <TableHead className="w-16 text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              Array.from({ length: 8 }).map((_, i) => <SkeletonRow key={i} />)
            ) : error ? (
              <TableRow>
                <TableCell colSpan={13} className="py-12 text-center">
                  <p className="text-sm text-destructive">{error}</p>
                  {onRetry && (
                    <Button variant="outline" size="sm" className="mt-3" onClick={onRetry}>
                      Retry
                    </Button>
                  )}
                </TableCell>
              </TableRow>
            ) : products.length === 0 ? (
              <TableRow>
                <TableCell colSpan={13} className="py-14 text-center">
                  <Package className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                  <p className="text-sm font-medium">No products found</p>
                  <p className="mt-1 text-xs text-muted-foreground">Try adjusting your search or filters.</p>
                </TableCell>
              </TableRow>
            ) : (
              products.map((product) => {
                const isArchived = product.productStatus === 'archived'
                return (
                  <TableRow key={product.id} className={isArchived ? 'opacity-50' : ''}>
                    <TableCell>
                      <Checkbox
                        checked={selected.has(product.id)}
                        onCheckedChange={() => onToggleSelected(product.id)}
                        aria-label={`Select ${product.title}`}
                      />
                    </TableCell>
                    <TableCell>
                      {product.images[0] ? (
                        <img
                          src={product.images[0]}
                          alt=""
                          className="h-11 w-11 rounded-md border object-cover"
                          loading="lazy"
                        />
                      ) : (
                        <div className="flex h-11 w-11 items-center justify-center rounded-md border bg-muted">
                          <Package className="h-4 w-4 text-muted-foreground" />
                        </div>
                      )}
                    </TableCell>
                    <TableCell>
                      <button type="button" onClick={() => onView(product)} className="block text-left">
                        <p className="line-clamp-2 max-w-56 text-sm font-medium hover:text-primary">{product.title}</p>
                        <div className="mt-0.5 flex flex-wrap gap-1">
                          {product.isFeatured && <Badge variant="secondary" className="text-[9px]">Featured</Badge>}
                          {product.isTrending && <Badge variant="secondary" className="text-[9px]">Trending</Badge>}
                          {product.isFlashSale && <Badge variant="warning" className="text-[9px]">Flash</Badge>}
                          {product.isHotDeal && <Badge variant="warning" className="text-[9px]">Hot Deal</Badge>}
                        </div>
                      </button>
                    </TableCell>
                    <TableCell className="font-mono text-xs text-muted-foreground">{product.sku || '—'}</TableCell>
                    <TableCell className="text-sm">{brandName(product)}</TableCell>
                    <TableCell className="text-sm">{product.category?.name || '—'}</TableCell>
                    <TableCell className="text-right">
                      <p className="text-sm font-semibold">{currency(product.price)}</p>
                      {Number(product.discount) > 0 && (
                        <p className="text-xs text-muted-foreground">-{product.discount}%</p>
                      )}
                    </TableCell>
                    <TableCell className="text-right">
                      {Number(product.salePrice) > 0 ? (
                        <p className="text-sm font-medium text-success">{currency(product.salePrice!)}</p>
                      ) : (
                        <span className="text-xs text-muted-foreground">—</span>
                      )}
                    </TableCell>
                    <TableCell className="text-center">
                      <Badge
                        variant={
                          product.stock <= 0
                            ? 'destructive'
                            : product.stock <= (product.lowStockAlert || 10)
                              ? 'warning'
                              : 'secondary'
                        }
                      >
                        {product.stock}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant={badgeVariant(product)}>{STATUS_LABELS[statusLabel(product)] || statusLabel(product)}</Badge>
                    </TableCell>
                    <TableCell className="text-center">
                      <Switch
                        checked={product.isFeatured}
                        disabled={pendingFeatured === product.id}
                        onCheckedChange={(v) => {
                          setPendingFeatured(product.id)
                          onToggleFeatured(product, v)
                          setTimeout(() => setPendingFeatured(null), 800)
                        }}
                        aria-label={`Toggle featured for ${product.title}`}
                      />
                    </TableCell>
                    <TableCell className="text-xs text-muted-foreground">{formatDate(product.createdAt)}</TableCell>
                    <TableCell className="text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon" aria-label="Product actions">
                            <MoreHorizontal className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                          <DropdownMenuItem onClick={() => onView(product)} className="cursor-pointer">
                            <Eye className="mr-2 h-4 w-4" /> View
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => onEdit(product)} className="cursor-pointer">
                            <Pencil className="mr-2 h-4 w-4" /> Edit
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => onDuplicate(product)} className="cursor-pointer">
                            <Copy className="mr-2 h-4 w-4" /> Duplicate
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
                          <DropdownMenuItem
                            onClick={() => navigate(`/products/${product.slug}`)}
                            className="cursor-pointer"
                          >
                            <Eye className="mr-2 h-4 w-4" /> View on storefront
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
                          <DropdownMenuItem
                            onClick={() => onDelete(product)}
                            className="cursor-pointer text-destructive focus:text-destructive"
                          >
                            <Trash2 className="mr-2 h-4 w-4" /> Delete
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                )
              })
            )}
          </TableBody>
        </Table>
      </div>
    </div>
  )
}

export default ProductTable
