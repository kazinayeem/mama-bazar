import { useMemo } from 'react'
import type { AdminOrder } from '@/types/admin'
import type { UserOrderWithItems } from '@/types'

type InvoiceOrder = AdminOrder | UserOrderWithItems

interface InvoiceTemplateProps {
  order: InvoiceOrder
  showActions?: boolean
  onDownload?: () => void
  onPrint?: () => void
}

const PAYMENT_STATUS_LABELS: Record<string, string> = {
  pending: 'Pending',
  payment_pending: 'Payment Pending',
  payment_verification: 'Under Verification',
  verified: 'Verified',
  success: 'Paid',
  failed: 'Failed',
  rejected: 'Rejected',
  refunded: 'Refunded',
}

const ORDER_STATUS_LABELS: Record<string, string> = {
  pending: 'Pending',
  payment_pending: 'Payment Pending',
  payment_verification: 'Payment Verification',
  confirmed: 'Confirmed',
  processing: 'Processing',
  packed: 'Packed',
  shipped: 'Shipped',
  out_for_delivery: 'Out for Delivery',
  delivered: 'Delivered',
  returned: 'Returned',
  cancelled: 'Cancelled',
  refunded: 'Refunded',
}

const PAYMENT_STATUS_COLORS: Record<string, string> = {
  success: '#059669',
  verified: '#059669',
  paid: '#059669',
  pending: '#d97706',
  payment_pending: '#d97706',
  payment_verification: '#2563eb',
  failed: '#dc2626',
  rejected: '#dc2626',
  refunded: '#7c3aed',
}

const formatCurrency = (value: number | string) => {
  const amount = Number(value)
  if (Number.isNaN(amount)) return `৳${value}`
  return `৳${amount.toLocaleString('en-BD')}`
}

