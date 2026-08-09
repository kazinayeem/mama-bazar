import { useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import AdminLayout from '@/components/layout/AdminLayout'
import ProductForm, { type SaveMode } from '../components/ProductForm'
import { emptyForm } from '../lib/productForm'
import { parseError, useCreateProductMutation } from '@/store/services/adminProductsApi'
import type { ProductInput } from '@/types/admin'
import { SEO } from '@/components/common/SEO'

const AdminProductCreatePage = () => {
  const navigate = useNavigate()
  const [createProduct, { isLoading }] = useCreateProductMutation()

  const handleSubmit = async (payload: ProductInput, mode: SaveMode) => {
    try {
      const product = await createProduct({ ...payload, productStatus: mode === 'publish' ? 'published' : 'draft' }).unwrap()
      toast.success(mode === 'publish' ? 'Product published' : 'Draft saved')
      navigate(`/admin/products/${product.id}/edit`)
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

  return (
    <AdminLayout>
      <SEO title="Create Product" description="Add a new product to your catalog." url="/admin/products/create" />
      <ProductForm
        initialValues={emptyForm()}
        isEditing={false}
        submitting={isLoading}
        onCancel={() => navigate('/admin/products')}
        onSubmit={handleSubmit}
      />
    </AdminLayout>
  )
}

export default AdminProductCreatePage
