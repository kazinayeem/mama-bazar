import { useCallback, useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
import InvoiceTemplate from '../../components/invoice/InvoiceTemplate'
import { downloadInvoicePdf, generateInvoiceFilename, printInvoice } from '../../lib/invoiceUtils'
import { adminApi } from '../../lib/adminApi'
import type { AdminOrder } from '../../types/admin'
import { SEO } from '../../components/common/SEO'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'

const AdminOrderInvoicePage = () => {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const [order, setOrder] = useState<AdminOrder | null>(null)
  const [loading, setLoading] = useState(true)
  const [downloading, setDownloading] = useState(false)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const data = await adminApi.getOrder(Number(id))
      setOrder(data)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load order')
    } finally {
      setLoading(false)
    }
  }, [id])

  useEffect(() => {
    void load()
  }, [load])

  const handleDownload = async () => {
    if (!order) return
    setDownloading(true)
    try {
      await downloadInvoicePdf('invoice-content', generateInvoiceFilename(order.orderId))
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Download failed')
    } finally {
      setDownloading(false)
    }
  }

  return (
    <AdminLayout>
      <SEO
        title={order ? `Invoice ${order.orderId}` : 'Invoice'}
        description="View, print or download the order invoice."
        url={`/admin/orders/${id}/invoice`}
      />
      <div className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Order Invoice</h1>
            <p className="text-sm text-muted-foreground">
              {order ? `Invoice INV-${order.orderId} · placed ${new Date(order.createdAt).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}` : 'Loading invoice...'}
            </p>
          </div>
          <Button variant="outline" onClick={() => navigate('/admin/orders')} className="w-fit">
            <ArrowLeft className="h-4 w-4" /> Back to Orders
          </Button>
        </div>

        {loading ? (
          <Card>
            <CardContent className="space-y-3 p-6">
              <Skeleton className="h-8 w-64" />
              <Skeleton className="h-40 w-full" />
              <Skeleton className="h-64 w-full" />
            </CardContent>
          </Card>
        ) : !order ? (
          <Card>
            <CardContent className="flex flex-col items-center gap-3 py-16 text-center">
              <p className="text-sm text-muted-foreground">Could not load the order for this invoice.</p>
              <Button variant="outline" onClick={() => navigate('/admin/orders')}>
                <ArrowLeft className="h-4 w-4" /> Back to Orders
              </Button>
            </CardContent>
          </Card>
        ) : (
          <InvoiceTemplate
            order={order}
            showActions
            onPrint={() => printInvoice('invoice-content')}
            onDownload={handleDownload}
          />
        )}
        {downloading && (
          <div className="fixed bottom-4 right-4 flex items-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm shadow-lg">
            <Loader2 className="h-4 w-4 animate-spin" /> Generating PDF...
          </div>
        )}
      </div>
    </AdminLayout>
  )
}

export default AdminOrderInvoicePage
