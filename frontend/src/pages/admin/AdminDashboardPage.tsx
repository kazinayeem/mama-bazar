import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  Area,
  AreaChart,
  CartesianGrid,
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import {
  ArrowDownRight,
  ArrowUpRight,
  Box,
  CircleDollarSign,
  Package,
  ShoppingCart,
  TrendingUp,
  Users,
  Wallet,
} from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { adminApi } from '@/lib/adminApi'
import { currency } from '@/lib/format'
import type { DashboardData } from '@/types/admin'
import { SEO } from '../../components/common/SEO'

const STATUS_COLORS: Record<string, string> = {
  pending: 'bg-warning/15 text-warning',
  confirmed: 'bg-primary/10 text-primary',
  processing: 'bg-primary/10 text-primary',
  packed: 'bg-accent/10 text-accent',
  shipped: 'bg-primary/10 text-primary',
  out_for_delivery: 'bg-accent/10 text-accent',
  delivered: 'bg-success/15 text-success',
  cancelled: 'bg-destructive/10 text-destructive',
}

const STATUS_LABELS: Record<string, string> = {
  pending: 'Pending',
  confirmed: 'Confirmed',
  processing: 'Processing',
  packed: 'Packed',
  shipped: 'Shipped',
  out_for_delivery: 'Out for delivery',
  delivered: 'Delivered',
  cancelled: 'Cancelled',
}

const CHART_COLORS = ['#2563EB', '#F97316', '#22C55E', '#8B5CF6', '#EC4899', '#14B8A6', '#F59E0B']

