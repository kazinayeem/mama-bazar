import { useMemo, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
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
import { CalendarDays, Loader2, TrendingUp, Users, Wallet } from 'lucide-react'
import AdminLayout from '../../components/layout/AdminLayout'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { SEO } from '../../components/common/SEO'
import {
  useGetExpenseMonthlyReportQuery,
  useGetExpenseMonthlyTrendQuery,
  useGetExpenseByMemberQuery,
  useGetExpenseByCategoryQuery,
  useGetExpenseRangeReportQuery,
  useGetProfitOverviewQuery,
  useGetExpenseTeamMembersQuery,
  useGetAdminExpenseCategoriesQuery,
} from '@/store/services/adminProductsApi'
import { currency, formatNumber } from '@/lib/format'

const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
const MONTH_FULL = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']

const PIE_COLORS = ['#22c55e', '#f59e0b', '#3b82f6', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16']

const now = new Date()
const thisMonth = now.getMonth() + 1
const thisYear = now.getFullYear()
const monthOptions = MONTH_FULL.map((name, i) => ({ value: String(i + 1), label: name }))
const yearOptions = Array.from({ length: 6 }, (_, i) => String(thisYear - i))

const AdminExpenseReportsPage = () => {
  const [searchParams, setSearchParams] = useSearchParams()
  const tab = searchParams.get('tab') || 'overview'
  const setTab = (value: string) => {
    const next = new URLSearchParams(searchParams)
    if (value === 'overview') next.delete('tab')
    else next.set('tab', value)
    setSearchParams(next, { replace: true })
  }

  const { data: members = [] } = useGetExpenseTeamMembersQuery()
  const { data: categories = [] } = useGetAdminExpenseCategoriesQuery()

  const [month, setMonth] = useState(String(thisMonth))
  const [year, setYear] = useState(String(thisYear))
  const [memberFilter, setMemberFilter] = useState('')
  const [categoryFilter, setCategoryFilter] = useState('')
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')
  const [profitMonth, setProfitMonth] = useState(String(thisMonth))
  const [profitYear, setProfitYear] = useState(String(thisYear))

  const reportParams = useMemo(
    () => ({ year, month, memberId: memberFilter || undefined, categoryId: categoryFilter || undefined }),
    [year, month, memberFilter, categoryFilter],
  )
  const trendParams = useMemo(() => ({ year }), [year])
  const memberParams = useMemo(
    () => ({ memberId: memberFilter || undefined, dateFrom: dateFrom || undefined, dateTo: dateTo || undefined }),
    [memberFilter, dateFrom, dateTo],
  )
  const categoryParams = useMemo(
    () => ({ categoryId: categoryFilter || undefined, dateFrom: dateFrom || undefined, dateTo: dateTo || undefined }),
    [categoryFilter, dateFrom, dateTo],
  )
  const rangeParams = useMemo(
    () => ({ dateFrom: dateFrom || undefined, dateTo: dateTo || undefined }),
    [dateFrom, dateTo],
  )
  const profitParams = useMemo(() => ({ year: profitYear, month: profitMonth }), [profitYear, profitMonth])

  const { data: report, isFetching: reportLoading } = useGetExpenseMonthlyReportQuery(reportParams)
  const { data: trend, isFetching: trendLoading } = useGetExpenseMonthlyTrendQuery(trendParams)
  const { data: byMember, isFetching: memberLoading } = useGetExpenseByMemberQuery(memberParams)
  const { data: byCategory, isFetching: categoryLoading } = useGetExpenseByCategoryQuery(categoryParams)
  const { data: rangeReport, isFetching: rangeLoading } = useGetExpenseRangeReportQuery(rangeParams)
  const { data: profit, isFetching: profitLoading } = useGetProfitOverviewQuery(profitParams)

  const trendData = useMemo(
    () =>
      (trend?.data || []).map((row) => ({
        label: MONTH_NAMES[Math.max(0, row.month - 1)],
        total: Number(row.total),
        count: row.count,
      })),
    [trend],
  )

  const summaryCards = [
    { label: 'Total (Approved)', value: report?.total ?? 0 },
    { label: 'Transactions', value: report?.count ?? 0 },
    { label: 'Average', value: report?.average ?? 0 },
    { label: 'Highest', value: report?.highest ?? 0 },
    { label: 'Lowest', value: report?.lowest ?? 0 },
  ]

  const renderSummaryCards = () => (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
      {summaryCards.map((card) => (
        <Card key={card.label}>
          <CardContent className="p-4">
            <p className="text-xs text-muted-foreground">{card.label}</p>
            <p className="mt-1 text-lg font-bold">{currency(card.value)}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  )

  const renderTrendChart = () => {
    if (trendLoading) return <Skeleton className="h-64 w-full" />
    if (!trendData.length) return <p className="py-12 text-center text-sm text-muted-foreground">No data for {year}.</p>
    return (
      <ResponsiveContainer width="100%" height={260}>
        <AreaChart data={trendData}>
          <defs>
            <linearGradient id="expenseTrend" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor="#ef4444" stopOpacity={0.35} />
              <stop offset="95%" stopColor="#ef4444" stopOpacity={0} />
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
          <XAxis dataKey="label" tick={{ fontSize: 12 }} />
          <YAxis tick={{ fontSize: 12 }} tickFormatter={(v: number) => formatNumber(v)} width={50} />
          <Tooltip
            formatter={(value: number | string) => [currency(value), 'Spent']}
            labelFormatter={(label: string) => `${label} ${year}`}
          />
          <Area type="monotone" dataKey="total" stroke="#ef4444" fill="url(#expenseTrend)" strokeWidth={2} />
        </AreaChart>
      </ResponsiveContainer>
    )
  }

  const renderAggTable = (
    rows: Array<{ name: string; total: number; count: number }> | undefined,
    loading: boolean,
    valueColumn: string,
  ) => {
    if (loading) return <Skeleton className="h-40 w-full" />
    const data = rows || []
    if (!data.length) return <p className="py-10 text-center text-sm text-muted-foreground">No data for the selected filters.</p>
    const max = Math.max(1, ...data.map((r) => Number(r.total)))
    return (
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>{valueColumn}</TableHead>
            <TableHead className="text-right">Total</TableHead>
            <TableHead className="text-right">Count</TableHead>
            <TableHead className="w-40">Share</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {data.map((row) => (
            <TableRow key={row.name}>
              <TableCell className="font-medium">{row.name || 'Unassigned'}</TableCell>
              <TableCell className="text-right font-semibold">{currency(row.total)}</TableCell>
              <TableCell className="text-right">{row.count}</TableCell>
              <TableCell>
                <div className="flex items-center gap-2">
                  <div className="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                    <div className="h-full rounded-full bg-primary" style={{ width: `${(Number(row.total) / max) * 100}%` }} />
                  </div>
                  <span className="text-xs text-muted-foreground">
                    {Number(row.total) > 0 ? `${((Number(row.total) / (data.reduce((s, r) => s + Number(r.total), 0) || 1)) * 100).toFixed(1)}%` : '0%'}
                  </span>
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    )
  }

  return (
    <AdminLayout>
      <SEO title="Expense Reports" description="Monthly, range, breakdown and profit reports for expenses." url="/admin/expenses/reports" />
      <div className="space-y-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Expense Reports</h1>
          <p className="text-sm text-muted-foreground">Analyze spending, breakdowns and profitability</p>
        </div>

        <Tabs value={tab} onValueChange={setTab}>
          <TabsList className="flex-wrap">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="members">By Member</TabsTrigger>
            <TabsTrigger value="categories">By Category</TabsTrigger>
            <TabsTrigger value="range">Date Range</TabsTrigger>
            <TabsTrigger value="profit">Profit</TabsTrigger>
          </TabsList>

          {/* ==================== OVERVIEW ==================== */}
          <TabsContent value="overview" className="space-y-4">
            <div className="flex flex-wrap items-center gap-2">
              <Select value={year} onValueChange={setYear}>
                <SelectTrigger className="w-28">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {yearOptions.map((y) => <SelectItem key={y} value={y}>{y}</SelectItem>)}
                </SelectContent>
              </Select>
              <Select value={month} onValueChange={setMonth}>
                <SelectTrigger className="w-40">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {monthOptions.map((m) => <SelectItem key={m.value} value={m.value}>{m.label}</SelectItem>)}
                </SelectContent>
              </Select>
              <Select value={memberFilter} onValueChange={setMemberFilter}>
                <SelectTrigger className="w-44">
                  <SelectValue placeholder="All members" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All members</SelectItem>
                  {members.map((m) => <SelectItem key={m.id} value={String(m.id)}>{m.name}</SelectItem>)}
                </SelectContent>
              </Select>
              <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                <SelectTrigger className="w-44">
                  <SelectValue placeholder="All categories" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All categories</SelectItem>
                  {categories.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                </SelectContent>
              </Select>
              {reportLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
            </div>

            {renderSummaryCards()}

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <TrendingUp className="h-4 w-4" /> Monthly Trend — {year}
                </CardTitle>
              </CardHeader>
              <CardContent>{renderTrendChart()}</CardContent>
            </Card>

            <div className="grid gap-4 lg:grid-cols-2">
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2 text-base">
                    <Users className="h-4 w-4" /> By Member
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {renderAggTable(
                    (report?.byMember || []).map((r) => ({ name: r.memberName, total: Number(r.total), count: r.count })),
                    reportLoading,
                    'Member',
                  )}
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2 text-base">
                    <Wallet className="h-4 w-4" /> By Category
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {renderAggTable(
                    (report?.byCategory || []).map((r) => ({ name: r.categoryName, total: Number(r.total), count: r.count })),
                    reportLoading,
                    'Category',
                  )}
                </CardContent>
              </Card>
            </div>

            <Card>
              <CardHeader>
                <CardTitle className="text-base">
                  Expenses — {MONTH_FULL[Number(month) - 1]} {year}
                </CardTitle>
              </CardHeader>
              <CardContent className="p-0">
                {reportLoading ? (
                  <Skeleton className="h-40 w-full" />
                ) : !report?.expenses.length ? (
                  <p className="py-10 text-center text-sm text-muted-foreground">No expenses recorded for this period.</p>
                ) : (
                  <div className="overflow-x-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Expense</TableHead>
                          <TableHead>Category</TableHead>
                          <TableHead>Member</TableHead>
                          <TableHead className="text-right">Amount</TableHead>
                          <TableHead>Date</TableHead>
                          <TableHead>Status</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {report.expenses.slice(0, 15).map((e) => (
                          <TableRow key={e.id}>
                            <TableCell className="font-medium">{e.title}</TableCell>
                            <TableCell>{e.categoryName || '—'}</TableCell>
                            <TableCell>{e.memberName || '—'}</TableCell>
                            <TableCell className="text-right font-semibold">{currency(e.amount)}</TableCell>
                            <TableCell className="whitespace-nowrap">{e.expenseDate?.slice(0, 10)}</TableCell>
                            <TableCell>
                              <Badge variant={e.status === 'approved' ? 'success' : e.status === 'pending' ? 'warning' : 'destructive'}>
                                {e.status}
                              </Badge>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* ==================== BY MEMBER ==================== */}
          <TabsContent value="members" className="space-y-4">
            <div className="flex flex-wrap items-center gap-2">
              <Select value={memberFilter} onValueChange={setMemberFilter}>
                <SelectTrigger className="w-44">
                  <SelectValue placeholder="All members" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All members</SelectItem>
                  {members.map((m) => <SelectItem key={m.id} value={String(m.id)}>{m.name}</SelectItem>)}
                </SelectContent>
              </Select>
              <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="w-40" aria-label="From date" />
              <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="w-40" aria-label="To date" />
              {memberLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
            </div>
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <Users className="h-4 w-4" /> Spending by Member
                </CardTitle>
              </CardHeader>
              <CardContent className="p-0">
                {renderAggTable(
                  (byMember || []).map((r) => ({ name: r.memberName, total: Number(r.total), count: r.count })),
                  memberLoading,
                  'Member',
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* ==================== BY CATEGORY ==================== */}
          <TabsContent value="categories" className="space-y-4">
            <div className="flex flex-wrap items-center gap-2">
              <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                <SelectTrigger className="w-44">
                  <SelectValue placeholder="All categories" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All categories</SelectItem>
                  {categories.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                </SelectContent>
              </Select>
              <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="w-40" aria-label="From date" />
              <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="w-40" aria-label="To date" />
              {categoryLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
            </div>
            <div className="grid gap-4 lg:grid-cols-3">
              <Card className="lg:col-span-2">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2 text-base">
                    <Wallet className="h-4 w-4" /> Spending by Category
                  </CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                  {renderAggTable(
                    (byCategory || []).map((r) => ({ name: r.categoryName, total: Number(r.total), count: r.count })),
                    categoryLoading,
                    'Category',
                  )}
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle className="text-base">Category Share</CardTitle>
                </CardHeader>
                <CardContent>
                  {categoryLoading ? (
                    <Skeleton className="h-56 w-full" />
                  ) : !byCategory?.length ? (
                    <p className="py-10 text-center text-sm text-muted-foreground">No data.</p>
                  ) : (
                    <ResponsiveContainer width="100%" height={220}>
                      <PieChart>
                        <Pie data={byCategory} dataKey="total" nameKey="categoryName" innerRadius={50} outerRadius={85} paddingAngle={2}>
                          {byCategory.map((_, i) => (
                            <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
                          ))}
                        </Pie>
                        <Tooltip
                          formatter={(value: number | string, _name: string, props) => {
                            const row = props.payload as { categoryName?: string | null }
                            return [currency(value), row.categoryName || 'Unassigned']
                          }}
                        />
                      </PieChart>
                    </ResponsiveContainer>
                  )}
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* ==================== DATE RANGE ==================== */}
          <TabsContent value="range" className="space-y-4">
            <div className="flex flex-wrap items-center gap-2">
              <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="w-40" aria-label="From date" />
              <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="w-40" aria-label="To date" />
              {(dateFrom || dateTo) && (
                <Button variant="ghost" size="sm" onClick={() => { setDateFrom(''); setDateTo('') }}>
                  Clear
                </Button>
              )}
              {rangeLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
            </div>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
              <Card>
                <CardContent className="p-4">
                  <p className="text-xs text-muted-foreground">Total Spent</p>
                  <p className="mt-1 text-xl font-bold">{currency(rangeReport?.total ?? 0)}</p>
                </CardContent>
              </Card>
              <Card>
                <CardContent className="p-4">
                  <p className="text-xs text-muted-foreground">Transactions</p>
                  <p className="mt-1 text-xl font-bold">{rangeReport?.count ?? 0}</p>
                </CardContent>
              </Card>
              <Card className="col-span-2 sm:col-span-1">
                <CardContent className="p-4">
                  <p className="text-xs text-muted-foreground">Period</p>
                  <p className="mt-1 text-sm font-semibold">
                    {rangeReport?.dateFrom || 'All time'}
                    {rangeReport?.dateTo ? ` → ${rangeReport.dateTo}` : ''}
                  </p>
                </CardContent>
              </Card>
            </div>
            <div className="grid gap-4 lg:grid-cols-2">
              <Card>
                <CardHeader><CardTitle className="text-base">By Member</CardTitle></CardHeader>
                <CardContent className="p-0">
                  {renderAggTable(
                    (rangeReport?.byMember || []).map((r) => ({ name: r.memberName, total: Number(r.total), count: r.count })),
                    rangeLoading,
                    'Member',
                  )}
                </CardContent>
              </Card>
              <Card>
                <CardHeader><CardTitle className="text-base">By Category</CardTitle></CardHeader>
                <CardContent className="p-0">
                  {renderAggTable(
                    (rangeReport?.byCategory || []).map((r) => ({ name: r.categoryName, total: Number(r.total), count: r.count })),
                    rangeLoading,
                    'Category',
                  )}
                </CardContent>
              </Card>
            </div>
            <Card>
              <CardHeader>
                <CardTitle className="text-base">
                  <span className="flex items-center gap-2"><CalendarDays className="h-4 w-4" /> Expense List</span>
                </CardTitle>
              </CardHeader>
              <CardContent className="p-0">
                {rangeLoading ? (
                  <Skeleton className="h-40 w-full" />
                ) : !rangeReport?.expenses.length ? (
                  <p className="py-10 text-center text-sm text-muted-foreground">
                    {dateFrom || dateTo ? 'No expenses in the selected range.' : 'Pick a date range to see expenses.'}
                  </p>
                ) : (
                  <div className="overflow-x-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Expense</TableHead>
                          <TableHead>Category</TableHead>
                          <TableHead>Member</TableHead>
                          <TableHead className="text-right">Amount</TableHead>
                          <TableHead>Date</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {rangeReport.expenses.map((e) => (
                          <TableRow key={e.id}>
                            <TableCell className="font-medium">{e.title}</TableCell>
                            <TableCell>{e.categoryName || '—'}</TableCell>
                            <TableCell>{e.memberName || '—'}</TableCell>
                            <TableCell className="text-right font-semibold">{currency(e.amount)}</TableCell>
                            <TableCell className="whitespace-nowrap">{e.expenseDate?.slice(0, 10)}</TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* ==================== PROFIT ==================== */}
          <TabsContent value="profit" className="space-y-4">
            <div className="flex flex-wrap items-center gap-2">
              <Select value={profitYear} onValueChange={setProfitYear}>
                <SelectTrigger className="w-28">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {yearOptions.map((y) => <SelectItem key={y} value={y}>{y}</SelectItem>)}
                </SelectContent>
              </Select>
              <Select value={profitMonth} onValueChange={setProfitMonth}>
                <SelectTrigger className="w-40">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {monthOptions.map((m) => <SelectItem key={m.value} value={m.value}>{m.label}</SelectItem>)}
                </SelectContent>
              </Select>
              {profitLoading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
            </div>

            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
              <Card>
                <CardContent className="p-4">
                  <p className="text-xs text-muted-foreground">Revenue</p>
                  <p className="mt-1 text-lg font-bold">{currency(profit?.revenue ?? 0)}</p>
                </CardContent>
              </Card>
              <Card>
                <CardContent className="p-4">
                  <p className="text-xs text-muted-foreground">Product Cost</p>
                  <p className="mt-1 text-lg font-bold">{currency(profit?.productCost ?? 0)}</p>
                </CardContent>
              </Card>
              <Card>
                <CardContent className="p-4">
                  <p className="text-xs text-muted-foreground">Operating Expenses</p>
                  <p className="mt-1 text-lg font-bold">{currency(profit?.operatingExpenses ?? 0)}</p>
                </CardContent>
              </Card>
              <Card>
                <CardContent className={`p-4 ${(profit?.netProfit ?? 0) >= 0 ? '' : 'bg-destructive/5'}`}>
                  <p className="text-xs text-muted-foreground">Net Profit</p>
                  <p className={`mt-1 text-lg font-bold ${(profit?.netProfit ?? 0) >= 0 ? 'text-success' : 'text-destructive'}`}>
                    {currency(profit?.netProfit ?? 0)}
                  </p>
                </CardContent>
              </Card>
            </div>

            <Card>
              <CardContent className="p-4">
                {profitLoading ? (
                  <Skeleton className="h-40 w-full" />
                ) : !profit?.hasRevenueData ? (
                  <div className="py-10 text-center">
                    <p className="text-sm text-muted-foreground">
                      No revenue data found for {MONTH_FULL[Number(profitMonth) - 1]} {profitYear}.
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                      Expenses are still tracked, but profit can only be computed when revenue data is available.
                    </p>
                    <Button variant="outline" size="sm" className="mt-4" asChild>
                      <Link to="/admin/analytics">View analytics</Link>
                    </Button>
                  </div>
                ) : (
                  <ResponsiveContainer width="100%" height={260}>
                    <AreaChart data={[{ label: `${MONTH_NAMES[Number(profitMonth) - 1]} ${profitYear}`, revenue: profit?.revenue ?? 0, productCost: profit?.productCost ?? 0, operatingExpenses: profit?.operatingExpenses ?? 0, netProfit: profit?.netProfit ?? 0 }]}>
                      <defs>
                        <linearGradient id="profitNet" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="5%" stopColor="#22c55e" stopOpacity={0.35} />
                          <stop offset="95%" stopColor="#22c55e" stopOpacity={0} />
                        </linearGradient>
                      </defs>
                      <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                      <XAxis dataKey="label" tick={{ fontSize: 12 }} />
                      <YAxis tick={{ fontSize: 12 }} tickFormatter={(v: number) => formatNumber(v)} width={50} />
                      <Tooltip formatter={(value: number | string, name: string) => [currency(value), name]} />
                      <Area type="monotone" dataKey="revenue" stroke="#3b82f6" fill="none" strokeWidth={2} name="Revenue" />
                      <Area type="monotone" dataKey="productCost" stroke="#f59e0b" fill="none" strokeWidth={2} name="Product Cost" />
                      <Area type="monotone" dataKey="operatingExpenses" stroke="#ef4444" fill="none" strokeWidth={2} name="Operating Expenses" />
                      <Area type="monotone" dataKey="netProfit" stroke="#22c55e" fill="url(#profitNet)" strokeWidth={2} name="Net Profit" />
                    </AreaChart>
                  </ResponsiveContainer>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </AdminLayout>
  )
}

export default AdminExpenseReportsPage