const formatDate = (date: string | Date) => {
  return new Date(date).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

const InvoiceTemplate = ({ order, showActions = false, onDownload, onPrint }: InvoiceTemplateProps) => {
  const items = useMemo(() => {
    return order.items || []
  }, [order.items])

  const subtotal = Number(order.subtotal) || 0
  const discount = Number(order.discount) || 0
  const shippingCost = Number(order.shippingCost) || 0
  const tax = Number(order.tax) || 0
  const totalPrice = Number(order.totalPrice) || 0

  const invoiceNumber = `INV-${order.orderId}`
  const paymentColor = PAYMENT_STATUS_COLORS[order.paymentStatus] || '#6b7280'

  return (
    <>
      {showActions && (
        <div className="no-print mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div>
            <h2 className="text-lg font-bold text-slate-900">Invoice {invoiceNumber}</h2>
            <p className="text-sm text-slate-500">Order {order.orderId} &middot; {formatDate(order.createdAt)}</p>
          </div>
          <div className="flex gap-2">
            {onPrint && (
              <button
                onClick={onPrint}
                className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
              >
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                </svg>
                Print Invoice
              </button>
            )}
            {onDownload && (
              <button
                onClick={onDownload}
                className="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
              >
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Download PDF
              </button>
            )}
          </div>
        </div>
      )}

      <div
        id="invoice-content"
        className="mx-auto"
        style={{
          width: '210mm',
          maxWidth: '100%',
          background: '#ffffff',
          fontFamily: "'Inter', 'Segoe UI', system-ui, sans-serif",
          fontSize: '11px',
          lineHeight: '1.5',
          color: '#1a1a1a',
          padding: '0',
        }}
      >
        <style>{`
          @media print {
            .invoice-page { 
              margin: 0; 
              padding: 12mm;
              width: 210mm;
              min-height: 297mm;
              max-height: 297mm;
              overflow: hidden;
              font-size: 10px;
            }
          }
          .invoice-page {
            padding: 32px;
          }
        `}</style>

        <div className="invoice-page">
          {/* Header */}
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '20px', paddingBottom: '16px', borderBottom: '3px solid #064E3B' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
              <img
                src="/brandlogo.png"
                alt="Mama Bazar"
                style={{ height: '40px', width: 'auto', objectFit: 'contain' }}
              />
              <div>
                <div style={{ fontSize: '18px', fontWeight: 800, color: '#064E3B', letterSpacing: '-0.02em' }}>
                  <span style={{ color: '#176B3A' }}>Mama</span><span style={{ color: '#F47B20' }}>Bazar</span>
                </div>
                <div style={{ fontSize: '9px', color: '#6b7280', marginTop: '2px' }}>
                  Home Appliances & Gadgets
                </div>
              </div>
            </div>

            <div style={{ textAlign: 'right' }}>
              <div style={{ fontSize: '24px', fontWeight: 800, color: '#064E3B', letterSpacing: '0.05em' }}>
                INVOICE
              </div>
              <div style={{ marginTop: '8px', fontSize: '10px', color: '#6b7280', lineHeight: '1.6' }}>
                <div><strong>Invoice:</strong> {invoiceNumber}</div>
                <div><strong>Order ID:</strong> {order.orderId}</div>
                <div><strong>Date:</strong> {formatDate(order.createdAt)}</div>
              </div>
            </div>
          </div>

          {/* Company + Bill To */}
          <div style={{ display: 'flex', justifyContent: 'space-between', gap: '24px', marginBottom: '20px' }}>
            <div style={{ flex: 1 }}>
              <div style={{ fontSize: '8px', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.15em', color: '#064E3B', marginBottom: '6px' }}>
                From
              </div>
              <div style={{ fontSize: '11px', lineHeight: '1.6', color: '#374151' }}>
                <div style={{ fontWeight: 700 }}>Mama Bazar</div>
                <div>Dhanmondi, Dhaka, Bangladesh</div>
                <div>Phone: +880 1XXX-XXXXXX</div>
                <div>Email: support@mamabazar.com</div>
                <div>Web: www.mamabazar.com</div>
              </div>
            </div>

            <div style={{ flex: 1 }}>
              <div style={{ fontSize: '8px', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.15em', color: '#064E3B', marginBottom: '6px' }}>
                Bill To
              </div>
              <div style={{ fontSize: '11px', lineHeight: '1.6', color: '#374151' }}>
                <div style={{ fontWeight: 700 }}>{order.customerName}</div>
                {order.phone && <div>Phone: {order.phone}</div>}
                {order.email && <div>Email: {order.email}</div>}
                {order.address && <div>{order.address}</div>}
                {(order.division || order.district || order.upazila || order.area) && (
                  <div>
                    {[order.division, order.district, order.upazila, order.area].filter(Boolean).join(', ')}
                    {order.postalCode ? ` - ${order.postalCode}` : ''}
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Items Table */}
          <div style={{ marginBottom: '16px' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '10px' }}>
              <thead>
                <tr style={{ background: '#064E3B', color: '#ffffff' }}>
                  <th style={{ padding: '8px 10px', textAlign: 'left', fontWeight: 600, fontSize: '9px', textTransform: 'uppercase', letterSpacing: '0.05em' }}>#</th>
                  <th style={{ padding: '8px 10px', textAlign: 'left', fontWeight: 600, fontSize: '9px', textTransform: 'uppercase', letterSpacing: '0.05em' }}>Product</th>
                  <th style={{ padding: '8px 10px', textAlign: 'left', fontWeight: 600, fontSize: '9px', textTransform: 'uppercase', letterSpacing: '0.05em' }}>Variant</th>
                  <th style={{ padding: '8px 10px', textAlign: 'center', fontWeight: 600, fontSize: '9px', textTransform: 'uppercase', letterSpacing: '0.05em' }}>Qty</th>
                  <th style={{ padding: '8px 10px', textAlign: 'right', fontWeight: 600, fontSize: '9px', textTransform: 'uppercase', letterSpacing: '0.05em' }}>Unit Price</th>
                  <th style={{ padding: '8px 10px', textAlign: 'right', fontWeight: 600, fontSize: '9px', textTransform: 'uppercase', letterSpacing: '0.05em' }}>Discount</th>
                  <th style={{ padding: '8px 10px', textAlign: 'right', fontWeight: 600, fontSize: '9px', textTransform: 'uppercase', letterSpacing: '0.05em' }}>Total</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item: any, index: number) => {
                  const unitPrice = Number(item.price) || 0
                  const quantity = item.quantity || 1
                  const lineTotal = unitPrice * quantity
                  const hasVariant = !!(item.color || item.size || item.variantName)
                  const variantParts = [item.color, item.size, item.variantName].filter(Boolean)
                  const variantStr = variantParts.length > 0 ? variantParts.join(' / ') : '-'
                  return (
                    <tr
                      key={item.id || index}
                      style={{
                        borderBottom: '1px solid #e5e7eb',
                        background: index % 2 === 0 ? '#ffffff' : '#f9fafb',
                      }}
                    >
                      <td style={{ padding: '8px 10px', color: '#9ca3af', fontWeight: 500 }}>{index + 1}</td>
                      <td style={{ padding: '8px 10px' }}>
                        <div style={{ fontWeight: 600, color: '#111827' }}>{item.product?.title || `Product #${item.productId}`}</div>
                        {item.product?.sku && (
                          <div style={{ fontSize: '9px', color: '#9ca3af', marginTop: '2px' }}>SKU: {item.product.sku}</div>
                        )}
                      </td>
                      <td style={{ padding: '8px 10px', color: hasVariant ? '#374151' : '#d1d5db' }}>
                        {hasVariant ? variantStr : '-'}
                      </td>
                      <td style={{ padding: '8px 10px', textAlign: 'center', fontWeight: 500 }}>{quantity}</td>
                      <td style={{ padding: '8px 10px', textAlign: 'right' }}>{formatCurrency(unitPrice)}</td>
                      <td style={{ padding: '8px 10px', textAlign: 'right', color: '#059669' }}>
                        {discount > 0 ? `-${formatCurrency(discount / items.length)}` : '-'}
                      </td>
                      <td style={{ padding: '8px 10px', textAlign: 'right', fontWeight: 600 }}>{formatCurrency(lineTotal)}</td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>

          {/* Totals + Payment Info */}
          <div style={{ display: 'flex', justifyContent: 'space-between', gap: '24px', marginBottom: '16px' }}>
            {/* Payment & Shipping Info */}
            <div style={{ flex: 1 }}>
              <div style={{ padding: '12px', border: '1px solid #e5e7eb', borderRadius: '8px', background: '#f9fafb' }}>
                <div style={{ fontSize: '8px', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.15em', color: '#064E3B', marginBottom: '8px' }}>
                  Payment Information
                </div>
                <div style={{ fontSize: '10px', lineHeight: '1.8', color: '#374151' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span>Payment Method:</span>
                    <span style={{ fontWeight: 600 }}>{(order.paymentMethod || 'cod').toUpperCase()}</span>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <span>Payment Status:</span>
                    <span style={{
                      fontWeight: 600,
                      color: paymentColor,
                      padding: '1px 6px',
                      borderRadius: '4px',
                      background: `${paymentColor}15`,
                      fontSize: '9px',
                    }}>
                      {PAYMENT_STATUS_LABELS[order.paymentStatus] || order.paymentStatus}
                    </span>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <span>Order Status:</span>
                    <span style={{
                      fontWeight: 600,
                      color: '#064E3B',
                      padding: '1px 6px',
                      borderRadius: '4px',
                      background: '#064E3B15',
                      fontSize: '9px',
                    }}>
                      {ORDER_STATUS_LABELS[order.status] || order.status}
                    </span>
                  </div>
                  {(order as any).transactionId && (
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                      <span>TrxID:</span>
                      <span style={{ fontFamily: 'monospace', fontWeight: 600 }}>{(order as any).transactionId}</span>
                    </div>
                  )}
                </div>
              </div>

              <div style={{ padding: '12px', border: '1px solid #e5e7eb', borderRadius: '8px', background: '#f9fafb', marginTop: '8px' }}>
                <div style={{ fontSize: '8px', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.15em', color: '#064E3B', marginBottom: '8px' }}>
                  Shipping Information
                </div>
                <div style={{ fontSize: '10px', lineHeight: '1.8', color: '#374151' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span>Shipping Method:</span>
                    <span style={{ fontWeight: 600 }}>{order.shippingMethodName || 'Standard'}</span>
                  </div>
                  {order.courierTrackingNumber && (
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                      <span>Tracking:</span>
                      <span style={{ fontFamily: 'monospace', fontWeight: 600 }}>{order.courierTrackingNumber}</span>
                    </div>
                  )}
                </div>
              </div>
            </div>

            {/* Totals */}
            <div style={{ width: '220px' }}>
              <div style={{ padding: '12px', border: '1px solid #e5e7eb', borderRadius: '8px', background: '#f9fafb' }}>
                <div style={{ fontSize: '8px', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.15em', color: '#064E3B', marginBottom: '8px' }}>
                  Order Summary
                </div>
                <div style={{ fontSize: '10px', lineHeight: '2', color: '#374151' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span>Subtotal</span>
                    <span style={{ fontWeight: 500 }}>{formatCurrency(subtotal)}</span>
                  </div>
                  {discount > 0 && (
                    <div style={{ display: 'flex', justifyContent: 'space-between', color: '#059669' }}>
                      <span>Discount{(order as any).couponCode ? ` (${(order as any).couponCode})` : ''}</span>
                      <span style={{ fontWeight: 500 }}>-{formatCurrency(discount)}</span>
                    </div>
                  )}
<div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span>Tax</span>
                    <span style={{ fontWeight: 500 }}>{formatCurrency(tax)}</span>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span>Shipping{order.shippingMethodName ? ` (${order.shippingMethodName})` : ''}</span>
                    <span style={{ fontWeight: 500 }}>{shippingCost === 0 ? 'FREE' : formatCurrency(shippingCost)}</span>
                  </div>
                  <div style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    borderTop: '2px solid #064E3B',
                    paddingTop: '6px',
                    marginTop: '4px',
                    fontSize: '13px',
                    fontWeight: 800,
                    color: '#064E3B',
                  }}>
                    <span>TOTAL</span>
                    <span>{formatCurrency(totalPrice)}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Footer */}
          <div style={{
            borderTop: '1px solid #e5e7eb',
            paddingTop: '12px',
            marginTop: '8px',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'flex-end',
            fontSize: '9px',
            color: '#6b7280',
          }}>
            <div style={{ lineHeight: '1.6' }}>
              <div style={{ fontWeight: 600, color: '#064E3B', marginBottom: '2px' }}>Thank you for shopping with Mama Bazar!</div>
              <div>For support: +880 1XXX-XXXXXX | support@mamabazar.com</div>
              <div>Terms & conditions apply. Return policy at www.mamabazar.com/return-refund</div>
            </div>
            <div style={{ textAlign: 'right', fontSize: '8px', color: '#9ca3af' }}>
              <div>Invoice generated on</div>
              <div>{new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}

export default InvoiceTemplate