const AdminDashboardPage = () => {
  const [range, setRange] = useState('30')
  const [data, setData] = useState<DashboardData | null>(null)
  const [loading, setLoading] = useState(true)

  const load = useCallback(async (r: string) => {
    setLoading(true)
    try {
      const result = await adminApi.getDashboard(r)
      setData(result)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load dashboard')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load(range)
  }, [range, load])

  const kpis = useMemo(() => {
    if (!data) return []
    const k = data.kpis
    return [
      {
        label: 'Total Revenue',
        value: currency(k.totalRevenue),
        sub: `${currency(k.periodRevenue)} this period`,
        trend: '+12.4%',
        trendUp: true,
        icon: Wallet,
      },
      {
        label: 'Total Orders',
        value: String(k.totalOrders),
        sub: `${k.periodOrders} in last ${range} days`,
        trend: '+8.1%',
        trendUp: true,
        icon: ShoppingCart,
      },
      {
        label: 'Avg Order Value',
        value: currency(k.avgOrderValue),
        sub: 'Per delivered order',
        trend: '-2.3%',
        trendUp: false,
        icon: CircleDollarSign,
      },
      {
        label: 'Customers',
        value: String(k.totalCustomers),
        sub: 'Registered accounts',
        trend: '+5.7%',
        trendUp: true,
        icon: Users,
      },
      {
        label: 'Products',
        value: String(k.totalProducts),
        sub: `${k.outOfStock} out of stock`,
        trend: k.outOfStock > 0 ? 'Attention needed' : 'Healthy',
        trendUp: k.outOfStock === 0,
        icon: Package,
      },
      {
        label: "Today's Orders",
        value: String(k.todayOrders),
        sub: 'Orders placed today',
        trend: 'Live',
        trendUp: true,
        icon: Box,
      },
    ]
  }, [data, range])

  const statusData = useMemo(() => {
    if (!data) return []
    return Object.entries(data.statusBreakdown)
      .map(([key, value]) => ({ name: STATUS_LABELS[key] || key, value }))
      .filter((d) => d.value > 0)
  }, [data])

  const revenueChartData = useMemo(() => {
    if (!data) return []
    return data.revenueChart.map((d) => ({
      ...d,
      label: new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
    }))
  }, [data])

  return (
    <AdminLayout>
      <SEO title="Admin Dashboard" description="Mama Bazar admin dashboard. View analytics, orders, and manage your store." url="/admin/dashboard" />
      <div className="space-y-6">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Dashboard</h1>
            <p className="text-sm text-muted-foreground">Business overview and performance metrics</p>
          </div>
          <div className="flex items-center gap-2">
            <Select value={range} onValueChange={setRange}>
              <SelectTrigger className="w-36">
                <SelectValue placeholder="Range" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="7">Last 7 days</SelectItem>
                <SelectItem value="30">Last 30 days</SelectItem>
                <SelectItem value="365">Last 365 days</SelectItem>
              </SelectContent>
            </Select>
            <Button variant="outline" onClick={() => load(range)}>Refresh</Button>
          </div>
        </div>

        {/* KPI cards */}
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {loading
            ? Array.from({ length: 6 }).map((_, i) => (
                <Card key={i}>
                  <CardContent className="p-5">
                    <Skeleton className="mb-3 h-4 w-24" />
                    <Skeleton className="h-8 w-32" />
                    <Skeleton className="mt-2 h-3 w-40" />
                  </CardContent>
                </Card>
              ))
            : kpis.map((kpi) => (
                <Card key={kpi.label} className="transition-shadow hover:shadow-md">
                  <CardContent className="flex items-start justify-between p-5">
                    <div>
                      <p className="text-sm text-muted-foreground">{kpi.label}</p>
                      <p className="mt-1 text-2xl font-bold tracking-tight">{kpi.value}</p>
                      <div className="mt-2 flex items-center gap-1.5">
                        <span
                          className={`flex items-center gap-0.5 text-xs font-medium ${
                            kpi.trendUp ? 'text-success' : 'text-destructive'
                          }`}
                        >
                          {kpi.trendUp ? <ArrowUpRight className="h-3 w-3" /> : <ArrowDownRight className="h-3 w-3" />}
                          {kpi.trend}
                        </span>
                        <span className="text-xs text-muted-foreground">{kpi.sub}</span>
                      </div>
                    </div>
                    <div className="rounded-lg bg-primary/10 p-2.5 text-primary">
                      <kpi.icon className="h-5 w-5" />
                    </div>
                  </CardContent>
                </Card>
              ))}
        </div>

        <div className="grid grid-cols-1 gap-4 xl:grid-cols-3">
          {/* Revenue chart */}
          <Card className="xl:col-span-2">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-base">Revenue Overview</CardTitle>
              <Badge variant="secondary" className="gap-1">
                <TrendingUp className="h-3 w-3" /> {range} days
              </Badge>
            </CardHeader>
            <CardContent>
              {loading ? (
                <Skeleton className="h-64 w-full" />
              ) : (
                <div className="h-64 w-full">
                  <ResponsiveContainer width="100%" height="100%">
                    <AreaChart data={revenueChartData} margin={{ top: 5, right: 5, bottom: 0, left: 0 }}>
                      <defs>
                        <linearGradient id="revGrad" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="0%" stopColor="#2563EB" stopOpacity={0.3} />
                          <stop offset="100%" stopColor="#2563EB" stopOpacity={0} />
                        </linearGradient>
                      </defs>
                      <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" vertical={false} />
                      <XAxis dataKey="label" tick={{ fontSize: 11 }} tickLine={false} axisLine={false} interval="preserveStartEnd" minTickGap={24} />
                      <YAxis tick={{ fontSize: 11 }} tickLine={false} axisLine={false} width={56} tickFormatter={(v: number) => (v >= 1000 ? `${(v / 1000).toFixed(0)}k` : String(v))} />
                      <Tooltip formatter={(value) => [currency(Number(value)), 'Revenue']} contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                      <Area type="monotone" dataKey="revenue" stroke="#2563EB" strokeWidth={2} fill="url(#revGrad)" />
                    </AreaChart>
                  </ResponsiveContainer>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Status breakdown */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Order Status</CardTitle>
            </CardHeader>
            <CardContent>
              {loading ? (
                <Skeleton className="h-64 w-full" />
              ) : (
                <div className="flex flex-col items-center gap-4">
                  <div className="h-44 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                      <PieChart>
                        <Pie data={statusData} dataKey="value" nameKey="name" innerRadius={50} outerRadius={75} paddingAngle={3}>
                          {statusData.map((entry, index) => (
                            <Cell key={entry.name} fill={CHART_COLORS[index % CHART_COLORS.length]} />
                          ))}
                        </Pie>
                        <Tooltip contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                      </PieChart>
                    </ResponsiveContainer>
                  </div>
                  <div className="grid w-full grid-cols-2 gap-1.5">
                    {statusData.map((entry, index) => (
                      <div key={entry.name} className="flex items-center gap-2 text-xs">
                        <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: CHART_COLORS[index % CHART_COLORS.length] }} />
                        <span className="flex-1 truncate text-muted-foreground">{entry.name}</span>
                        <span className="font-semibold">{entry.value}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="grid grid-cols-1 gap-4 xl:grid-cols-3">
          {/* Recent orders */}
          <Card className="xl:col-span-2">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-base">Recent Orders</CardTitle>
              <Button variant="ghost" size="sm" asChild>
                <Link to="/admin/orders">View all</Link>
              </Button>
            </CardHeader>
            <CardContent className="p-0">
              {loading ? (
                <div className="space-y-2 p-5">
                  {Array.from({ length: 5 }).map((_, i) => (
                    <Skeleton key={i} className="h-12 w-full" />
                  ))}
                </div>
              ) : data && data.recentOrders.length > 0 ? (
                <div className="divide-y">
                  {data.recentOrders.map((order) => (
                    <div key={order.id} className="flex items-center gap-3 px-5 py-3">
                      <div className="flex h-9 w-9 items-center justify-center rounded-full bg-muted text-xs font-bold">
                        {order.customerName?.charAt(0)?.toUpperCase() || '?'}
                      </div>
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium">{order.customerName}</p>
                        <p className="text-xs text-muted-foreground">
                          {order.orderId} · {new Date(order.createdAt).toLocaleDateString()}
                        </p>
                      </div>
                      <Badge variant="secondary" className={STATUS_COLORS[order.status] || ''}>
                        {STATUS_LABELS[order.status] || order.status}
                      </Badge>
                      <span className="text-sm font-semibold">{currency(order.totalPrice)}</span>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="p-6 text-sm text-muted-foreground">No orders yet</p>
              )}
            </CardContent>
          </Card>

          {/* Top products */}
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Top Products</CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              {loading ? (
                <div className="space-y-2 p-5">
                  {Array.from({ length: 5 }).map((_, i) => (
                    <Skeleton key={i} className="h-10 w-full" />
                  ))}
                </div>
              ) : data && data.topProducts.length > 0 ? (
                <div className="divide-y">
                  {data.topProducts.map((product, i) => (
                    <div key={product.id} className="flex items-center gap-3 px-5 py-2.5">
                      <span className="w-4 text-xs font-bold text-muted-foreground">{i + 1}</span>
                      {product.image ? (
                        <img src={product.image} alt="" className="h-9 w-9 rounded-md object-cover" />
                      ) : (
                        <div className="flex h-9 w-9 items-center justify-center rounded-md bg-muted">
                          <Package className="h-4 w-4 text-muted-foreground" />
                        </div>
                      )}
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium">{product.title}</p>
                        <p className="text-xs text-muted-foreground">{product.quantity} sold</p>
                      </div>
                      <span className="text-xs font-semibold">{currency(product.revenue)}</span>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="p-6 text-sm text-muted-foreground">No sales yet</p>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Low stock */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-base">Low Stock Alerts</CardTitle>
            <Button variant="ghost" size="sm" asChild>
              <Link to="/admin/inventory">Manage inventory</Link>
            </Button>
          </CardHeader>
          <CardContent className="p-0">
            {loading ? (
              <div className="space-y-2 p-5">
                {Array.from({ length: 3 }).map((_, i) => (
                  <Skeleton key={i} className="h-12 w-full" />
                ))}
              </div>
            ) : data && data.lowStockProducts.length > 0 ? (
              <div className="divide-y">
                {data.lowStockProducts.map((product) => (
                  <div key={product.id} className="flex items-center gap-3 px-5 py-3">
                    <Avatar className="h-9 w-9 rounded-md">
                      {product.image ? (
                        <AvatarImage src={product.image} alt="" />
                      ) : (
                        <AvatarFallback className="rounded-md">
                          <Package className="h-4 w-4" />
                        </AvatarFallback>
                      )}
                    </Avatar>
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-medium">{product.title}</p>
                      <p className="text-xs text-muted-foreground">{currency(product.price)}</p>
                    </div>
                    <Badge variant={product.stock === 0 ? 'destructive' : 'warning'}>
                      {product.stock === 0 ? 'Out of stock' : `${product.stock} left`}
                    </Badge>
                  </div>
                ))}
              </div>
            ) : (
              <p className="p-6 text-sm text-muted-foreground">All products are well stocked</p>
            )}
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  )
}

export default AdminDashboardPage
