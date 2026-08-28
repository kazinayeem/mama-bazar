import { useCallback, useEffect, useMemo, useState } from 'react'
import { Box, Loader2, Package, Search, TrendingDown } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { Progress } from '@/components/ui/progress'
import { adminApi } from '@/lib/adminApi'
import { currency } from '@/lib/format'
import type { AdminProduct } from '@/types/admin'
import { SEO } from '../../components/common/SEO'

const LOW_STOCK_THRESHOLD = 10

const AdminInventoryPage = () => {
  const [products, setProducts] = useState<AdminProduct[]>([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [filter, setFilter] = useState<'all' | 'low' | 'out'>('all')
  const [page, setPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [totalPages, setTotalPages] = useState(1)
  const [updating, setUpdating] = useState<number | null>(null)

  const load = useCallback(async (params: { page?: number; search?: string; filter?: string; isInitial?: boolean } = {}) => {
    if (params.isInitial) setLoading(true)
    try {
      const result = await adminApi.getProducts({
        page: params.page ?? page,
        limit: 20,
        search: params.search ?? search,
        sort: 'stock_asc',
      })
      setProducts(result.data)
      setTotal(result.total)
      setTotalPages(result.totalPages)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load inventory')
    } finally {
      setLoading(false)
    }
  }, [page, search])

  useEffect(() => {
    load({ isInitial: true })
  }, [load])

  const visible = useMemo(() => {
    let list = products
    if (filter === 'low') list = list.filter((p) => p.stock > 0 && p.stock <= LOW_STOCK_THRESHOLD)
    if (filter === 'out') list = list.filter((p) => p.stock <= 0)
    return list
  }, [products, filter])

  const updateStock = async (product: AdminProduct, delta: number) => {
    const next = Math.max(0, product.stock + delta)
    setUpdating(product.id)
    try {
      await adminApi.updateProduct(product.id, { stock: next })
      setProducts((prev) => prev.map((p) => (p.id === product.id ? { ...p, stock: next } : p)))
      toast.success(`Stock updated to ${next}`)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Update failed')
    } finally {
      setUpdating(null)
    }
  }

  const stats = useMemo(() => {
    const all = products
    const out = all.filter((p) => p.stock <= 0).length
    const low = all.filter((p) => p.stock > 0 && p.stock <= LOW_STOCK_THRESHOLD).length
    const healthy = all.filter((p) => p.stock > LOW_STOCK_THRESHOLD).length
    const totalStock = all.reduce((sum, p) => sum + p.stock, 0)
    return { out, low, healthy, totalStock }
  }, [products])

  const applySearch = (value: string) => {
    setSearch(value)
    setPage(1)
    setLoading(true)
    adminApi
      .getProducts({ page: 1, limit: 20, search: value, sort: 'stock_asc' })
      .then((r) => {
        setProducts(r.data)
        setTotal(r.total)
        setTotalPages(r.totalPages)
      })
      .catch((err) => toast.error(err.message))
      .finally(() => setLoading(false))
  }

  return (
    <AdminLayout>
      <SEO title="Inventory Management" description="Track and manage product inventory levels." url="/admin/inventory" />
      <div className="space-y-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Inventory</h1>
          <p className="text-sm text-muted-foreground">Monitor and adjust stock levels</p>
        </div>

        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <Card>
            <CardContent className="flex items-center gap-3 p-4">
              <div className="rounded-lg bg-success/15 p-2.5 text-success">
                <Box className="h-5 w-5" />
              </div>
              <div>
                <p className="text-2xl font-bold">{stats.totalStock}</p>
                <p className="text-xs text-muted-foreground">Total units</p>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="flex items-center gap-3 p-4">
              <div className="rounded-lg bg-primary/10 p-2.5 text-primary">
                <Package className="h-5 w-5" />
              </div>
              <div>
                <p className="text-2xl font-bold">{stats.healthy}</p>
                <p className="text-xs text-muted-foreground">Well stocked</p>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="flex items-center gap-3 p-4">
              <div className="rounded-lg bg-warning/15 p-2.5 text-warning">
                <TrendingDown className="h-5 w-5" />
              </div>
              <div>
                <p className="text-2xl font-bold">{stats.low}</p>
                <p className="text-xs text-muted-foreground">Low stock</p>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="flex items-center gap-3 p-4">
              <div className="rounded-lg bg-destructive/10 p-2.5 text-destructive">
                <Box className="h-5 w-5" />
              </div>
              <div>
                <p className="text-2xl font-bold">{stats.out}</p>
                <p className="text-xs text-muted-foreground">Out of stock</p>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-base">Stock Levels</CardTitle>
          </CardHeader>
          <CardContent className="p-3">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Search by product or SKU..."
                  value={search}
                  onChange={(e) => applySearch(e.target.value)}
                  className="pl-9"
                />
              </div>
              <Select value={filter} onValueChange={(v) => setFilter(v as typeof filter)}>
                <SelectTrigger className="w-40">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All products</SelectItem>
                  <SelectItem value="low">Low stock (≤{LOW_STOCK_THRESHOLD})</SelectItem>
                  <SelectItem value="out">Out of stock</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Product</TableHead>
                  <TableHead>SKU</TableHead>
                  <TableHead className="text-right">Price</TableHead>
                  <TableHead className="w-40">Stock Level</TableHead>
                  <TableHead className="text-center">Adjust</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading && products.length === 0 ? (
                  Array.from({ length: 8 }).map((_, i) => (
                    <TableRow key={i}>
                      <TableCell colSpan={5}>
                        <Skeleton className="h-10 w-full" />
                      </TableCell>
                    </TableRow>
                  ))
                ) : visible.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="py-12 text-center text-sm text-muted-foreground">
                      No products match your filters
                    </TableCell>
                  </TableRow>
                ) : (
                  visible.map((product) => {
                    const pct = Math.min(100, (product.stock / 50) * 100)
                    return (
                      <TableRow key={product.id}>
                        <TableCell>
                          <div className="flex items-center gap-3">
                            {product.images[0] ? (
                              <img src={product.images[0]} alt="" className="h-9 w-9 rounded-md object-cover" />
                            ) : (
                              <div className="flex h-9 w-9 items-center justify-center rounded-md bg-muted">
                                <Package className="h-4 w-4 text-muted-foreground" />
                              </div>
                            )}
                            <div>
                              <p className="max-w-52 truncate text-sm font-medium">{product.title}</p>
                              <p className="text-xs text-muted-foreground">{product.sku || '—'}</p>
                            </div>
                          </div>
                        </TableCell>
                        <TableCell className="text-sm text-muted-foreground">{product.sku || '—'}</TableCell>
                        <TableCell className="text-right text-sm font-medium">{currency(product.price)}</TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <Progress value={pct} className="h-2 flex-1" />
                            <Badge variant={product.stock <= 0 ? 'destructive' : product.stock <= 10 ? 'warning' : 'success'}>
                              {product.stock}
                            </Badge>
                          </div>
                        </TableCell>
                        <TableCell className="text-center">
                          <div className="flex items-center justify-center gap-1">
                            <Button
                              variant="outline"
                              size="sm"
                              disabled={updating === product.id || product.stock <= 0}
                              onClick={() => updateStock(product, -1)}
                            >
                              −
                            </Button>
                            <Button
                              variant="outline"
                              size="sm"
                              disabled={updating === product.id}
                              onClick={() => updateStock(product, 1)}
                            >
                              +
                            </Button>
                            {updating === product.id && <Loader2 className="h-4 w-4 animate-spin" />}
                          </div>
                        </TableCell>
                      </TableRow>
                    )
                  })
                )}
              </TableBody>
            </Table>

            {totalPages > 1 && (
              <div className="flex items-center justify-between border-t p-3">
                <span className="text-xs text-muted-foreground">Page {page} of {totalPages} · {total} products</span>
                <div className="flex items-center gap-2">
                  <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => { const p = page - 1; setPage(p); load({ page: p }) }}>
                    Previous
                  </Button>
                  <Button variant="outline" size="sm" disabled={page >= totalPages} onClick={() => { const p = page + 1; setPage(p); load({ page: p }) }}>
                    Next
                  </Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  )
}

export default AdminInventoryPage
