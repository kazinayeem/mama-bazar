import AdminProductListPage from '@/features/products/pages/AdminProductListPage'
import { SEO } from '../../components/common/SEO'

const AdminProductsPage = () => (
  <>
    <SEO title="Manage Products" description="Manage your product catalog. Add, edit, and organize products." url="/admin/products" />
    <AdminProductListPage />
  </>
)

export default AdminProductsPage
