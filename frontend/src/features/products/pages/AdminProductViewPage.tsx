import { useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, ExternalLink, Loader2, Pencil } from 'lucide-react'
import AdminLayout from '@/components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatDate } from '../lib/formatDate'
import { currency } from '@/lib/format'
import { parseError, useGetAdminProductByIdQuery } from '@/store/services/adminProductsApi'
import type { AdminProduct } from '@/types/admin'
import { SEO } from '@/components/common/SEO'

const STATUS_LABELS: Record<string, string> = {
  draft: 'Draft',
  published: 'Published',
  hidden: 'Hidden',
  archived: 'Archived',
}

const FLAG_LABELS: Array<{ key: keyof AdminProduct; label: string }> = [
  { key: 'isFeatured', label: 'Featured' },
  { key: 'isTrending', label: 'Trending' },
  { key: 'isFlashSale', label: 'Flash Sale' },
  { key: 'isNewArrival', label: 'New Arrival' },
  { key: 'isBestSeller', label: 'Best Seller' },
  { key: 'isLimitedEdition', label: 'Limited Edition' },
  { key: 'isOfficial', label: 'Official' },
  { key: 'isHotDeal', label: 'Hot Deal' },
]

const Field = ({ label, value }: { label: string; value?: React.ReactNode }) => (
  <div className="flex items-start justify-between gap-4 border-b py-2 last:border-0">
    <span className="shrink-0 text-xs font-medium text-muted-foreground">{label}</span>
    <span className="text-right text-sm">{value || '—'}</span>
  </div>
)

