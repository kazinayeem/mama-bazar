import { useCallback, useEffect, useMemo, useState } from 'react'
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { Boxes, CircleDollarSign, ShoppingCart, Users } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { adminApi } from '@/lib/adminApi'
import { currency, formatNumber } from '@/lib/format'
import type { DashboardData } from '@/types/admin'
import { SEO } from '../../components/common/SEO'

const CHART_COLORS = ['#2563EB', '#F97316', '#22C55E', '#8B5CF6', '#EC4899', '#14B8A6', '#F59E0B']

const PAYMENT_LABELS: Record<string, string> = {
  bKash: 'bKash',
  Nagad: 'Nagad',
  COD: 'Cash on Delivery',
  cash_on_delivery: 'Cash on Delivery',
  card: 'Credit Card',
  bank: 'Bank Transfer',
}

const AdminAnalyticsPage = () => {
  const [range, setRange] = useState('30')
  const [data, setData] = useState<DashboardData | null>(null)
  const [loading, setLoading] = useState(true)

  const load = useCallback(async (r: string) => {
    setLoading(true)
    try {
      const result = await adminApi.getDashboard(r)
      setData(result)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load analytics')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load(range)
  }, [range, load])

  const revenueChart = useMemo(
    () =>
      (data?.revenueChart || []).map((point) => ({
        ...point,
        revenue: Number(point.revenue),
      })),
    [data?.revenueChart],
  )

  const statusRows = useMemo(
    () =>
      Object.entries(data?.statusBreakdown || {}).map(([status, count]) => ({
        name: status.replace(/_/g, ' '),
        count,
        status,
      })),
    [data?.statusBreakdown],
  )

  const paymentRows = useMemo(
    () =>
      (data?.paymentBreakdown || []).map((row) => ({
        name: PAYMENT_LABELS[row.method] || row.method,
        count: row.count,
        revenue: Number(row.revenue),
      })),
    [data?.paymentBreakdown],
  )

  const kpis = data?.kpis

  return (
    <AdminLayout>
      <SEO title="Analytics" description="View store analytics and reports." url="/admin/analytics" />
      <header className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Analytics</h1>
          <p className="mt-1 text-sm text-muted-foreground">Live sales, revenue, and order metrics</p>
        </div>
        <Select value={range} onValueChange={setRange}>
          <SelectTrigger className="w-40">
            <SelectValue placeholder="Time range" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="7">Last 7 days</SelectItem>
            <SelectItem value="30">Last 30 days</SelectItem>
            <SelectItem value="365">Last 365 days</SelectItem>
          </SelectContent>
        </Select>
      </header>

      {loading ? (
        <div className="space-y-6">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {Array.from({ length: 4 }).map((_, i) => (
              <Skeleton key={i} className="h-28 w-full" />
            ))}
          </div>
          <Skeleton className="h-72 w-full" />
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
              <CardContent className="flex items-center gap-4 p-5">
                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                  <CircleDollarSign className="h-6 w-6" />
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Total Revenue</p>
                  <p className="text-2xl font-bold">{currency(kpis?.totalRevenue || 0)}</p>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="flex items-center gap-4 p-5">
                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-success/10 text-success">
                  <ShoppingCart className="h-6 w-6" />
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Total Orders</p>
                  <p className="text-2xl font-bold">{formatNumber(kpis?.totalOrders || 0)}</p>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="flex items-center gap-4 p-5">
                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-accent/10 text-accent">
                  <Users className="h-6 w-6" />
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Total Customers</p>
                  <p className="text-2xl font-bold">{formatNumber(kpis?.totalCustomers || 0)}</p>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="flex items-center gap-4 p-5">
                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-warning/10 text-warning">
                  <Boxes className="h-6 w-6" />
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Active Products</p>
                  <p className="text-2xl font-bold">{formatNumber(kpis?.totalProducts || 0)}</p>
                </div>
              </CardContent>
            </Card>
          </div>

          <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <Card className="xl:col-span-2">
              <CardHeader>
                <CardTitle className="text-base">Revenue Over Time</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="h-80">
                  <ResponsiveContainer width="100%" height="100%">
                    <AreaChart data={revenueChart}>
                      <defs>
                        <linearGradient id="rev" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="5%" stopColor="#2563EB" stopOpacity={0.3} />
                          <stop offset="95%" stopColor="#2563EB" stopOpacity={0} />
                        </linearGradient>
                      </defs>
                      <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                      <XAxis dataKey="date" tick={{ fontSize: 11 }} />
                      <YAxis tick={{ fontSize: 11 }} tickFormatter={(v: number) => formatNumber(v)} />
                      <Tooltip formatter={(value) => [currency(Number(value)), 'Revenue']} contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                      <Area type="monotone" dataKey="revenue" stroke="#2563EB" strokeWidth={2} fill="url(#rev)" />
                    </AreaChart>
                  </ResponsiveContainer>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="text-base">Order Status Breakdown</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="h-64">
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie data={statusRows} dataKey="count" nameKey="name" innerRadius={55} outerRadius={85} paddingAngle={2}>
                        {statusRows.map((entry, index) => (
                          <Cell key={entry.name} fill={CHART_COLORS[index % CHART_COLORS.length]} />
                        ))}
                      </Pie>
                      <Tooltip contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                    </PieChart>
                  </ResponsiveContainer>
                </div>
                <div className="mt-2 space-y-1.5">
                  {statusRows.map((row, index) => (
                    <div key={row.name} className="flex items-center justify-between text-sm">
                      <span className="flex items-center gap-2">
                        <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: CHART_COLORS[index % CHART_COLORS.length] }} />
                        {row.name}
                      </span>
                      <span className="font-medium">{row.count}</span>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="text-base">Payment Method Distribution</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="h-72">
                  <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={paymentRows}>
                      <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                      <XAxis dataKey="name" tick={{ fontSize: 10 }} />
                      <YAxis tick={{ fontSize: 11 }} />
                      <Tooltip formatter={(value, name) => (name === 'count' ? [value, 'Orders'] : [currency(Number(value)), 'Revenue'])} contentStyle={{ borderRadius: 8, fontSize: 12 }} />
                      <Bar dataKey="count" fill="#F97316" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              </CardContent>
            </Card>

            <Card className="xl:col-span-2">
              <CardHeader>
                <CardTitle className="text-base">Top Products</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {data?.topProducts.length === 0 && (
                  <p className="py-6 text-center text-sm text-muted-foreground">No sales yet</p>
                )}
                {data?.topProducts.map((product, index) => (
                  <div key={product.id} className="flex items-center gap-4">
                    <span className="w-5 text-sm font-semibold text-muted-foreground">{index + 1}</span>
                    {product.image ? (
                      <img src={product.image} alt={product.title} className="h-11 w-11 rounded-lg object-cover" />
                    ) : (
                      <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-muted text-xs text-muted-foreground">No img</div>
                    )}
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-medium">{product.title}</p>
                      <p className="text-xs text-muted-foreground">{product.quantity} sold</p>
                    </div>
                    <span className="text-sm font-semibold">{currency(product.revenue)}</span>
                  </div>
                ))}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="text-base">Top Categories</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {data?.topCategories.length === 0 && (
                  <p className="py-6 text-center text-sm text-muted-foreground">No data yet</p>
                )}
                {data?.topCategories.map((category, index) => (
                  <div key={index} className="flex items-center justify-between">
                    <span className="flex items-center gap-2 text-sm">
                      <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: CHART_COLORS[index % CHART_COLORS.length] }} />
                      {category.name || 'Uncategorized'}
                    </span>
                    <span className="text-sm font-medium">{category.count} products</span>
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>
        </>
      )}
    </AdminLayout>
  )
}

export default AdminAnalyticsPage
