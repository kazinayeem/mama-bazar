import { useRef } from 'react'
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
  // Guard against double-clicks / duplicate submissions while the request is in
  // flight — the mutation's own isPending flag lags one render behind.
  const submittingRef = useRef(false)

  const handleSubmit = async (payload: ProductInput, mode: SaveMode) => {
    if (submittingRef.current) return
    submittingRef.current = true
    try {
      await createProduct({ ...payload, productStatus: mode === 'publish' ? 'published' : 'draft' }).unwrap()
      toast.success(mode === 'publish' ? 'Product published' : 'Draft saved')
      // Redirect to the list. The successful mutation invalidated the Products
      // tag, which drops the cached list (unsubscribed) — the list refetches on
      // mount, so the new product appears immediately without a refresh.
      navigate('/admin/products')
    } catch (err) {
      toast.error(parseError(err))
      const fieldErrors = (err as { data?: { errors?: Record<string, string> } })?.data?.errors
      if (fieldErrors && Object.keys(fieldErrors).length) {
        toast.error(
          Object.entries(fieldErrors)
            .map(([field, message]) =>
              field === 'relations'
                ? `• relations: ${message} — please check the category, brand, collection, vendor, supplier or related product selections.`
                : `• ${field}: ${message}`,
            )
            .join('\n'),
        )
      }
    } finally {
      submittingRef.current = false
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