const AdminProductViewPage = () => {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const productId = Number(id)

  const { data: product, isLoading, isError, error, refetch } = useGetAdminProductByIdQuery(productId, { skip: !productId })

  if (isLoading) {
    return (
      <AdminLayout>
        <div className="flex h-[60vh] items-center justify-center">
          <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
        </div>
      </AdminLayout>
    )
  }

  if (isError || !product) {
    return (
      <AdminLayout>
        <div className="flex h-[60vh] flex-col items-center justify-center gap-3">
          <p className="text-sm text-destructive">{parseError(error)}</p>
          <Button variant="outline" onClick={refetch}>
            Retry
          </Button>
        </div>
      </AdminLayout>
    )
  }

  const activeFlags = FLAG_LABELS.filter((f) => product[f.key] === true)

  return (
    <AdminLayout>
      <SEO title="View Product" description="View product details and information." url="/admin/products/view" />
      <div className="mx-auto w-full max-w-[1400px] space-y-5">
        {/* Header */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-3">
            <Button variant="ghost" size="icon" onClick={() => navigate('/admin/products')} aria-label="Back to products">
              <ArrowLeft className="h-4 w-4" />
            </Button>
            <div>
              <h1 className="text-2xl font-bold tracking-tight">{product.title}</h1>
              <p className="flex items-center gap-2 text-sm text-muted-foreground">
                <span>SKU: {product.sku || '—'}</span>
                <span>·</span>
                <span>Created {formatDate(product.createdAt)}</span>
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            {product.slug && (
              <Button variant="outline" onClick={() => navigate(`/products/${product.slug}`)}>
                <ExternalLink className="mr-1 h-4 w-4" /> View on storefront
              </Button>
            )}
            <Button onClick={() => navigate(`/admin/products/${product.id}/edit`)}>
              <Pencil className="mr-1 h-4 w-4" /> Edit
            </Button>
          </div>
        </div>

        {/* Status + badges */}
        <Card>
          <CardContent className="flex flex-wrap items-center gap-2 p-4">
            <Badge variant={product.productStatus === 'published' ? 'success' : 'secondary'}>
              {STATUS_LABELS[product.productStatus || ''] || product.productStatus || '—'}
            </Badge>
            <Badge variant={product.stock > 0 ? 'secondary' : 'destructive'}>
              Stock: {product.stock}
            </Badge>
            <Badge variant="outline">Stock status: {product.stockStatus || '—'}</Badge>
            {activeFlags.map((f) => (
              <Badge key={f.key} variant="warning">
                {f.label}
              </Badge>
            ))}
          </CardContent>
        </Card>

        <div className="grid gap-5 lg:grid-cols-3">
          {/* Images */}
          <Card className="lg:col-span-1">
            <CardHeader>
              <CardTitle className="text-base">Images</CardTitle>
            </CardHeader>
            <CardContent>
              {product.images.length === 0 ? (
                <p className="text-sm text-muted-foreground">No images</p>
              ) : (
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                  {product.images.map((src, i) => (
                    <img key={i} src={src} alt={`${product.title} ${i + 1}`} className="aspect-square w-full rounded-md border object-cover" />
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          {/* Details */}
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle className="text-base">Details</CardTitle>
            </CardHeader>
            <CardContent>
              <Field label="Price" value={currency(product.price)} />
              <Field label="Sale price" value={product.salePrice ? currency(product.salePrice) : '—'} />
              <Field label="Discount" value={Number(product.discount) > 0 ? `-${product.discount}%` : '—'} />
              <Field label="Cost price" value={product.costPrice ? currency(product.costPrice) : '—'} />
              <Field label="Category" value={product.category?.name || '—'} />
              <Field label="Sub-category" value={product.subCategory?.name || '—'} />
              <Field label="Child category" value={product.childCategory?.name || '—'} />
              <Field label="Brand" value={product.brandInfo?.name || product.brand || '—'} />
              <Field label="Collection" value={product.collection?.name || '—'} />
              <Field label="Vendor" value={product.vendor?.name || '—'} />
              <Field label="Supplier" value={product.supplierInfo?.name || product.supplier || '—'} />
              <Field label="Country of origin" value={product.countryOfOrigin || '—'} />
              <Field label="Weight" value={product.weight || '—'} />
              <Field label="Dimensions" value={product.dimensions || '—'} />
              <Field label="Warranty" value={product.warranty || '—'} />
              <Field label="Tags" value={product.tags?.join(', ') || '—'} />
            </CardContent>
          </Card>
        </div>

        {/* Description */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Description</CardTitle>
          </CardHeader>
          <CardContent>
            {product.description ? (
              <div
                className="prose prose-sm max-w-none"
                dangerouslySetInnerHTML={{ __html: product.description }}
              />
            ) : (
              <p className="text-sm text-muted-foreground">No description</p>
            )}
          </CardContent>
        </Card>

        {/* Specs + variants + relations */}
        <div className="grid gap-5 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Specifications</CardTitle>
            </CardHeader>
            <CardContent>
              {!product.specs || product.specs.length === 0 ? (
                <p className="text-sm text-muted-foreground">No specifications</p>
              ) : (
                product.specs.map((s) => <Field key={s.id} label={s.label} value={s.value} />)
              )}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Variants</CardTitle>
            </CardHeader>
            <CardContent>
              {!product.variants || product.variants.length === 0 ? (
                <p className="text-sm text-muted-foreground">No variants</p>
              ) : (
                <div className="space-y-2">
                  {product.variants.map((v) => (
                    <div key={v.id} className="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm">
                      <div className="min-w-0">
                        <p className="truncate font-medium">{v.name}</p>
                        {v.options && Object.keys(v.options).length > 0 && (
                          <p className="truncate text-xs text-muted-foreground">
                            {Object.entries(v.options).map(([k, val]) => `${k}: ${val}`).join(' · ')}
                          </p>
                        )}
                      </div>
                      <span className="shrink-0 text-muted-foreground">
                        {v.price ? currency(v.price) : currency(product.price)} · qty {v.stock ?? product.stock}
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {product.relations && product.relations.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Related products</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-wrap gap-2">
              {product.relations.map((r, i) => (
                <Badge key={i} variant="outline">
                  {r.type} → #{r.relatedProductId}
                </Badge>
              ))}
            </CardContent>
          </Card>
        )}

        {/* SEO */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">SEO</CardTitle>
          </CardHeader>
          <CardContent>
            <Field label="SEO title" value={product.seoTitle || '—'} />
            <Field label="SEO description" value={product.seoDescription || '—'} />
            <Field label="SEO keywords" value={product.seoKeywords || '—'} />
            <Field label="Canonical URL" value={product.canonicalUrl || '—'} />
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  )
}

export default AdminProductViewPage
