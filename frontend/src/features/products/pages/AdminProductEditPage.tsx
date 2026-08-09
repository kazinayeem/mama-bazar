import { useNavigate, useParams } from 'react-router-dom'
import { toast } from 'sonner'
import { Loader2 } from 'lucide-react'
import AdminLayout from '@/components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import ProductForm, { type SaveMode } from '../components/ProductForm'
import { productToFormValues } from '../lib/productForm'
import { parseError, useGetAdminProductByIdQuery, useUpdateProductMutation } from '@/store/services/adminProductsApi'
import type { ProductInput } from '@/types/admin'
import { SEO } from '@/components/common/SEO'

const AdminProductEditPage = () => {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const productId = Number(id)

  const { data: product, isLoading, isError, error, refetch } = useGetAdminProductByIdQuery(productId, { skip: !productId })
  const [updateProduct, { isLoading: submitting }] = useUpdateProductMutation()

  const handleSubmit = async (payload: ProductInput, mode: SaveMode) => {
    try {
      await updateProduct({
        id: productId,
        payload: { ...payload, productStatus: mode === 'publish' ? 'published' : 'draft' },
      }).unwrap()
      toast.success(mode === 'publish' ? 'Product published' : 'Changes saved')
    } catch (err) {
      toast.error(parseError(err))
      const fieldErrors = (err as { data?: { errors?: Record<string, string> } })?.data?.errors
      if (fieldErrors && Object.keys(fieldErrors).length) {
        toast.error(
          Object.entries(fieldErrors)
            .map(([field, message]) => `• ${field}: ${message}`)
            .join('\n'),
        )
      }
    }
  }

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
          <Button variant="ghost" onClick={() => navigate('/admin/products')}>
            Back to products
          </Button>
        </div>
      </AdminLayout>
    )
  }

  return (
    <AdminLayout>
      <SEO title="Edit Product" description="Edit product details and information." url="/admin/products/edit" />
      <ProductForm
        key={product.id}
        initialValues={productToFormValues(product)}
        isEditing
        submitting={submitting}
        onCancel={() => navigate('/admin/products')}
        onSubmit={handleSubmit}
        onPreview={() => product.slug && navigate(`/products/${product.slug}`)}
      />
    </AdminLayout>
  )
}

export default AdminProductEditPage
